<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallenge extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.superadmin.pages.two-factor-challenge';

    protected static string $layout = 'filament-panels::components.layout.base';

    public ?string $code = '';

    public function mount(): void
    {
        if (! Auth::guard('super_admin')->check()) {
            redirect()->route('filament.superadmin.auth.login');
        }

        if (session()->get('super_admin_2fa_verified')) {
            redirect()->route('filament.superadmin.pages.dashboard');
        }
    }

    public function verify(): void
    {
        $this->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $user = Auth::guard('super_admin')->user();
        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($user->two_factor_secret, $this->code)) {
            session(['super_admin_2fa_verified' => true]);
            redirect()->route('filament.superadmin.pages.dashboard');
        } else {
            $this->addError('code', 'Invalid 2FA code.');
        }
    }
}
