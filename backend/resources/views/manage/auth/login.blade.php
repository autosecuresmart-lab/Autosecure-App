<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff sign in · AUTOSECURE</title>
    <style>
        :root {
            --as-bg: #0b1220; --as-panel: #121b2d; --as-panel-2: #17223a;
            --as-line: #24304a; --as-text: #e6ebf5; --as-muted: #93a1bd; --as-accent: #ff7a1a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: var(--as-bg); color: var(--as-text);
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif; font-size: 14px;
        }
        .card { width: 100%; max-width: 380px; background: var(--as-panel); border: 1px solid var(--as-line); border-radius: 14px; padding: 26px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .brand-mark { width: 34px; height: 34px; border-radius: 9px; background: var(--as-accent); color: #12100c; display: grid; place-items: center; font-weight: 800; }
        .brand-name { font-weight: 700; }
        .brand-sub { color: var(--as-muted); font-size: 11px; letter-spacing: .6px; text-transform: uppercase; }
        h1 { font-size: 18px; margin: 0 0 18px; }
        .field { margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; color: var(--as-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
        input[type=email], input[type=password] {
            width: 100%; padding: 10px 12px; border-radius: 8px;
            border: 1px solid var(--as-line); background: var(--as-panel-2); color: var(--as-text);
        }
        .btn { width: 100%; padding: 11px; border-radius: 8px; border: 0; background: var(--as-accent); color: #12100c; font-weight: 700; cursor: pointer; font-size: 14px; }
        .errors { background: rgba(220,60,60,.12); border: 1px solid rgba(220,60,60,.4); color: #ffb3b3; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .row { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; color: var(--as-muted); }
        .note { margin-top: 18px; color: var(--as-muted); font-size: 12px; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <div class="brand-mark">AS</div>
        <div>
            <div class="brand-name">AUTOSECURE</div>
            <div class="brand-sub">Management Portal</div>
        </div>
    </div>

    <h1>Staff sign in</h1>

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('manage.login.store') }}">
        @csrf

        <div class="field">
            <label for="email">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>

        <div class="row">
            <input id="remember" name="remember" type="checkbox" value="1">
            <label for="remember" style="margin:0; text-transform:none; letter-spacing:0;">Keep me signed in</label>
        </div>

        <button class="btn" type="submit">Sign in</button>
    </form>

    <p class="note">
        Staff accounts are held separately from customer accounts. Access to each
        module is granted by role.
    </p>
</div>
</body>
</html>
