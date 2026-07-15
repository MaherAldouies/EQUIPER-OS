<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Order;
use App\Models\Organization;
use App\Services\Analytics\DashboardAggregationService;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_revenue_signal_sums_non_cancelled_non_returned_orders_for_the_day(): void
    {
        $organization = Organization::factory()->create();
        $today = now();

        Order::query()->create([
            'organization_id' => $organization->id,
            'salla_order_id' => '1',
            'status' => 'completed',
            'total_amount' => 100,
            'currency' => 'SAR',
            'placed_at' => $today,
            'last_synced_at' => $today,
        ]);
        Order::query()->create([
            'organization_id' => $organization->id,
            'salla_order_id' => '2',
            'status' => 'completed',
            'total_amount' => 250.50,
            'currency' => 'SAR',
            'placed_at' => $today,
            'last_synced_at' => $today,
        ]);
        Order::query()->create([
            'organization_id' => $organization->id,
            'salla_order_id' => '3',
            'status' => 'cancelled',
            'total_amount' => 9999,
            'currency' => 'SAR',
            'placed_at' => $today,
            'last_synced_at' => $today,
        ]);

        $signal = (new DashboardAggregationService($organization->id))->refreshRevenueSignal($today);

        $this->assertSame('daily_revenue', $signal->metric_key);
        $this->assertEquals(350.50, (float) $signal->value);
        $this->assertSame('normal', $signal->confidence);

        $orderCountSignal = \App\Models\AnalyticsSignal::query()
            ->where('organization_id', $organization->id)
            ->where('metric_key', 'daily_order_count')
            ->first();
        $this->assertEquals(2, (int) $orderCountSignal->value);
    }

    public function test_content_pipeline_signal_counts_drafted_content(): void
    {
        $organization = Organization::factory()->create();
        Content::factory()->count(3)->create(['organization_id' => $organization->id, 'status' => 'drafted']);
        Content::factory()->create(['organization_id' => $organization->id, 'status' => 'approved']);

        $signal = (new DashboardAggregationService($organization->id))->refreshContentPipelineSignal(now());

        $this->assertSame('content_pipeline_pending_count', $signal->metric_key);
        $this->assertEquals(3, (int) $signal->value);
    }

    public function test_refreshing_the_same_day_upserts_rather_than_duplicates(): void
    {
        $organization = Organization::factory()->create();
        $service = new DashboardAggregationService($organization->id);

        $service->refreshRevenueSignal(now());
        $service->refreshRevenueSignal(now());

        $this->assertSame(
            1,
            \App\Models\AnalyticsSignal::query()
                ->where('organization_id', $organization->id)
                ->where('metric_key', 'daily_revenue')
                ->count()
        );
    }
}
