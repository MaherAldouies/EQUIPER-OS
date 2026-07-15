<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\Organization;
use App\Models\SocialMessage;
use App\Services\Social\XApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PollXMentions — Social Media Hub epic, Phase 5. X has no free
 * real-time webhook push at the pay-per-use tier, so mentions are
 * polled on a schedule (config: equiperos.x.mentions_poll_interval_minutes,
 * default 30 min — every run costs real money, do not shorten this
 * without accepting the cost impact) rather than delivered instantly
 * like WhatsApp/Meta's webhooks.
 */
class PollXMentions extends Command
{
    protected $signature = 'x:poll-mentions';

    protected $description = 'Poll X for new mentions and record them into the unified reply inbox (Social Media Hub, Phase 5).';

    public function handle(): int
    {
        $organization = Organization::query()->first();

        if (! $organization) {
            $this->warn('No Organization found — skipping.');

            return self::SUCCESS;
        }

        $userId = Integration::config($organization->id, 'x', 'user_id');

        if (! $userId) {
            $this->error('X user_id is not configured — set it up on the Integrations settings page.');

            return self::FAILURE;
        }

        $sinceId = SocialMessage::query()
            ->where('organization_id', $organization->id)
            ->where('provider', 'x')
            ->where('direction', 'inbound')
            ->orderByDesc('received_at')
            ->value('external_message_id');

        try {
            $mentions = (new XApiClient($organization->id))->mentions($userId, $sinceId);

            foreach ($mentions as $mention) {
                SocialMessage::recordInbound([
                    'organization_id' => $organization->id,
                    'provider' => 'x',
                    'external_conversation_id' => $mention['id'],
                    'external_message_id' => $mention['id'],
                    'from_name' => $mention['author_id'] ?? null,
                    'body' => $mention['text'],
                    'received_at' => $mention['created_at'] ?? now(),
                ]);
            }

            $this->info(count($mentions).' new X mention(s) recorded.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('X mentions poll failed', ['error' => $e->getMessage()]);
            $this->error("X mentions poll failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
