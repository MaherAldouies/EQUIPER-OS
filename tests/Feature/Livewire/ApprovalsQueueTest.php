<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Approvals\Queue;
use App\Models\Approval;
use App\Models\ContentAsset;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalsQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    private function approverUser(Organization $organization): User
    {
        $permission = Permission::query()->firstOrCreate(['key' => 'content.approve']);
        $role = Role::factory()->create(['organization_id' => $organization->id]);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_approve_action_approves_and_removes_from_pending_list(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->approverUser($organization);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);
        $approval = Approval::requestFor($asset, 'marketing_manager');

        Livewire::actingAs($user)
            ->test(Queue::class)
            ->call('approve', $approval->id);

        $this->assertSame('approved', $approval->fresh()->status);
    }

    public function test_reject_flow_requires_reason(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->approverUser($organization);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);
        $approval = Approval::requestFor($asset, 'marketing_manager');

        Livewire::actingAs($user)
            ->test(Queue::class)
            ->call('startReject', $approval->id)
            ->call('reject', $approval->id)
            ->assertHasErrors(['reason' => 'required']);

        $this->assertSame('pending', $approval->fresh()->status);
    }

    public function test_reject_with_reason_succeeds(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->approverUser($organization);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);
        $approval = Approval::requestFor($asset, 'marketing_manager');

        Livewire::actingAs($user)
            ->test(Queue::class)
            ->call('startReject', $approval->id)
            ->set('reason', 'Not on brand.')
            ->call('reject', $approval->id);

        $approval->refresh();
        $this->assertSame('rejected', $approval->status);
        $this->assertSame('Not on brand.', $approval->rejection_reason);
    }

    public function test_user_without_approve_permission_cannot_approve(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $asset = ContentAsset::factory()->create(['organization_id' => $organization->id, 'status' => 'generated']);
        $approval = Approval::requestFor($asset, 'marketing_manager');

        Livewire::actingAs($user)
            ->test(Queue::class)
            ->call('approve', $approval->id)
            ->assertForbidden();

        $this->assertSame('pending', $approval->fresh()->status);
    }
}
