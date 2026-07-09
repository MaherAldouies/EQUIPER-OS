<?php

namespace App\Listeners;

use App\Events\IntegrationWentDegraded;
use App\Models\User;
use App\Notifications\IntegrationDegradedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyOwnerOfIntegrationDegradation implements ShouldQueue
{
    public function handle(IntegrationWentDegraded $event): void
    {
        $owners = User::query()
            ->where('organization_id', $event->integration->organization_id)
            ->whereHas('roles', fn ($q) => $q->where('key', 'owner'))
            ->where('status', 'active')
            ->get();

        if ($owners->isEmpty()) {
            return;
        }

        Notification::send($owners, new IntegrationDegradedNotification($event->integration, $event->error));
    }
}
