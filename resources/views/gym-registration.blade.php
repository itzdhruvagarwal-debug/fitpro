<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register your gym</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 0; background: #0b1220; color: #e5e7eb; }
        .wrap { max-width: 920px; margin: 8vh auto; padding: 24px; }
        .grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 28px; }
        p { margin: 0 0 16px; color: #cbd5e1; line-height: 1.5; }
        label { display: block; font-weight: 600; margin: 12px 0 6px; color: #e2e8f0; }
        input { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #334155; background: #0b1220; color: #e5e7eb; }
        button { margin-top: 16px; width: 100%; padding: 12px 14px; border-radius: 12px; border: 0; background: #22c55e; color: #052e16; font-weight: 800; cursor: pointer; }
        .hint { font-size: 13px; color: #94a3b8; }
        .errors { background: #7f1d1d; color: #fecaca; border: 1px solid #991b1b; border-radius: 12px; padding: 12px 14px; margin-bottom: 14px; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="grid">
        <div class="card">
            <h1>Start your GymSaathi trial</h1>
            <p>Create your gym account. You’ll get a subdomain like <b>your-gym.{{ config('app.base_domain') }}</b>.</p>

            @if ($errors->any())
                <div class="errors">
                    <b>Fix these:</b>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('gym.register') }}">
                @csrf
                <label>Gym Name</label>
                <input name="gym_name" value="{{ old('gym_name') }}" placeholder="FitZone Agra" required />

                <label>Owner Name</label>
                <input name="owner_name" value="{{ old('owner_name') }}" placeholder="Dhruv Agarwal" required />

                <label>Email</label>
                <input name="email" value="{{ old('email') }}" placeholder="owner@fitzone.com" type="email" required />

                <label>Phone</label>
                <input name="phone" value="{{ old('phone') }}" placeholder="91XXXXXXXXXX" required />
                <div class="hint">Use WhatsApp-capable number for faster onboarding.</div>

                <label>City</label>
                <input name="city" value="{{ old('city') }}" placeholder="Agra" />

                <label>Password</label>
                <input name="password" type="password" placeholder="Min 8 characters" required />

                <button type="submit">Create Gym</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">What you get</h2>
            <p class="hint">Trial includes full access to billing, members, reminders, and GST invoices.</p>
            <ul>
                <li>Tenant-isolated data per gym</li>
                <li>Admin panel at `/admin`</li>
                <li>Automated reminders & payment updates</li>
                <li>Upgrade when you’re ready</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>

