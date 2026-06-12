<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Member Login - FitPro Dashboard</title>
    <link rel="manifest" href="/manifest.json" />
    <link rel="icon" href="/pwa-icon.svg" type="image/svg+xml" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            --bg: #09090b;
            --card-bg: rgba(24, 24, 27, 0.65);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
            --accent: #f43f5e;
            --accent-glow: rgba(244, 63, 94, 0.4);
            --accent-strong: #be123c;
            --input-bg: rgba(9, 9, 11, 0.8);
            --input-border: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(244, 63, 94, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(190, 18, 60, 0.1) 0%, transparent 40%),
                        var(--bg);
            color: var(--text-main);
            overflow-x: hidden;
            padding: 16px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, var(--accent), transparent, var(--accent-strong));
            border-radius: 24px;
            z-index: -1;
            opacity: 0.15;
            transition: opacity 0.5s ease;
        }

        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
            text-align: center;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            border-radius: 16px;
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px var(--accent-glow);
            animation: pulse 2s infinite alternate;
        }

        .brand-logo svg {
            width: 32px;
            height: 32px;
            fill: #fff;
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #e4e4e7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .brand p {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            height: 48px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 15px;
            padding: 0 16px;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
            background: #000;
        }

        .btn-submit {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px var(--accent-glow);
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px var(--accent-glow);
            filter: brightness(1.1);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .errors {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px 16px;
            border-radius: 12px;
            color: #f87171;
            font-size: 14px;
            margin-bottom: 24px;
            animation: shake 0.5s ease-in-out;
        }

        .errors ul {
            list-style: none;
        }

        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-muted);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.4);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(244, 63, 94, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(244, 63, 94, 0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand">
            <div class="brand-logo">
                <svg viewBox="0 0 24 24">
                    <path d="M5 9c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H5zm14 0c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1h-1zM9 11h6v2H9v-2zm-2-2h2v6H7V9zm8 0h2v6h-2V9z" />
                </svg>
            </div>
            <h1>Member Workspace</h1>
            <p>Access your workout dashboard</p>
        </div>

        @if ($errors->any())
            <div class="errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('member.login.post') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="name@domain.com" />
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                </div>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <p class="footer-note">Note: Onboarding members can use their registered contact number as their temporary password.</p>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered', reg))
                    .catch(err => console.error('Service Worker registration failed', err));
            });
        }
    </script>
</body>
</html>
