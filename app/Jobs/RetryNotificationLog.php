<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Jobs\Concerns\LogsJobFailures;
use App\Services\Msg91Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\TenantAware;

class RetryNotificationLog implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, LogsJobFailures, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $notificationLogId) {}

    public function uniqueId(): string
    {
        return 'retry_notification_log:'.$this->notificationLogId;
    }

    public function handle(Msg91Service $msg91): void
    {
        $log = NotificationLog::query()->with('member')->find($this->notificationLogId);
        if (! $log || ! $log->member) {
            return;
        }

        if ($log->status !== 'failed' && $log->status !== 'pending') {
            return;
        }

        $context = [];
        if (filled($log->message_preview)) {
            $decoded = json_decode((string) $log->message_preview, true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        $ok = false;

        if ($log->channel === 'whatsapp') {
            $variables = is_array($context) ? array_values($context) : [];
            $ok = $msg91->sendWhatsApp($log->member, (string) $log->template_name, $variables);
        } elseif ($log->channel === 'sms') {
            $message = is_string($context['message'] ?? null) ? (string) $context['message'] : '';
            if (filled($message)) {
                $ok = $msg91->sendSms($log->member, $message);
            }
        }

        if (! $ok) {
            $this->release(3600);
        }
    }
}
