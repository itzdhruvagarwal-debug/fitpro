<?php

namespace App\Services;

use App\Contracts\SettingsRepository;

/**
 * JSON-backed settings repository (OSS default).
 *
 * Settings are stored per tenant under `storage/data/gym_{id}_settings.json`.
 * Non-tenant contexts use `storage/data/settingsData.json`.
 * Other installations can override this binding to store settings elsewhere.
 */
class JsonSettingsRepository implements SettingsRepository
{
    private const GLOBAL_SETTINGS_PATH = 'data/settingsData.json';

    private const EXAMPLE_SETTINGS_PATH = 'data/settingsData.json.example';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $cachedSettings = [];

    /**
     * @var array<string, mixed>|null
     */
    protected static ?array $testOverride = null;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $testSettings = [];

    /**
     * @param  array<string, mixed>|null  $override
     */
    public function setTestOverride(?array $override): void
    {
        static::$testOverride = $override;
        $this->cachedSettings = [];
    }

    public function get(): array
    {
        if (static::$testOverride !== null) {
            return $this->normalize(static::$testOverride);
        }

        $cacheKey = $this->settingsPath();

        if (isset($this->cachedSettings[$cacheKey])) {
            return $this->cachedSettings[$cacheKey];
        }

        if (app()->runningUnitTests()) {
            if (isset(static::$testSettings[$cacheKey])) {
                return $this->cachedSettings[$cacheKey] = $this->normalize(static::$testSettings[$cacheKey]);
            }

            $exampleFilePath = storage_path(self::EXAMPLE_SETTINGS_PATH);

            if (file_exists($exampleFilePath)) {
                $settings = json_decode((string) file_get_contents($exampleFilePath), true) ?? [];
                $settings = is_array($settings) ? $settings : [];

                return $this->cachedSettings[$cacheKey] = $this->normalize($settings);
            }

            return $this->cachedSettings[$cacheKey] = $this->normalize([]);
        }

        $filePath = storage_path($cacheKey);

        if (! file_exists($filePath)) {
            $this->initializeFile($filePath);
        }

        $settings = json_decode((string) file_get_contents($filePath), true) ?? [];
        $settings = is_array($settings) ? $settings : [];

        return $this->cachedSettings[$cacheKey] = $this->normalize($settings);
    }

    public function put(array $settings): void
    {
        $normalized = $this->normalize($settings);
        $cacheKey = $this->settingsPath();

        if (app()->runningUnitTests()) {
            static::$testSettings[$cacheKey] = $normalized;
            $this->cachedSettings[$cacheKey] = $normalized;

            return;
        }

        $filePath = storage_path($cacheKey);

        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents(
            $filePath,
            json_encode($normalized, JSON_PRETTY_PRINT),
            LOCK_EX,
        );

        $this->cachedSettings[$cacheKey] = $normalized;
    }

    /**
     * Atomically read, mutate, and write settings for the active tenant.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutator
     * @return array<string, mixed>
     */
    public function updateWithLock(callable $mutator): array
    {
        $cacheKey = $this->settingsPath();

        if (app()->runningUnitTests()) {
            $current = static::$testSettings[$cacheKey] ?? $this->get();
            $updated = $this->normalize($mutator($this->normalize($current)));
            static::$testSettings[$cacheKey] = $updated;
            $this->cachedSettings[$cacheKey] = $updated;

            return $updated;
        }

        $filePath = storage_path($cacheKey);

        if (! file_exists($filePath)) {
            $this->initializeFile($filePath);
        }

        $handle = fopen($filePath, 'c+');
        if ($handle === false) {
            $updated = $this->normalize($mutator($this->get()));
            $this->put($updated);

            return $updated;
        }

        try {
            flock($handle, LOCK_EX);
            rewind($handle);
            $contents = stream_get_contents($handle);
            $settings = json_decode(is_string($contents) ? $contents : '', true) ?? [];
            $settings = is_array($settings) ? $settings : [];

            $updated = $this->normalize($mutator($this->normalize($settings)));

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($updated, JSON_PRETTY_PRINT) ?: '{}');
            fflush($handle);

            $this->cachedSettings[$cacheKey] = $updated;

            return $updated;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function initializeFile(string $filePath): void
    {
        $exampleFilePath = storage_path(self::EXAMPLE_SETTINGS_PATH);

        if (file_exists($exampleFilePath)) {
            copy($exampleFilePath, $filePath);

            return;
        }

        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, json_encode([
            'general' => [],
            'invoice' => [],
            'member' => [],
            'charges' => [],
            'expenses' => [],
            'subscriptions' => [],
            'payments' => [],
            'notifications' => [],
        ], JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalize(array $settings): array
    {
        foreach ([
            'general',
            'invoice',
            'member',
            'charges',
            'expenses',
            'subscriptions',
            'payments',
            'notifications',
        ] as $key) {
            if (! array_key_exists($key, $settings) || ! is_array($settings[$key])) {
                $settings[$key] = [];
            }
        }

        /** @var array<string, mixed> $general */
        $general = $settings['general'];
        if (
            ! array_key_exists('locale', $general) ||
            (! is_string($general['locale']) && $general['locale'] !== null)
        ) {
            $general['locale'] = null;
        }
        $settings['general'] = $general;

        /** @var array<string, mixed> $notifications */
        $notifications = $settings['notifications'];
        if (
            ! array_key_exists('email', $notifications) ||
            ! is_array($notifications['email'])
        ) {
            $notifications['email'] = [];
        }

        if (
            ! array_key_exists('whatsapp', $notifications) ||
            ! is_array($notifications['whatsapp'])
        ) {
            $notifications['whatsapp'] = [];
        }

        if (
            ! array_key_exists('sms', $notifications) ||
            ! is_array($notifications['sms'])
        ) {
            $notifications['sms'] = [];
        }
        $settings['notifications'] = $notifications;

        /** @var array<string, mixed> $emailSettings */
        $emailSettings = $settings['notifications']['email'];

        foreach ([
            'enabled' => false,
            'auto_send_invoice_issued' => false,
            'auto_send_payment_receipt' => false,
            'invoice_subject_template' => 'Invoice {invoice_number} - {status}',
            'receipt_subject_template' => 'Payment received - {invoice_number}',
        ] as $key => $default) {
            if (! array_key_exists($key, $emailSettings)) {
                $emailSettings[$key] = $default;
            }
        }
        $settings['notifications']['email'] = $emailSettings;

        /** @var array<string, mixed> $whatsappSettings */
        $whatsappSettings = $settings['notifications']['whatsapp'];
        foreach ([
            'enabled' => false,
            'send_welcome_message' => true,
            'send_payment_confirmation' => true,
            'send_payment_failed' => true,
            'send_expiry_warning' => true,
            'send_renewal_reminders' => true,
            'renewal_reminder_days' => [7, 3, 1],
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ] as $key => $default) {
            if (! array_key_exists($key, $whatsappSettings)) {
                $whatsappSettings[$key] = $default;
            }
        }
        $settings['notifications']['whatsapp'] = $whatsappSettings;

        /** @var array<string, mixed> $smsSettings */
        $smsSettings = $settings['notifications']['sms'];
        foreach ([
            'enabled' => false,
            'sender_id' => 'GYMSTH',
        ] as $key => $default) {
            if (! array_key_exists($key, $smsSettings)) {
                $smsSettings[$key] = $default;
            }
        }
        $settings['notifications']['sms'] = $smsSettings;

        /** @var array<string, mixed> $payments */
        $payments = $settings['payments'];
        if (
            ! array_key_exists('provider', $payments) ||
            ! is_string($payments['provider']) ||
            trim($payments['provider']) === ''
        ) {
            $payments['provider'] = 'razorpay';
        }
        $settings['payments'] = $payments;

        return $settings;
    }

    private function settingsPath(): string
    {
        $tenantId = app()->bound('currentTenant') && app('currentTenant')
            ? (int) app('currentTenant')->id
            : null;

        if ($tenantId === null || $tenantId <= 0) {
            return self::GLOBAL_SETTINGS_PATH;
        }

        return "data/gym_{$tenantId}_settings.json";
    }
}
