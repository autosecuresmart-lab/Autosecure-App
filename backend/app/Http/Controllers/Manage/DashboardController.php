<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Device;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * /manage dashboard.
 *
 * Phase 1 provides the shell: real headline counts plus a module map. Deeper
 * reporting arrives with each module in later phases.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $admin->loadMissing('roles.permissions');

        return view('manage.dashboard', [
            'admin' => $admin,
            'stats' => [
                'customers' => User::count(),
                'vehicles' => Vehicle::count(),
                'devices' => Device::count(),
                'vendors_total' => Vendor::count(),
                'vendors_pending' => Vendor::whereIn('status', [Vendor::STATUS_PENDING, Vendor::STATUS_UNDER_REVIEW])->count(),
                'bookings' => Booking::count(),
                'payments_successful' => Payment::where('status', Payment::STATUS_SUCCESSFUL)->count(),
            ],
            'permissions' => $admin->isSuperAdmin()
                ? '*'
                : $admin->permissions()->pluck('slug')->all(),
        ]);
    }
}
