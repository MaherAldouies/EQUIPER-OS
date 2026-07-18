<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Inbox\Index;
use App\Models\Integration;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SocialMessage;
use App\Models\User;
use Database\Seeders\EventCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class InboxIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EventCatalogSeeder::class);
    }

    private function connectWhatsapp(string $organizationId): void
    {
        $integration = Integration::query()->create([
            'organization_id' => $organizationId,
            'provider' => 'whatsapp',
            'status' => 'connected',
            'settings' => ['phone_number_id' => '123'],
        ]);
        $integration->credential()->create(['access_token' => 'token']);
    }

    private function connectMeta(string $organizationId): void
    {
        $integration = Integration::query()->create([
            'organization_id' => $organizationId,
            'provider' => 'meta',
            'status' => 'connected',
        ]);
        $integration->credential()->create(['access_token' => 'meta-token']);
    }

    private function socialUser(Organization $organization): User
    {
        $permission = Permission::query()->firstOrCreate(['key' => 'social.manage']);
        $role = Role::factory()->create(['organization_id' => $organization->id]);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_lists_conversations_for_the_users_organization_only(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->socialUser($organization);

        SocialMessage::factory()->create([
            'organization_id' => $organization->id,
            'from_name' => 'My Customer',
        ]);
        SocialMessage::factory()->create(['from_name' => 'Someone Elses Customer']); // different org

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('My Customer')
            ->assertDontSee('Someone Elses Customer');
    }

    public function test_sending_a_reply_calls_whatsapp_and_records_outbound_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OUT123']]], 200),
        ]);

        $organization = Organization::factory()->create();
        $this->connectWhatsapp($organization->id);
        $user = $this->socialUser($organization);
        $inbound = SocialMessage::factory()->create([
            'organization_id' => $organization->id,
            'external_conversation_id' => '16505551234',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openConversation', '16505551234')
            ->set('reply', 'Yes, it comes in black too!')
            ->call('send');

        $this->assertDatabaseHas('social_messages', [
            'organization_id' => $organization->id,
            'external_conversation_id' => '16505551234',
            'direction' => 'outbound',
            'body' => 'Yes, it comes in black too!',
            'external_message_id' => 'wamid.OUT123',
        ]);
        $this->assertSame('read', $inbound->fresh()->status);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/123/messages')
                && $request['to'] === '16505551234'
                && $request['text']['body'] === 'Yes, it comes in black too!';
        });
    }

    public function test_user_without_permission_cannot_send_reply(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        SocialMessage::factory()->create([
            'organization_id' => $organization->id,
            'external_conversation_id' => '16505551234',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openConversation', '16505551234')
            ->set('reply', 'Hello')
            ->call('send')
            ->assertForbidden();
    }

    public function test_replying_to_a_meta_comment_uses_the_comment_reply_endpoint(): void
    {
        Http::fake([
            '*/COMMENT1/replies' => Http::response(['id' => 'REPLY1'], 200),
        ]);

        $organization = Organization::factory()->create();
        $this->connectMeta($organization->id);
        $user = $this->socialUser($organization);
        SocialMessage::factory()->create([
            'organization_id' => $organization->id,
            'provider' => 'meta_instagram',
            'message_type' => 'comment',
            'external_conversation_id' => 'COMMENT1',
            'external_message_id' => 'COMMENT1',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openConversation', 'COMMENT1')
            ->set('reply', 'شكرًا لتواصلك!')
            ->call('send');

        $this->assertDatabaseHas('social_messages', [
            'provider' => 'meta_instagram',
            'direction' => 'outbound',
            'body' => 'شكرًا لتواصلك!',
            'external_message_id' => 'REPLY1',
        ]);
    }

    public function test_replying_to_an_x_mention_posts_a_reply_tweet(): void
    {
        config(['equiperos.x.api_base_url' => 'https://api.x.com/2']);

        $organization = Organization::factory()->create();
        $integration = \App\Models\Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'x',
            'status' => 'connected',
        ]);
        $integration->credential()->create(['access_token' => 'x-token', 'expires_at' => now()->addHour()]);

        Http::fake([
            '*/tweets' => Http::response(['data' => ['id' => 'REPLYTWEET1']], 200),
        ]);

        $user = $this->socialUser($organization);
        SocialMessage::factory()->create([
            'organization_id' => $organization->id,
            'provider' => 'x',
            'external_conversation_id' => 'T1',
            'external_message_id' => 'T1',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openConversation', 'T1')
            ->set('reply', 'Yes it is in stock!')
            ->call('send');

        $this->assertDatabaseHas('social_messages', [
            'provider' => 'x',
            'direction' => 'outbound',
            'body' => 'Yes it is in stock!',
            'external_message_id' => 'REPLYTWEET1',
        ]);

        Http::assertSent(fn ($request) => $request['reply']['in_reply_to_tweet_id'] === 'T1');
    }
}
