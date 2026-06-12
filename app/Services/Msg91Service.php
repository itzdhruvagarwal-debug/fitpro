<?php

namespace App\Services;

use App\Helpers\Helpers;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Support\AppConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service
{
    protected string $authKey;

    protected string $baseUrl = 'https://api.msg91.com/api/v5/';

    protected string $waBaseUrl = 'https://api.msg91.com/api/whatsapp/whatsapp-outbound-message/';

    public function __construct()
    {
        $this->authKey = (string) config('msg91.auth_key', '');
    }

    public function sendWhatsApp(string|Member $to, string $templateKey, array $variables = []): bool
    {
        $member = null;
        if ($to instanceof Member) {
            $member = $to;
            $phone = $this->normalizePhone($member->notification_phone ?? $member->contact);
        } else {
            $phone = $this->normalizePhone($to);
        }

        $templateId = (string) config("msg91.templates.{$templateKey}", '');
        $settings = Helpers::getSettings();
        $integratedNumber = (string) data_get($settings, 'notifications.whatsapp.integrated_number', (string) config('msg91.whatsapp_number', ''));

        if (! filled($templateId) || ! $phone) {
            $this->log($member, 'whatsapp', $templateKey, $phone, 'failed', 'Missing template ID or phone', [
                'template_id' => $templateId ?: null,
            ], $variables);

            return false;
        }

        if ($this->isQuietHours()) {
            $this->log($member, 'whatsapp', $templateKey, $phone, 'pending', 'Quiet hours - will retry', null, $variables);

            return false;
        }

        $payload = [
            'integrated_number' => $integratedNumber,
            'content_type' => 'template',
            'payload' => [
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    // NOTE: MSG91 uses template ID, while our key must match approved template name.
                    // We store the template ID in config and still keep template_name for our own logs.
                    'name' => $templateId,
                    'language' => ['code' => 'en'],
                    'components' => $this->buildComponents($variables),
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post($this->waBaseUrl, $payload);

            $success = $response->successful();
            $this->log(
                $member,
                'whatsapp',
                $templateKey,
                $phone,
                $success ? 'sent' : 'failed',
                $success ? null : $response->body(),
                $response->json(),
                $variables,
            );

            return $success;
        } catch (\Throwable $e) {
            Log::error('MSG91 WhatsApp error: '.$e->getMessage());
            $this->log($member, 'whatsapp', $templateKey, $phone, 'failed', $e->getMessage(), null, $variables);

            return false;
        }
    }

    public function sendSms(Member $member, string $message): bool
    {
        $phone = $this->normalizePhone($member->notification_phone ?? $member->contact);
        $settings = Helpers::getSettings();
        $senderId = (string) data_get($settings, 'notifications.sms.sender_id', (string) config('msg91.sender_id', 'GYMSTH'));

        if (! $phone) {
            $this->log($member, 'sms', 'sms', null, 'failed', 'Missing phone', null, ['message' => $message]);

            return false;
        }

        if ($this->isQuietHours()) {
            $this->log($member, 'sms', 'sms', $phone, 'pending', 'Quiet hours - will retry', null, ['message' => $message]);

            return false;
        }

        try {
            $response = Http::withHeaders(['authkey' => $this->authKey])
                ->post($this->baseUrl.'flow/', [
                    'sender' => $senderId,
                    'route' => '4',
                    'country' => '91',
                    'sms' => [[
                        'message' => $message,
                        'to' => [$phone],
                    ]],
                ]);

            $success = $response->successful();
            $this->log(
                $member,
                'sms',
                'sms',
                $phone,
                $success ? 'sent' : 'failed',
                $success ? null : $response->body(),
                $response->json(),
                ['message' => $message],
            );

            return $success;
        } catch (\Throwable $e) {
            Log::error('MSG91 SMS error: '.$e->getMessage());
            $this->log($member, 'sms', 'sms', $phone, 'failed', $e->getMessage(), null, ['message' => $message]);

            return false;
        }
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        $digits = is_string($digits) ? $digits : '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return $digits;
        }

        return null;
    }

    protected function buildComponents(array $variables): array
    {
        if (empty($variables)) {
            return [];
        }

        return [[
            'type' => 'body',
            'parameters' => array_map(
                static fn (mixed $v): array => ['type' => 'text', 'text' => (string) $v],
                $variables,
            ),
        ]];
    }

    protected function isQuietHours(): bool
    {
        $settings = Helpers::getSettings();
        $start = (string) data_get($settings, 'notifications.whatsapp.quiet_hours_start', '22:00');
        $end = (string) data_get($settings, 'notifications.whatsapp.quiet_hours_end', '08:00');

        if (! filled($start) || ! filled($end)) {
            return false;
        }

        $now = now(AppConfig::timezone())->format('H:i');

        if ($start > $end) {
            return $now >= $start || $now < $end;
        }

        return $now >= $start && $now < $end;
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @param  array<int|string, mixed>  $context
     */
    protected function log(
        ?Member $member,
        string $channel,
        string $template,
        ?string $phone,
        string $status,
        ?string $error = null,
        ?array $response = null,
        array $context = [],
    ): void {
        $preview = null;
        if (! empty($context)) {
            $preview = mb_substr(json_encode($context, JSON_UNESCAPED_UNICODE) ?: '', 0, 200);
        }

        NotificationLog::create([
            'member_id' => $member?->id,
            'channel' => $channel,
            'template_name' => $template,
            'phone' => $phone,
            'message_preview' => $preview,
            'status' => $status,
            'error_message' => $error,
            'msg91_response' => $response,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
