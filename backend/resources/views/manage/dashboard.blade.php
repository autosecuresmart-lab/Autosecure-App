@extends('manage.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>
    <p class="muted">
        Phase 1 foundation. Headline counts are live; module depth arrives with the phase that builds it.
    </p>

    <h2>Platform</h2>
    <div class="grid">
        <div class="card">
            <div class="stat-value">{{ number_format($stats['customers']) }}</div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="card">
            <div class="stat-value">{{ number_format($stats['vehicles']) }}</div>
            <div class="stat-label">Vehicles</div>
        </div>
        <div class="card">
            <div class="stat-value">{{ number_format($stats['devices']) }}</div>
            <div class="stat-label">Devices</div>
        </div>
        <div class="card">
            <div class="stat-value">{{ number_format($stats['vendors_total']) }}</div>
            <div class="stat-label">Vendors</div>
        </div>
        <div class="card">
            <div class="stat-value">{{ number_format($stats['vendors_pending']) }}</div>
            <div class="stat-label">Awaiting verification</div>
        </div>
        <div class="card">
            <div class="stat-value">{{ number_format($stats['bookings']) }}</div>
            <div class="stat-label">Bookings</div>
        </div>
        <div class="card">
            <div class="stat-value">{{ number_format($stats['payments_successful']) }}</div>
            <div class="stat-label">Successful payments</div>
        </div>
    </div>

    <h2>Your access</h2>
    <div class="card">
        @if (auth('admin')->user()->isSuperAdmin())
            <p><span class="pill">Super Admin</span> — full access to every module.</p>
        @else
            <p>
                Roles:
                @forelse (auth('admin')->user()->roles as $role)
                    <span class="pill pill-neutral">{{ $role->name }}</span>
                @empty
                    <span class="muted">none assigned</span>
                @endforelse
            </p>
            <p class="muted">
                {{ count($permissions) }} permission(s) granted. Locked modules in the sidebar require a
                permission your account does not hold.
            </p>
        @endif
    </div>

    <h2>Phase 1 status</h2>
    <div class="card">
        <table>
            <thead>
                <tr><th>Area</th><th>State</th><th>Note</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Authentication</td>
                    <td><span class="pill">Ready</span></td>
                    <td class="muted">Customers via Sanctum tokens; staff via the admin guard on a separate table.</td>
                </tr>
                <tr>
                    <td>My Vehicle</td>
                    <td><span class="pill">Ready</span></td>
                    <td class="muted">CRUD with server-side ownership and sharing checks.</td>
                </tr>
                <tr>
                    <td>Security / Tracker</td>
                    <td><span class="pill pill-neutral">Pending</span></td>
                    <td class="muted">Awaiting tracker API documentation.</td>
                </tr>
                <tr>
                    <td>Dashcam</td>
                    <td><span class="pill pill-neutral">Pending</span></td>
                    <td class="muted">Awaiting dashcam SDK/API documentation.</td>
                </tr>
                <tr>
                    <td>Vehicle Care</td>
                    <td><span class="pill pill-neutral">Schema ready</span></td>
                    <td class="muted">Tables and models in place; endpoints arrive in Phase 3.</td>
                </tr>
                <tr>
                    <td>AutoDoc</td>
                    <td><span class="pill pill-neutral">Pending</span></td>
                    <td class="muted">Integration level not yet agreed.</td>
                </tr>
                <tr>
                    <td>Finder / Bookings / Payments</td>
                    <td><span class="pill pill-neutral">Schema ready</span></td>
                    <td class="muted">Awaiting commercial sign-off on fees and commission.</td>
                </tr>
                <tr>
                    <td>Coins / Subscriptions</td>
                    <td><span class="pill pill-neutral">Schema ready</span></td>
                    <td class="muted">Server-side entitlement resolution is implemented.</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
