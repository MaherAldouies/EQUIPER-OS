<?php

namespace App\Notifications;

use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IntegrationDegradedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Integration $integration,
        public readonly string $error,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("EQUIPER OS — تنبيه: تكامل {$this->integration->provider} متعطل")
            ->greeting("مرحبًا {$notifiable->name}،")
            ->line("توقف الاتصال بـ {$this->integration->provider} عن العمل بشكل صحيح.")
            ->line("رسالة الخطأ: {$this->error}")
            ->line('البيانات المعروضة في لوحة التحكم قد تكون غير محدَّثة حتى إصلاح هذا الاتصال.')
            ->action('عرض حالة التكاملات', url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'integration_id' => $this->integration->id,
            'provider' => $this->integration->provider,
            'error' => $this->error,
        ];
    }
}
