<?php

namespace App\Listeners;

use App\Events\ApprovalWasRequested;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendApprovalRequestedNotification implements ShouldQueue
{
    public function handle(ApprovalWasRequested $event): void
    {
        $approval = $event->approval;

        if (! $approval->requested_for_role) {
            return;
        }

        $recipients = User::query()
            ->whereHas('roles', fn ($q) => $q->where('roles.id', $approval->requested_for_role))
            ->where('status', 'active')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ApprovalRequestedNotification($approval));
    }
}
