<?php

namespace App\Livewire\Inbox;

use App\Models\SocialMessage;
use App\Services\Social\MetaMessagingService;
use App\Services\Social\WhatsAppService;
use App\Services\Social\XApiClient;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Inbox\Index — Social Media Hub epic, unified reply inbox. Replies
 * are always typed and sent by the logged-in human here — there is no
 * AI drafting or auto-send in this component, a deliberate product
 * decision distinct from the AI-drafts/human-approves Content pipeline.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public ?string $activeConversationId = null;

    public string $reply = '';

    public function openConversation(string $conversationId): void
    {
        $this->activeConversationId = $conversationId;
        $this->reply = '';
    }

    public function send(WhatsAppService $whatsApp, MetaMessagingService $meta): void
    {
        Gate::authorize('social.manage');

        $this->validate(['reply' => ['required', 'string', 'max:4096']]);

        $conversation = SocialMessage::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->where('external_conversation_id', $this->activeConversationId)
            ->latest('received_at')
            ->firstOrFail();

        $externalMessageId = match (true) {
            $conversation->provider === 'whatsapp' => $whatsApp->sendMessage($conversation->organization_id, $conversation->external_conversation_id, $this->reply),
            $conversation->provider === 'x' => (new XApiClient($conversation->organization_id))
                ->postTweet($this->reply, inReplyToTweetId: $conversation->external_message_id),
            in_array($conversation->provider, ['meta_instagram', 'meta_facebook'], true) && $conversation->message_type === 'comment'
                => $meta->replyToComment($conversation->organization_id, $conversation->external_message_id, $this->reply),
            in_array($conversation->provider, ['meta_instagram', 'meta_facebook'], true)
                => $meta->replyToDm($conversation->organization_id, $conversation->external_conversation_id, $this->reply),
            default => throw new \RuntimeException("Replying via provider '{$conversation->provider}' is not yet supported."),
        };

        $outbound = SocialMessage::query()->create([
            'organization_id' => $conversation->organization_id,
            'provider' => $conversation->provider,
            'message_type' => $conversation->message_type,
            'external_conversation_id' => $conversation->external_conversation_id,
            'external_message_id' => $externalMessageId,
            'direction' => 'outbound',
            'body' => $this->reply,
            'status' => 'replied',
            'received_at' => now(),
        ]);
        $outbound->markReplied(auth()->user());

        // Mark the inbound thread as replied too so it drops out of "unread".
        SocialMessage::query()
            ->where('organization_id', $conversation->organization_id)
            ->where('external_conversation_id', $conversation->external_conversation_id)
            ->where('direction', 'inbound')
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        $this->reply = '';
    }

    public function render()
    {
        $organizationId = auth()->user()->organization_id;

        $conversations = SocialMessage::query()
            ->where('organization_id', $organizationId)
            ->selectRaw('DISTINCT ON (external_conversation_id) *')
            ->orderBy('external_conversation_id')
            ->orderByDesc('received_at')
            ->get()
            ->sortByDesc('received_at');

        $thread = $this->activeConversationId
            ? SocialMessage::query()
                ->where('organization_id', $organizationId)
                ->where('external_conversation_id', $this->activeConversationId)
                ->orderBy('received_at')
                ->get()
            : collect();

        return view('livewire.inbox.index', [
            'conversations' => $conversations,
            'thread' => $thread,
        ]);
    }
}
