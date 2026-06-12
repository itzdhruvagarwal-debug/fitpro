<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FAQRCode\Google2FA;
use Filament\Notifications\Notification;

class TwoFactorSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.superadmin.pages.two-factor-settings';

    protected static ?string $title = 'Two-Factor Authentication';

    protected static ?string $navigationLabel = 'Two-Factor Auth';

    public bool $isEnabled = false;
    public ?string $secretKey = '';
    public ?string $qrCodeUri = '';
    public ?string $code = '';

    public function mount(): void
    {
        $user = Auth::guard('super_admin')->user();
        $this->isEnabled = ! empty($user->two_factor_secret);

        if (! $this->isEnabled) {
            $google2fa = new Google2FA();
            $this->secretKey = $google2fa->generateSecretKey();
            $this->qrCodeUri = $google2fa->getQRCodeInline(
                config('app.name', 'GymSaathi'),
                $user->email,
                $this->secretKey
            );
        }
    }

    public function enable(): void
    {
        $this->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $google2fa = new Google2FA();
        if ($google2fa->verifyKey($this->secretKey, $this->code)) {
            $user = Auth::guard('super_admin')->user();
            $user->update([
                'two_factor_secret' => $this->secretKey,
            ]);

            $this->isEnabled = true;
            session(['super_admin_2fa_verified' => true]);

            Notification::make()
                ->title('Two-Factor Authentication Enabled')
                ->success()
                ->send();
        } else {
            $this->addError('code', 'Invalid 2FA code.');
        }
    }

    public function disable(): void
    {
        abort(403, 'Two-factor authentication is mandatory and cannot be disabled.');
    }
}
