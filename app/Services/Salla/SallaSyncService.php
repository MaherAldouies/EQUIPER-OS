<?php

namespace App\Services\Salla;

use App\Models\Integration;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SallaSyncService — the Anti-Corruption Layer (Business Ontology,
 * Section 0) between Salla's data model and EQUIPER OS's own language.
 *
 * IMPORTANT: This is a Sprint 0/1 SKELETON. The actual Salla Partner API
 * client, webhook signature verification, and full field mapping are
 * explicitly flagged as the highest-risk unknown in the PRD (Sprint 0's
 * mandatory technical spike) — do NOT treat the mapping logic below as
 * final until that spike confirms Salla's real API/webhook payload shape.
 *
 * This class deliberately owns 100% of the Salla-shape-awareness in the
 * system — no other class should ever read a raw Salla payload field.
 */
class SallaSyncService
{
    public function __construct(
        private readonly string $organizationId,
    ) {}

    /**
     * Upserts a single product from a raw Salla payload (webhook or
     * reconciliation poll) into EQUIPER OS's own Product entity.
     *
     * @param  array  $sallaPayload  Raw payload as received from Salla — shape TBD by Sprint 0 spike.
     */
    public function syncProduct(array $sallaPayload): Product
    {
        try {
            $product = DB::transaction(function () use ($sallaPayload) {
                $product = Product::query()->updateOrCreate(
                    [
                        'organization_id' => $this->organizationId,
                        'salla_product_id' => (string) ($sallaPayload['id'] ?? throw new \InvalidArgumentException('Salla payload missing id')),
                    ],
                    [
                        // --- Translation layer: Salla's shape -> EQUIPER OS's language ---
                        // NOTE: field names below are placeholders pending the Sprint 0
                        // Salla API spike confirming the actual payload shape.
                        'salla_raw_payload' => $sallaPayload,
                        'salla_category_name' => $sallaPayload['category']['name'] ?? null,
                        'name' => $sallaPayload['name'] ?? '',
                        'sku' => $sallaPayload['sku'] ?? null,
                        'price' => $sallaPayload['price']['amount'] ?? null,
                        'stock_quantity' => $sallaPayload['quantity'] ?? 0,
                        'stock_status' => $this->mapStockStatus((int) ($sallaPayload['quantity'] ?? 0)),
                        'last_synced_at' => now(),
                    ]
                );

                $eventType = $product->wasRecentlyCreated ? 'SallaProductSynced' : 'StockLevelChanged';

                $product->recordEvent(
                    eventType: $eventType,
                    payload: ['salla_product_id' => $product->salla_product_id],
                    actorType: 'system',
                );

                return $product;
            });

            $this->integration()->markHealthy();

            return $product;
        } catch (Throwable $e) {
            Log::error('Salla product sync failed', ['error' => $e->getMessage()]);
            $this->integration()->markDegraded($e->getMessage());
            throw $e;
        }
    }

    /**
     * Sprint 2 scope (PRD): mirrors an Order from Salla. Per the Business
     * Ontology's explicit rule, Orders are read-only and only ever
     * written here — no other code path may create/update an Order.
     *
     * @param  array  $sallaPayload  Raw payload — shape TBD by Sprint 0 spike.
     */
    public function syncOrder(array $sallaPayload): Order
    {
        try {
            $order = DB::transaction(function () use ($sallaPayload) {
                $order = Order::query()->updateOrCreate(
                    [
                        'organization_id' => $this->organizationId,
                        'salla_order_id' => (string) ($sallaPayload['id'] ?? throw new \InvalidArgumentException('Salla order payload missing id')),
                    ],
                    [
                        'salla_raw_payload' => $sallaPayload,
                        'status' => $this->mapOrderStatus($sallaPayload['status'] ?? 'unknown'),
                        'total_amount' => $sallaPayload['amounts']['total']['amount'] ?? 0,
                        'currency' => $sallaPayload['currency'] ?? 'SAR',
                        'customer_reference' => $sallaPayload['customer']['id'] ?? null,
                        'placed_at' => $sallaPayload['created_at'] ?? now(),
                        'last_synced_at' => now(),
                    ]
                );

                // Line items are always fully replaced on sync — simpler
                // and safer than diffing, given Orders are read-only mirrors.
                $order->lineItems()->delete();
                foreach ($sallaPayload['items'] ?? [] as $item) {
                    $product = Product::query()
                        ->where('organization_id', $this->organizationId)
                        ->where('salla_product_id', (string) ($item['product_id'] ?? ''))
                        ->first();

                    OrderLineItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product?->id,
                        'product_name_snapshot' => $item['name'] ?? 'Unknown',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['price']['amount'] ?? 0,
                    ]);
                }

                $order->recordEvent(
                    eventType: 'SallaOrderSynced',
                    payload: ['salla_order_id' => $order->salla_order_id, 'status' => $order->status],
                    actorType: 'system',
                );

                return $order;
            });

            $this->integration()->markHealthy();

            return $order;
        } catch (Throwable $e) {
            Log::error('Salla order sync failed', ['error' => $e->getMessage()]);
            $this->integration()->markDegraded($e->getMessage());
            throw $e;
        }
    }

    private function mapOrderStatus(string $sallaStatus): string
    {
        // Placeholder mapping pending Sprint 0 spike confirming Salla's
        // actual status vocabulary.
        return match ($sallaStatus) {
            'completed', 'delivered' => 'completed',
            'processing', 'confirmed' => 'confirmed',
            'shipped' => 'fulfilled',
            'cancelled' => 'cancelled',
            'returned', 'refunded' => 'returned',
            default => 'placed',
        };
    }

    /**
     * Business rule (Business Ontology, Inventory entity): crossing the
     * low-stock threshold must be able to trigger downstream Automation
     * Rule evaluation (PRD F14) — this is why stock_status is a distinct,
     * queryable field rather than only a raw quantity.
     */
    private function mapStockStatus(int $quantity, int $lowStockThreshold = 5): string
    {
        return match (true) {
            $quantity <= 0 => 'out_of_stock',
            $quantity <= $lowStockThreshold => 'low_stock',
            default => 'in_stock',
        };
    }

    private function integration(): Integration
    {
        return Integration::query()->firstOrCreate(
            ['organization_id' => $this->organizationId, 'provider' => 'salla'],
            ['status' => 'configuring']
        );
    }
}
