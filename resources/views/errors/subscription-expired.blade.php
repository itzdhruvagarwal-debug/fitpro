<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Subscription Expired</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 0; padding: 0; background: #0b1220; color: #e5e7eb; }
        .wrap { max-width: 720px; margin: 10vh auto; padding: 24px; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 14px; padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { margin: 0 0 16px; color: #cbd5e1; line-height: 1.5; }
        .hint { font-size: 14px; color: #94a3b8; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #713f12; color: #fde68a; font-weight: 600; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="badge">Payment Required</div>
        <h1>Your subscription has expired</h1>
        <p>Please renew your plan to continue using GymSaathi.</p>
        <p class="hint">Gym: {{ $tenant->name ?? 'Unknown' }}</p>
    </div>
</div>
</body>
</html>

