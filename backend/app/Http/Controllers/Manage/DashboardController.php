<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Payment;
use App\Models\TheftEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * /manage dashboard.
 *
 * Provides real-time metrics across customers, vehicles, hardware devices,
 * security alerts, bookings, payments, and activity trends.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $admin->loadMissing('roles.permissions');

        // Calculate 7-day registration and security activity metrics
        $days = [];
        $userTrend = [];
        $vehicleTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('D');
            $userTrend[] = User::whereDate('created_at', $date)->count();
            $vehicleTrend[] = Vehicle::whereDate('created_at', $date)->count();
        }

        $totalUsers = User::count();
        $individualUsers = User::where('account_type', 'individual')->count();
        $businessUsers = User::where('account_type', 'business')->count();

        $totalVehicles = Vehicle::count();
        $totalDevices = Device::count();
        $activeDevices = Device::where('status', 'active')->count();

        $openThefts = TheftEvent::where('status', TheftEvent::STATUS_OPEN)->count();
        $resolvedThefts = TheftEvent::where('status', TheftEvent::STATUS_RESOLVED)->count();
        $totalCommands = DeviceCommand::count();

        $recentIncidents = TheftEvent::with(['vehicle.user', 'device'])
            ->latest('triggered_at')
            ->take(5)
            ->get();

        $recentUsers = User::withCount(['vehicles', 'devices'])
            ->latest()
            ->take(5)
            ->get();

        return view('manage.dashboard', [
            'admin' => $admin,
            'overview' => [
                'total_users' => $totalUsers,
                'individual_users' => $individualUsers,
                'business_users' => $businessUsers,
                'total_vehicles' => $totalVehicles,
                'total_devices' => $totalDevices,
                'active_devices' => $activeDevices,
                'open_incidents' => $openThefts,
                'resolved_incidents' => $resolvedThefts,
                'total_commands' => $totalCommands,
                'date_label' => 'Today: ' . now()->format('d M Y'),
            ],
            'chart_data' => [
                'days' => $days,
                'users' => $userTrend,
                'vehicles' => $vehicleTrend,
            ],
            'recent_incidents' => $recentIncidents,
            'recent_users' => $recentUsers,
            'stats' => [
                'customers' => $totalUsers,
                'vehicles' => $totalVehicles,
                'devices' => $totalDevices,
                'vendors_total' => Vendor::count(),
                'vendors_pending' => Vendor::whereIn('status', [Vendor::STATUS_PENDING, Vendor::STATUS_UNDER_REVIEW])->count(),
                'bookings' => Booking::count(),
                'payments_successful' => Payment::where('status', Payment::STATUS_SUCCESSFUL)->count(),
                'revenue_total' => (float) Payment::where('status', Payment::STATUS_SUCCESSFUL)->sum('amount'),
            ],
            'permissions' => $admin->isSuperAdmin()
                ? '*'
                : $admin->permissions()->pluck('slug')->all(),
        ]);
    }
}

