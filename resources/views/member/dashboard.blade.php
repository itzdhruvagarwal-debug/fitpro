<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Member Dashboard - FitPro</title>
    <link rel="manifest" href="/manifest.json" />
    <link rel="icon" href="/pwa-icon.svg" type="image/svg+xml" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <meta name="theme-color" content="#f43f5e" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    
    <style>
        :root {
            --bg: #09090b;
            --card-bg: rgba(24, 24, 27, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
            --accent: #f43f5e;
            --accent-glow: rgba(244, 63, 94, 0.3);
            --accent-strong: #be123c;
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.2);
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            background: radial-gradient(circle at 50% -20%, rgba(244, 63, 94, 0.15) 0%, transparent 60%),
                        radial-gradient(circle at 10% 80%, rgba(190, 18, 60, 0.08) 0%, transparent 50%),
                        var(--bg);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            padding: 16px;
            padding-bottom: 80px; /* space for bottom navigation or mobile view */
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Top Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo svg {
            width: 28px;
            height: 28px;
            fill: var(--accent);
        }

        .brand-logo span {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid var(--card-border);
            padding: 8px 14px;
            border-radius: 10px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }

        /* Card styles */
        .card {
            background-color: var(--card-bg);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        /* Profile Card */
        .profile-card {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            object-fit: cover;
            border: 2px solid var(--accent);
            box-shadow: 0 0 15px var(--accent-glow);
            background: #27272a;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar svg {
            width: 40px;
            height: 40px;
            fill: var(--text-muted);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .profile-info h2 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
        }

        .profile-info .member-code {
            font-size: 13px;
            color: var(--accent);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gym-name {
            font-size: 14px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
        }

        .gym-name svg {
            width: 16px;
            height: 16px;
            fill: var(--text-muted);
        }

        /* Installation Banner */
        .install-banner {
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.15), rgba(190, 18, 60, 0.05));
            border: 1px dashed rgba(244, 63, 94, 0.3);
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-radius: 16px;
        }

        .install-text h3 {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
        }

        .install-text p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .btn-install {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 10px var(--accent-glow);
            transition: all 0.3s ease;
        }

        .btn-install:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px var(--accent-glow);
        }

        /* Attendance Check-in Card */
        .checkin-card {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            background: linear-gradient(180deg, var(--card-bg) 0%, rgba(24, 24, 27, 0.3) 100%);
        }

        .radar-box {
            position: relative;
            width: 80px;
            height: 80px;
            margin-bottom: 8px;
        }

        .radar-dot {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            display: grid;
            place-items: center;
            position: relative;
            z-index: 2;
        }

        .radar-dot svg {
            width: 36px;
            height: 36px;
            fill: var(--text-muted);
            transition: fill 0.3s ease;
        }

        .radar-box.checked-in .radar-dot svg {
            fill: var(--success);
        }

        .radar-pulse {
            position: absolute;
            top: 0;
            left: 0;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--accent-glow);
            z-index: 1;
            animation: pulse-ring 2s infinite cubic-bezier(0.215, 0.610, 0.355, 1);
            display: block;
        }

        .radar-box.checked-in .radar-pulse {
            background: var(--success-glow);
            animation: pulse-ring-success 2s infinite cubic-bezier(0.215, 0.610, 0.355, 1);
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { opacity: 0.5; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        @keyframes pulse-ring-success {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { opacity: 0.5; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        .checkin-status h3 {
            font-size: 18px;
            font-weight: 700;
        }

        .checkin-status p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .btn-checkin {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
            border: none;
            width: 100%;
            height: 48px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px var(--accent-glow);
            transition: all 0.3s ease;
        }

        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--accent-glow);
        }

        .btn-checkin:disabled {
            background: #27272a;
            color: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            border: 1px solid var(--card-border);
        }

        /* Subscription Card */
        .plan-card {
            border-left: 4px solid var(--accent);
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .plan-title h3 {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }

        .plan-title span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .plan-badge {
            background: rgba(244, 63, 94, 0.1);
            color: var(--accent);
            border: 1px solid rgba(244, 63, 94, 0.2);
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .plan-dates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            background: rgba(0, 0, 0, 0.2);
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            margin-bottom: 16px;
        }

        .date-box span {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .date-box strong {
            font-size: 14px;
            color: #fff;
            font-weight: 600;
        }

        .btn-renew {
            background: transparent;
            border: 1px solid var(--accent);
            color: var(--accent);
            width: 100%;
            height: 44px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-renew:hover {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 15px var(--accent-glow);
        }

        /* Quick Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 18px;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-card span {
            font-size: 12px;
            color: var(--text-muted);
        }

        .stat-card strong {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }

        /* Renew Modal & Form Layout */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-content {
            background: #18181b;
            border: 1px solid var(--card-border);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            gap: 20px;
            animation: slideUp 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
        }

        .btn-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
        }

        .plan-select-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .plan-option {
            background: #27272a;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .plan-option.selected {
            border-color: var(--accent);
            background: rgba(244, 63, 94, 0.05);
        }

        .plan-option-info h4 {
            font-size: 15px;
            font-weight: 600;
        }

        .plan-option-info p {
            font-size: 12px;
            color: var(--text-muted);
        }

        .plan-option-price {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        .payment-method-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            background: #09090b;
            padding: 4px;
            border-radius: 10px;
            border: 1px solid var(--card-border);
        }

        .method-btn {
            background: transparent;
            border: none;
            height: 38px;
            border-radius: 8px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .method-btn.active {
            background: var(--accent);
            color: #fff;
        }

        .btn-pay-confirm {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #fff;
            border: none;
            height: 48px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px var(--accent-glow);
            width: 100%;
        }

        /* Invoices Card */
        .invoice-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--card-border);
        }

        .invoice-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .invoice-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .invoice-info p {
            font-size: 11px;
            color: var(--text-muted);
        }

        .invoice-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .invoice-amount {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
        }

        .btn-download {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .btn-download:hover {
            color: #fff;
            background: var(--accent);
            border-color: var(--accent);
        }

        .btn-download svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        /* iOS Installation Instruction Sheet */
        .ios-prompt {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #18181b;
            border-top: 1px solid var(--card-border);
            border-radius: 24px 24px 0 0;
            z-index: 101;
            padding: 24px;
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.5);
            display: none;
            flex-direction: column;
            gap: 16px;
            animation: slideUpBottom 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .ios-prompt h3 {
            font-size: 17px;
            font-weight: 700;
            text-align: center;
        }

        .ios-step {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: var(--text-main);
        }

        .ios-step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 12px;
            font-weight: 700;
        }

        .ios-icon {
            display: inline-block;
            vertical-align: middle;
        }

        .btn-ios-close {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--card-border);
            color: #fff;
            height: 44px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 8px;
        }

        /* Toast notifications */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(24, 24, 27, 0.9);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 12px 24px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            gap: 8px;
            animation: slideDown 0.3s ease;
        }

        .toast.success {
            border-color: var(--success);
            color: var(--success);
        }

        .toast.danger {
            border-color: var(--danger);
            color: #fca5a5;
        }

        @keyframes slideDown {
            from { top: -50px; opacity: 0; }
            to { top: 20px; opacity: 1; }
        }

        @keyframes slideUpBottom {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand-logo">
                <svg viewBox="0 0 24 24">
                    <path d="M5 9c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H5zm14 0c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1h-1zM9 11h6v2H9v-2zm-2-2h2v6H7V9zm8 0h2v6h-2V9z" />
                </svg>
                <span>FitPro</span>
            </div>
            
            <form method="POST" action="{{ route('member.logout') }}">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </header>

        <!-- Profile Card -->
        <div class="card profile-card">
            <div class="avatar">
                @if($member->photo)
                    <img src="{{ Storage::disk('public')->url($member->photo) }}" alt="{{ $member->name }}" style="width:100%; height:100%; object-fit:cover; border-radius:18px;" />
                @else
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                    </svg>
                @endif
            </div>
            <div class="profile-info">
                <span class="member-code">{{ $member->code }}</span>
                <h2>{{ $member->name }}</h2>
                <div class="gym-name">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    <span>{{ data_get($settings, 'general.gym_name', $gym->name) }}</span>
                </div>
            </div>
        </div>

        <!-- PWA Installation Banner -->
        <div class="install-banner" id="pwa-install-banner">
            <div class="install-text">
                <h3>FitPro Mobile App</h3>
                <p>Install this app on your phone for faster access.</p>
            </div>
            <button class="btn-install" id="btn-pwa-install">Install App</button>
        </div>

        <!-- Attendance Checkin -->
        <div class="card checkin-card">
            <div class="radar-box {{ $todayAttendance ? 'checked-in' : '' }}" id="radar-box">
                <div class="radar-pulse"></div>
                <div class="radar-dot">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 18.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l1.9-1.9C9.22 18.58 10.57 19 12 19c4.97 0 9-4.03 9-9s-4.03-9-9-9zm0 15c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>
                        <circle cx="12" cy="11" r="3" />
                    </svg>
                </div>
            </div>

            <div class="checkin-status">
                <h3 id="checkin-status-text">
                    {{ $todayAttendance ? 'Checked In Today' : 'Ready to Check In' }}
                </h3>
                <p id="checkin-time-text">
                    {{ $todayAttendance ? 'Time: ' . $todayAttendance->checked_in_at->format('h:i A') : 'Tap button when you arrive at the gym' }}
                </p>
            </div>

            <button class="btn-checkin" id="btn-checkin" {{ $todayAttendance ? 'disabled' : '' }}>
                {{ $todayAttendance ? 'Attendance Marked' : 'Mark Attendance' }}
            </button>
        </div>

        <!-- Subscription / Plan Info -->
        <div class="card plan-card">
            <div class="plan-header">
                <div class="plan-title">
                    <span>Active Subscription</span>
                    <h3>{{ $activeSubscription ? $activeSubscription->plan?->name : 'No Active Plan' }}</h3>
                </div>
                <div class="plan-badge">
                    {{ $activeSubscription ? 'Ongoing' : 'Inactive' }}
                </div>
            </div>

            @if($activeSubscription)
                <div class="plan-dates">
                    <div class="date-box">
                        <span>Start Date</span>
                        <strong>{{ $activeSubscription->start_date?->format('d-m-Y') }}</strong>
                    </div>
                    <div class="date-box">
                        <span>Expiry Date</span>
                        <strong>{{ $activeSubscription->end_date?->format('d-m-Y') }}</strong>
                    </div>
                </div>
                
                @php
                    $daysLeft = now()->diffInDays($activeSubscription->end_date, false);
                    $daysLeft = $daysLeft < 0 ? 0 : $daysLeft;
                @endphp
                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                    Days Remaining: <strong style="color: var(--accent);">{{ $daysLeft }} Days</strong>
                </div>
            @endif

            <button class="btn-renew" id="btn-renew-trigger">Renew Plan / Autopay</button>
        </div>

        <!-- Profile Details Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span>Total Attendance</span>
                <strong>{{ $totalCheckins }} Days</strong>
            </div>
            <div class="stat-card">
                <span>Health Goal</span>
                <strong>{{ $member->goal ?: 'Fitness' }}</strong>
            </div>
            <div class="stat-card">
                <span>Emergency Contact</span>
                <strong style="font-size: 14px;">{{ $member->emergency_contact ?: 'Not Set' }}</strong>
            </div>
            <div class="stat-card">
                <span>Autopay Status</span>
                <strong style="font-size: 14px; color: {{ $member->isAutoPayActive() ? 'var(--success)' : 'var(--text-muted)' }}">
                    {{ $member->isAutoPayActive() ? 'Active' : 'Disabled' }}
                </strong>
            </div>
        </div>

        <!-- Invoices List -->
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Invoices & Billing History</h3>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                @forelse($invoices as $invoice)
                    <div class="invoice-item">
                        <div class="invoice-info">
                            <h4>{{ $invoice->invoice_number ?: $invoice->number }}</h4>
                            <p>{{ $invoice->date?->format('d-m-Y') }} • {{ ucfirst($invoice->payment_method ?: 'online') }}</p>
                        </div>
                        <div class="invoice-actions">
                            <span class="invoice-amount">₹{{ number_format($invoice->total_amount, 2) }}</span>
                            <a href="{{ route('member.invoice.download', $invoice) }}" class="btn-download" title="Download Bill">
                                <svg viewBox="0 0 24 24">
                                    <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <p style="font-size: 13px; color: var(--text-muted); text-align: center; padding: 12px 0;">No invoice history found.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Renew / Plan Select Modal -->
    <div class="modal" id="renew-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Plan & Renew</h3>
                <button class="btn-close" id="btn-modal-close">&times;</button>
            </div>
            
            <div class="plan-select-list">
                @foreach($plans as $index => $plan)
                    <div class="plan-option {{ $index === 0 ? 'selected' : '' }}" data-plan-id="{{ $plan->id }}" data-amount="{{ $plan->amount }}">
                        <div class="plan-option-info">
                            <h4>{{ $plan->name }}</h4>
                            <p>{{ $plan->days }} Days Validity</p>
                        </div>
                        <div class="plan-option-price">₹{{ number_format($plan->amount, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <h4 style="font-size: 13px; font-weight: 600;">Renewal Type</h4>
            <div class="payment-method-toggle">
                <button class="method-btn active" id="btn-method-autopay">AutoPay (UPI Mandate)</button>
                <button class="method-btn" id="btn-method-onetime">One-Time Pay</button>
            </div>

            <button class="btn-pay-confirm" id="btn-pay-confirm">Proceed to Checkout</button>
        </div>
    </div>

    <!-- iOS Install Guide Popup -->
    <div class="ios-prompt" id="ios-install-guide">
        <h3>Install FitPro on iPhone</h3>
        
        <div class="ios-step">
            <div class="ios-step-num">1</div>
            <div>Tap the Share button in Safari browser toolbar <span class="ios-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent)" style="display:inline;vertical-align:middle;"><path d="M16 5l-1.42 1.42L18.16 10H8v2h10.16l-3.59 3.58L16 17l6-6-6-6zM4 19H2V3h2v16z"/></svg></span>.</div>
        </div>
        
        <div class="ios-step">
            <div class="ios-step-num">2</div>
            <div>Scroll down and select <strong>"Add to Home Screen"</strong>.</div>
        </div>

        <button class="btn-ios-close" id="btn-ios-close">Got It</button>
    </div>

    <!-- Custom Toast Notification -->
    <div class="toast" id="toast-notification">
        <span id="toast-message"></span>
    </div>

    <!-- Razorpay Scripts -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        // Global variables
        let deferredPrompt;
        const pwaInstallBanner = document.getElementById('pwa-install-banner');
        const btnPwaInstall = document.getElementById('btn-pwa-install');
        const iosInstallGuide = document.getElementById('ios-install-guide');
        const btnIosClose = document.getElementById('btn-ios-close');
        
        const btnCheckin = document.getElementById('btn-checkin');
        const radarBox = document.getElementById('radar-box');
        const checkinStatusText = document.getElementById('checkin-status-text');
        const checkinTimeText = document.getElementById('checkin-time-text');
        
        const renewModal = document.getElementById('renew-modal');
        const btnRenewTrigger = document.getElementById('btn-renew-trigger');
        const btnModalClose = document.getElementById('btn-modal-close');
        const planOptions = document.querySelectorAll('.plan-option');
        const btnMethodAutopay = document.getElementById('btn-method-autopay');
        const btnMethodOnetime = document.getElementById('btn-method-onetime');
        const btnPayConfirm = document.getElementById('btn-pay-confirm');
        
        let selectedPlanId = planOptions.length > 0 ? planOptions[0].dataset.planId : null;
        let paymentType = 'autopay'; // autopay or onetime

        // Toast helper
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            const msgEl = document.getElementById('toast-message');
            toast.className = 'toast ' + type;
            msgEl.textContent = message;
            toast.style.display = 'flex';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 4000);
        }

        // PWA Implementation
        // Detect iOS
        const isIos = () => {
            const userAgent = window.navigator.userAgent.toLowerCase();
            return /iphone|ipad|ipod/.test(userAgent);
        };
        // Detect if already running standalone
        const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

        // Intercept installation prompt on Android
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Show custom install banner
            pwaInstallBanner.style.display = 'flex';
        });

        // Show install button for iOS users if not already installed
        window.addEventListener('load', () => {
            if (isIos() && !isInStandaloneMode()) {
                pwaInstallBanner.style.display = 'flex';
            }
        });

        btnPwaInstall.addEventListener('click', async () => {
            if (isIos()) {
                // Show iOS guide sheet
                iosInstallGuide.style.display = 'flex';
            } else if (deferredPrompt) {
                // Trigger Android install prompt
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    pwaInstallBanner.style.display = 'none';
                }
                deferredPrompt = null;
            }
        });

        btnIosClose.addEventListener('click', () => {
            iosInstallGuide.style.display = 'none';
        });

        // Geolocation Check-in
        btnCheckin.addEventListener('click', () => {
            if (!navigator.geolocation) {
                showToast('Geolocation is not supported by your browser.', 'danger');
                return;
            }

            btnCheckin.disabled = true;
            btnCheckin.textContent = 'Verifying Location...';

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        const response = await fetch('{{ route("member.checkin") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success) {
                            showToast(result.message, 'success');
                            radarBox.classList.add('checked-in');
                            checkinStatusText.textContent = 'Checked In Today';
                            
                            const now = new Date();
                            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            checkinTimeText.textContent = 'Time: ' + timeStr;
                            btnCheckin.textContent = 'Attendance Marked';
                        } else {
                            showToast(result.message || 'Check-in failed.', 'danger');
                            btnCheckin.disabled = false;
                            btnCheckin.textContent = 'Mark Attendance';
                        }
                    } catch (error) {
                        console.error(error);
                        showToast('Error marking attendance. Please try again.', 'danger');
                        btnCheckin.disabled = false;
                        btnCheckin.textContent = 'Mark Attendance';
                    }
                },
                (error) => {
                    let errMsg = 'Location access denied. Please enable GPS permissions.';
                    if (error.code === error.TIMEOUT) {
                        errMsg = 'Location request timed out. Please try again.';
                    }
                    showToast(errMsg, 'danger');
                    btnCheckin.disabled = false;
                    btnCheckin.textContent = 'Mark Attendance';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });

        // Renew Modal Interactions
        btnRenewTrigger.addEventListener('click', () => {
            renewModal.style.display = 'flex';
        });

        btnModalClose.addEventListener('click', () => {
            renewModal.style.display = 'none';
        });

        planOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                planOptions.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                selectedPlanId = opt.dataset.planId;
            });
        });

        btnMethodAutopay.addEventListener('click', () => {
            btnMethodOnetime.classList.remove('active');
            btnMethodAutopay.classList.add('active');
            paymentType = 'autopay';
        });

        btnMethodOnetime.addEventListener('click', () => {
            btnMethodAutopay.classList.remove('active');
            btnMethodOnetime.classList.add('active');
            paymentType = 'onetime';
        });

        btnPayConfirm.addEventListener('click', async () => {
            if (!selectedPlanId) {
                showToast('Please select a plan.', 'danger');
                return;
            }

            btnPayConfirm.disabled = true;
            btnPayConfirm.textContent = 'Preparing Checkout...';

            const endpoint = paymentType === 'autopay' 
                ? '{{ route("member.payment.subscribe") }}' 
                : '{{ route("member.payment.order") }}';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: selectedPlanId
                    })
                });

                const result = await response.json();
                
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to initiate checkout.');
                }

                const data = result.data;
                
                // Razorpay checkout configuration
                const options = {
                    key: data.razorpay_key,
                    name: 'FitPro Gym',
                    description: data.description,
                    image: '/pwa-icon.svg',
                    handler: async function (response) {
                        showToast('Payment successful. Verifying...', 'success');
                        
                        const verifyResponse = await fetch('{{ route("member.payment.verify") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                plan_id: selectedPlanId,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                                razorpay_order_id: data.order_id || null,
                                razorpay_subscription_id: data.subscription_id || null
                            })
                        });

                        const verifyResult = await verifyResponse.json();
                        
                        if (verifyResponse.ok && verifyResult.success) {
                            showToast(verifyResult.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(verifyResult.message || 'Signature verification failed.', 'danger');
                        }
                    },
                    prefill: {
                        name: data.member_name,
                        email: data.member_email,
                        contact: data.member_phone
                    },
                    theme: {
                        color: '#f43f5e'
                    }
                };

                if (paymentType === 'autopay') {
                    options.subscription_id = data.subscription_id;
                } else {
                    options.order_id = data.order_id;
                }

                const rzp = new Razorpay(options);
                rzp.open();
                renewModal.style.display = 'none';

            } catch (error) {
                console.error(error);
                showToast(error.message || 'Checkout error. Try again.', 'danger');
            } finally {
                btnPayConfirm.disabled = false;
                btnPayConfirm.textContent = 'Proceed to Checkout';
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === renewModal) {
                renewModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
