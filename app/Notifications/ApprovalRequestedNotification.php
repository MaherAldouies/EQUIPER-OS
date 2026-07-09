<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Approval $approval,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $entityType = class_basename($this->approval->approvable_type);

        return (new MailMessage)
            ->subject('EQUIPER OS — محتوى بانتظار مراجعتك')
            ->greeting("مرحبًا {$notifiable->name}،")
            ->line("يوجد {$entityType} جديد بانتظار مراجعتك واعتمادك.")
            ->action('مراجعة الآن', url('/approvals'))
            ->line('تنتهي صلاحية هذا الطلب خلال 72 ساعة إن لم تتم مراجعته.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'approval_id' => $this->approval->id,
            'approvable_type' => $this->approval->approvable_type,
            'approvable_id' => $this->approval->approvable_id,
        ];
    }
}
