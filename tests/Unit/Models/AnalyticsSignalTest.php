<?php

namespace Tests\Unit\Models;

use App\Models\AnalyticsSignal;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSignalTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_confidence_signal_is_not_reliable(): void
    {
        $signal = AnalyticsSignal::query()->create([
            'organization_id' => Organization::factory()->create()->id,
            'metric_key' => 'organic_clicks',
            'value' => 0,
            'unit' => 'count',
            'source' => 'google_search_console',
            'confidence' => 'low',
            'signal_date' => now()->toDateString(),
        ]);

        $this->assertFalse($signal->isReliable());
    }

    public function test_normal_confidence_signal_is_reliable(): void
    {
        $signal = AnalyticsSignal::query()->create([
            'organization_id' => Organization::factory()->create()->id,
            'metric_key' => 'daily_revenue',
            'value' => 1000,
            'unit' => 'SAR',
            'source' => 'salla',
            'confidence' => 'normal',
            'signal_date' => now()->toDateString(),
        ]);

        $this->assertTrue($signal->isReliable());
    }
}
