<?php

namespace Tests\Unit\Models;

use App\Models\Approval;
use App\Models\ContentAsset;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    public function test_requesting_approval_creates_pending_approval_and_linked_task(): void
    {
        $organization = Organization::factory()->create();
        $role = Role::factory()->create(['organization_id' => $organization->id, 'key' => 'marketing_manager']);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);

        $approval = Approval::requestFor($asset, 'marketing_manager');

        $this->assertSame('pending', $approval->status);
        $this->assertSame($role->id, $approval->requested_for_role);
        $this->assertDatabaseHas('tasks', ['related_approval_id' => $approval->id]);
    }

    public function test_approving_sets_status_and_propagates_to_content_asset_and_completes_task(): void
    {
        $organization = Organization::factory()->create();
        $decider = User::factory()->create(['organization_id' => $organization->id]);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);

        $approval = Approval::requestFor($asset, 'marketing_manager');
        $task = Task::query()->where('related_approval_id', $approval->id)->firstOrFail();

        $approval->approve($decider);

        $approval->refresh();
        $asset->refresh();
        $task->refresh();

        $this->assertSame('approved', $approval->status);
        $this->assertSame($decider->id, $approval->decided_by);
        $this->assertSame('approved', $asset->status);
        $this->assertSame('completed', $task->status);
    }

    public function test_rejecting_requires_reason_and_records_it(): void
    {
        $organization = Organization::factory()->create();
        $decider = User::factory()->create(['organization_id' => $organization->id]);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);

        $approval = Approval::requestFor($asset, 'marketing_manager');
        $approval->reject($decider, 'Off-brand tone.');

        $approval->refresh();

        $this->assertSame('rejected', $approval->status);
        $this->assertSame('Off-brand tone.', $approval->rejection_reason);
    }
}
