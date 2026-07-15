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
 * Field mapping confirmed against docs.salla.dev (Product Details,
 * Order Details resources) — not exhaustively verified against a live
 * account, so treat as high-confidence rather than guaranteed.
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
     * @param  array  $sallaPayload  Raw payload as received from Salla.
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
                        'salla_raw_payload' => $sallaPayload,
                        // Salla's Product Details resource returns `categories`
                        // as an array (a product can carry multiple Salla
                        // categories); we keep the first as the raw reference
                        // shown alongside EQUIPER's own corrected Category (F4).
                        'salla_category_name' => $sallaPayload['categories'][0]['name'] ?? null,
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
     * @param  array  $sallaPayload  Raw payload as received from Salla.
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
                        // Order Details' `status` is an object {name,color,slug},
                        // not a plain string — we key our own vocabulary off the slug.
                        'status' => $this->mapOrderStatus($sallaPayload['status']['slug'] ?? 'unknown'),
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
        // Salla's order status slugs are merchant-customizable (Salla
        // supports creating custom sub-statuses), so this maps the
        // confirmed default vocabulary and falls back to 'placed' for
        // any custom/unrecognized slug rather than throwing.
        return match ($sallaStatus) {
            'completed', 'delivered' => 'completed',
            'under_review', 'processing', 'confirmed' => 'confirmed',
            'delivering', 'shipped' => 'fulfilled',
            'cancelled' => 'cancelled',
            'restoring', 'returned', 'refunded' => 'returned',
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
