<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Start your GymSaathi trial</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: #ffffff;
            --ink: #102033;
            --muted: #5d6b7c;
            --line: #d9e2ec;
            --accent: #0f766e;
            --accent-strong: #0b5f59;
            --accent-soft: #dff7f3;
            --danger: #b42318;
            --danger-bg: #fff1f0;
            --shadow: 0 22px 55px rgba(15, 23, 42, .12);
        }

        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background:
                linear-gradient(135deg, rgba(15, 118, 110, .12), transparent 34%),
                linear-gradient(315deg, rgba(14, 165, 233, .10), transparent 28%),
                var(--bg);
            color: var(--ink);
        }

        .shell {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 42px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .mark {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 10px;
            background: var(--accent);
            color: #fff;
            font-weight: 900;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 430px;
            gap: 34px;
            align-items: start;
        }

        .intro {
            padding-top: 34px;
        }

        h1 {
            max-width: 720px;
            margin: 0 0 18px;
            font-size: clamp(34px, 5vw, 58px);
            line-height: 1.02;
            letter-spacing: 0;
        }

        .lead {
            max-width: 620px;
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.65;
        }

        .proof {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            max-width: 660px;
            margin-top: 34px;
        }

        .proof div {
            min-height: 94px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, .72);
        }

        .proof strong {
            display: block;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .proof span {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .panel-header {
            padding: 24px 24px 0;
        }

        h2 {
            margin: 0 0 8px;
            font-size: 22px;
            line-height: 1.2;
        }

        .hint {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .errors {
            margin: 18px 24px 0;
            padding: 12px 14px;
            border: 1px solid #f3b5ae;
            border-radius: 8px;
            background: var(--danger-bg);
            color: var(--danger);
            font-size: 14px;
        }

        .errors strong { display: block; margin-bottom: 6px; }
        .errors ul { margin: 0; padding-left: 18px; }

        form {
            display: grid;
            gap: 16px;
            padding: 24px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        label {
            font-size: 13px;
            font-weight: 800;
            color: #223044;
        }

        input {
            width: 100%;
            min-height: 44px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            padding: 10px 12px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .14);
        }

        input[aria-invalid="true"] {
            border-color: var(--danger);
            background: #fffafa;
        }

        .field-error {
            color: var(--danger);
            font-size: 12px;
            line-height: 1.4;
        }

        .helper {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
        }

        button {
            min-height: 46px;
            border: 0;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 900;
            transition: background .15s ease, transform .15s ease;
        }

        button:hover { background: var(--accent-strong); }
        button:active { transform: translateY(1px); }

        .subdomain {
            margin-top: 2px;
            padding: 11px 12px;
            border-radius: 8px;
            background: var(--accent-soft);
            color: #134e4a;
            font-size: 13px;
            line-height: 1.45;
        }

        @media (max-width: 960px) {
            .shell { padding: 24px 0; }
            .layout { grid-template-columns: 1fr; }
            .intro { padding-top: 0; }
            .proof { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .shell { width: min(100% - 20px, 1120px); }
            .brand { margin-bottom: 18px; }
            .row { grid-template-columns: 1fr; }
            .panel-header, form { padding-inline: 18px; }
            .errors { margin-inline: 18px; }
            h1 { font-size: 34px; }
            .lead { font-size: 16px; }
        }
    </style>
</head>
<body>
<main class="shell">
    <div class="brand" aria-label="GymSaathi">
        <span class="mark">GS</span>
        <span>GymSaathi</span>
    </div>

    <div class="layout">
        <section class="intro">
            <h1>Run members, billing, renewals, and GST invoices from one gym workspace.</h1>
            <p class="lead">
                Create a 14-day trial with a dedicated gym subdomain, starter plans, and an owner login ready for the admin panel.
            </p>

            <div class="proof" aria-label="Included modules">
                <div>
                    <strong>Tenant safe</strong>
                    <span>Each gym gets isolated members, plans, invoices, and reminders.</span>
                </div>
                <div>
                    <strong>Billing ready</strong>
                    <span>Default monthly, quarterly, and yearly plans are created on signup.</span>
                </div>
                <div>
                    <strong>Owner access</strong>
                    <span>The first user receives full access for their gym workspace.</span>
                </div>
            </div>
        </section>

        <section class="panel" aria-labelledby="registration-title">
            <div class="panel-header">
                <h2 id="registration-title">Start your trial</h2>
                <p class="hint">Use the owner's working email and WhatsApp-ready phone number.</p>
            </div>

            @if ($errors->any())
                <div class="errors" role="alert">
                    <strong>Fix these details:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('gym.register') }}">
                @csrf

                <div class="field">
                    <label for="gym_name">Gym name</label>
                    <input id="gym_name" name="gym_name" value="{{ old('gym_name') }}" placeholder="FitZone Agra" autocomplete="organization" required aria-invalid="{{ $errors->has('gym_name') ? 'true' : 'false' }}" />
                    @error('gym_name')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label for="owner_name">Owner name</label>
                    <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" placeholder="Dhruv Agarwal" autocomplete="name" required aria-invalid="{{ $errors->has('owner_name') ? 'true' : 'false' }}" />
                    @error('owner_name')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="row">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" value="{{ old('email') }}" placeholder="owner@fitzone.com" type="email" autocomplete="email" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" />
                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="91XXXXXXXXXX" autocomplete="tel" required aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" />
                        @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="field">
                    <label for="city">City</label>
                    <input id="city" name="city" value="{{ old('city') }}" placeholder="Agra" autocomplete="address-level2" aria-invalid="{{ $errors->has('city') ? 'true' : 'false' }}" />
                    @error('city')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Min 8 characters" autocomplete="new-password" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" />
                        @error('password')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat password" autocomplete="new-password" required />
                    </div>
                </div>

                <div class="subdomain">
                    Your workspace URL will be created as <strong>your-gym.{{ config('app.base_domain') }}/admin</strong>.
                </div>

                <button type="submit">Create gym workspace</button>
                <p class="helper">Trial length: 14 days. Owner credentials are created with this workspace.</p>
            </form>
        </section>
    </div>
</main>
</body>
</html>
