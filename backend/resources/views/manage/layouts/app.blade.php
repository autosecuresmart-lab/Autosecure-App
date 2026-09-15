<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Manage') · AUTOSECURE</title>
    <style>
        :root {
            --as-bg: #0b1220;
            --as-panel: #121b2d;
            --as-panel-2: #17223a;
            --as-line: #24304a;
            --as-text: #e6ebf5;
            --as-muted: #93a1bd;
            --as-accent: #ff7a1a;
            --as-accent-soft: rgba(255, 122, 26, .12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--as-bg);
            color: var(--as-text);
            font-size: 14px;
            line-height: 1.5;
        }
        a { color: inherit; text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            flex: 0 0 250px;
            background: var(--as-panel);
            border-right: 1px solid var(--as-line);
            padding: 18px 14px;
        }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
        .brand-mark {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--as-accent); color: #12100c;
            display: grid; place-items: center; font-weight: 800;
        }
        .brand-name { font-weight: 700; letter-spacing: .3px; }
        .brand-sub { color: var(--as-muted); font-size: 11px; letter-spacing: .6px; text-transform: uppercase; }
        .nav-label { color: var(--as-muted); font-size: 11px; text-transform: uppercase; letter-spacing: .7px; margin: 18px 8px 8px; }
        .nav a {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 10px; border-radius: 8px; color: var(--as-muted); margin-bottom: 2px;
        }
        .nav a:hover { background: var(--as-panel-2); color: var(--as-text); }
        .nav a.active { background: var(--as-accent-soft); color: var(--as-text); box-shadow: inset 2px 0 0 var(--as-accent); }
        .nav a.locked { opacity: .38; pointer-events: none; }
        .main { flex: 1; min-width: 0; }
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 24px; border-bottom: 1px solid var(--as-line); background: var(--as-panel);
        }
        .content { padding: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 26px 0 10px; }
        .muted { color: var(--as-muted); }
        .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); }
        .card {
            background: var(--as-panel); border: 1px solid var(--as-line);
            border-radius: 12px; padding: 16px;
        }
        .stat-value { font-size: 26px; font-weight: 700; }
        .stat-label { color: var(--as-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
        .pill {
            display: inline-block; padding: 3px 9px; border-radius: 999px;
            background: var(--as-accent-soft); color: var(--as-accent);
            font-size: 11px; font-weight: 600; letter-spacing: .3px;
        }
        .pill-neutral { background: var(--as-panel-2); color: var(--as-muted); }
        ul.list { margin: 0; padding-left: 18px; }
        ul.list li { margin-bottom: 5px; }
        .btn {
            display: inline-block; padding: 9px 16px; border-radius: 8px; border: 0;
            background: var(--as-accent); color: #12100c; font-weight: 700; cursor: pointer;
            font-size: 14px;
        }
        .btn-ghost { background: transparent; border: 1px solid var(--as-line); color: var(--as-text); font-weight: 600; }
        .field { margin-bottom: 14px; }
        .field label { display: block; margin-bottom: 6px; color: var(--as-muted); font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
        .field input[type=email], .field input[type=password], .field input[type=text] {
            width: 100%; padding: 10px 12px; border-radius: 8px;
            border: 1px solid var(--as-line); background: var(--as-panel-2); color: var(--as-text);
        }
        .errors { background: rgba(220, 60, 60, .12); border: 1px solid rgba(220, 60, 60, .4); color: #ffb3b3; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .login-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .login-card { width: 100%; max-width: 380px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 9px 10px; border-bottom: 1px solid var(--as-line); }
        th { color: var(--as-muted); font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        code { background: var(--as-panel-2); padding: 2px 6px; border-radius: 5px; font-size: 12px; }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">AS</div>
            <div>
                <div class="brand-name">AUTOSECURE</div>
                <div class="brand-sub">Manage</div>
            </div>
        </div>

        @php
            $admin = auth('admin')->user();
            $super = $admin?->isSuperAdmin() ?? false;
            $granted = $super ? null : ($admin?->permissions()->pluck('slug')->all() ?? []);
            $modules = \App\Http\Controllers\Manage\ModuleController::MODULES;
            $current = request()->route('module') ?? request()->segment(2);
        @endphp

        <nav class="nav">
            <div class="nav-label">Overview</div>
            <a href="{{ route('manage.dashboard') }}" class="{{ request()->routeIs('manage.dashboard') ? 'active' : '' }}">
                <span>Dashboard</span>
            </a>

            <div class="nav-label">Modules</div>
            @foreach ($modules as $key => $module)
                @php
                    $permission = \App\Http\Controllers\Manage\ModuleController::permissionFor($key);
                    $allowed = $super || in_array($permission, $granted, true);
                @endphp
                <a href="{{ $allowed ? route('manage.'.$key.'.index') : '#' }}"
                   class="{{ $current === $key ? 'active' : '' }} {{ $allowed ? '' : 'locked' }}"
                   title="{{ $allowed ? $module['summary'] : 'Requires permission: '.$permission }}">
                    <span>{{ $module['title'] }}</span>
                    @unless ($allowed)
                        <span class="pill pill-neutral">locked</span>
                    @endunless
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="main">
        <div class="topbar">
            <div>
                <div class="brand-sub">AUTOSECURE 2.0 · Management Portal</div>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                @auth('admin')
                    <div style="text-align:right;">
                        <div>{{ auth('admin')->user()->name }}</div>
                        <div class="muted" style="font-size:12px;">
                            {{ auth('admin')->user()->isSuperAdmin() ? 'Super Admin' : (auth('admin')->user()->roles->pluck('name')->join(', ') ?: 'No role') }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('manage.logout') }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Sign out</button>
                    </form>
                @endauth
            </div>
        </div>

        <div class="content">
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
