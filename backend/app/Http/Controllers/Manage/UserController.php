<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\TheftEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Display a listing of customer users.
     */
    public function index(Request $request): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'business_users' => User::where('account_type', 'business')->count(),
            'total_vehicles' => Vehicle::count(),
            'total_devices' => Device::count(),
        ];

        $query = User::query()
            ->withCount(['vehicles', 'devices'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->string('search'));
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('uuid', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->string('status'));
            })
            ->when($request->filled('account_type'), function ($q) use ($request) {
                $q->where('account_type', $request->string('account_type'));
            })
            ->orderByDesc('created_at');

        $users = $query->paginate(15)->withQueryString();

        return view('manage.users.index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'account_type' => $request->string('account_type')->toString(),
            ],
        ]);
    }

    /**
     * Store a newly created customer user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'account_type' => ['required', Rule::in(['individual', 'business'])],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'suspended', 'pending'])],
            'password' => ['required', 'string', 'min:6'],

            // Optional initial vehicle
            'attach_vehicle' => ['nullable', 'boolean'],
            'vehicle_make' => ['nullable', 'required_if:attach_vehicle,1', 'string', 'max:64'],
            'vehicle_model' => ['nullable', 'required_if:attach_vehicle,1', 'string', 'max:64'],
            'vehicle_year' => ['nullable', 'required_if:attach_vehicle,1', 'integer', 'min:1950', 'max:2050'],
            'vehicle_plate' => ['nullable', 'required_if:attach_vehicle,1', 'string', 'max:32'],
            'vehicle_colour' => ['nullable', 'string', 'max:32'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'account_type' => $validated['account_type'],
            'company_name' => $validated['account_type'] === 'business' ? ($validated['company_name'] ?? null) : null,
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        // If vehicle fields provided, create vehicle
        if (! empty($validated['attach_vehicle']) && ! empty($validated['vehicle_plate'])) {
            $vehicle = Vehicle::create([
                'user_id' => $user->id,
                'make' => $validated['vehicle_make'],
                'model' => $validated['vehicle_model'],
                'year' => $validated['vehicle_year'] ?? date('Y'),
                'plate_number' => strtoupper(trim($validated['vehicle_plate'])),
                'colour' => $validated['vehicle_colour'] ?? 'Silver',
                'is_primary' => true,
                'status' => 'active',
            ]);

            $this->audit->log('admin.vehicles.created', $vehicle, auth('admin')->user(), [], [
                'description' => "Initial vehicle {$vehicle->plate_number} created with user {$user->email}",
            ]);
        }

        $this->audit->log('admin.users.created', $user, auth('admin')->user(), [], [
            'description' => "Customer user {$user->name} ({$user->email}) created manually by admin",
        ]);

        return redirect()->route('manage.users.show', $user)
            ->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Display the specified customer user with full details (Col 8 / Col 4 layout).
     */
    public function show(User $user): View
    {
        $user->load([
            'vehicles.devices',
            'vehicles.reminders',
            'vehicles.maintenanceRecords',
            'devices.vehicle',
            'subscriptions',
            'coinWallet',
            'theftEvents',
        ]);

        $vehicles = $user->vehicles;
        $devices = $user->devices;
        $activeSubscription = $user->activeSubscription;
        $coinWallet = $user->coinWallet;
        $recentMaintenance = $user->maintenanceRecords()->with('vehicle')->latest()->take(5)->get();
        $recentTheftEvents = $user->theftEvents()->with('vehicle')->latest()->take(5)->get();

        return view('manage.users.show', [
            'user' => $user,
            'vehicles' => $vehicles,
            'devices' => $devices,
            'activeSubscription' => $activeSubscription,
            'coinWallet' => $coinWallet,
            'recentMaintenance' => $recentMaintenance,
            'recentTheftEvents' => $recentTheftEvents,
        ]);
    }

    /**
     * Update the specified customer user profile.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'account_type' => ['required', Rule::in(['individual', 'business'])],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'suspended', 'pending'])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $dataToUpdate = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'account_type' => $validated['account_type'],
            'company_name' => $validated['account_type'] === 'business' ? ($validated['company_name'] ?? null) : null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $dataToUpdate['password'] = Hash::make($validated['password']);
        }

        $user->update($dataToUpdate);

        $this->audit->log('admin.users.updated', $user, auth('admin')->user(), [], [
            'description' => "Customer user {$user->email} profile updated by admin",
        ]);

        return redirect()->route('manage.users.show', $user)
            ->with('success', 'User profile updated successfully.');
    }

    /**
     * Remove the specified user from storage (Soft Delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        $userName = $user->name;
        $userEmail = $user->email;

        $this->audit->log('admin.users.deleted', $user, auth('admin')->user(), [], [
            'description' => "Customer user {$userName} ({$userEmail}) deleted/deactivated by admin",
        ]);

        $user->delete();

        return redirect()->route('manage.users.index')
            ->with('success', "User '{$userName}' has been deactivated successfully.");
    }

    /**
     * Toggle the status of the user (Active / Suspended).
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        $this->audit->log('admin.users.status_toggle', $user, auth('admin')->user(), [], [
            'description' => "Status of user {$user->email} changed to {$newStatus}",
        ]);

        return redirect()->back()->with('success', "User status updated to '{$newStatus}'.");
    }

    /**
     * Add and assign a new vehicle to the user.
     */
    public function storeVehicle(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'make' => ['required', 'string', 'max:64'],
            'model' => ['required', 'string', 'max:64'],
            'year' => ['required', 'integer', 'min:1950', 'max:2050'],
            'plate_number' => ['required', 'string', 'max:32'],
            'colour' => ['nullable', 'string', 'max:32'],
            'vin' => ['nullable', 'string', 'max:64'],
            'fuel_type' => ['nullable', 'string', 'max:32'],
            'transmission' => ['nullable', 'string', 'max:32'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
        ]);

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'make' => $validated['make'],
            'model' => $validated['model'],
            'year' => $validated['year'],
            'plate_number' => strtoupper(trim($validated['plate_number'])),
            'colour' => $validated['colour'] ?? 'Silver',
            'vin' => $validated['vin'] ?? null,
            'fuel_type' => isset($validated['fuel_type']) ? strtolower($validated['fuel_type']) : 'petrol',
            'transmission' => isset($validated['transmission']) ? strtolower($validated['transmission']) : 'automatic',
            'odometer_km' => $validated['odometer_km'] ?? 0,
            'is_primary' => ($user->vehicles()->count() === 0),
            'status' => 'active',
        ]);

        $this->audit->log('admin.vehicles.created', $vehicle, auth('admin')->user(), [], [
            'description' => "Vehicle {$vehicle->plate_number} ({$vehicle->make} {$vehicle->model}) assigned to {$user->email}",
        ]);

        return redirect()->route('manage.users.show', $user)
            ->with('success', "Vehicle '{$vehicle->plate_number}' successfully added to user.");
    }

    /**
     * Add and bind a new device (tracker or dashcam) to user & vehicle.
     */
    public function storeDevice(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['tracker', 'dashcam'])],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'brand' => ['required', 'string', 'max:64'],
            'model' => ['required', 'string', 'max:64'],
            'serial_number' => ['required', 'string', 'max:64', 'unique:devices,serial_number'],
            'imei' => ['nullable', 'string', 'max:64', 'unique:devices,imei'],
            'sim_number' => ['nullable', 'string', 'max:32'],
            'label' => ['nullable', 'string', 'max:64'],
        ]);

        $device = Device::create([
            'user_id' => $user->id,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'type' => $validated['type'],
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'serial_number' => $validated['serial_number'],
            'imei' => $validated['imei'] ?? null,
            'sim_number' => $validated['sim_number'] ?? null,
            'label' => $validated['label'] ?? ($validated['brand'].' '.$validated['model']),
            'status' => Device::STATUS_ACTIVE,
            'is_online' => true,
            'bound_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->audit->log('admin.devices.created', $device, auth('admin')->user(), [], [
            'description' => "Device {$device->serial_number} ({$device->type}) bound to user {$user->email}",
        ]);

        return redirect()->route('manage.users.show', $user)
            ->with('success', "Device '{$device->serial_number}' successfully registered and bound.");
    }

    /**
     * Trigger remote security commands for a vehicle owned by user.
     */
    public function sendCommand(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'command_type' => ['required', Rule::in(['engine_cut', 'engine_restore', 'theft_alarm', 'locate_now'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        // Find primary tracker device for the vehicle
        $tracker = $vehicle->devices()->where('type', 'tracker')->first();

        $commandType = match ($validated['command_type']) {
            'engine_cut' => DeviceCommand::TYPE_REMOTE_SHUTDOWN,
            'engine_restore' => DeviceCommand::TYPE_RESTORE,
            'theft_alarm' => DeviceCommand::TYPE_THEFT_ALERT,
            'locate_now' => DeviceCommand::TYPE_LOCATE,
            default => DeviceCommand::TYPE_LOCATE,
        };

        if ($tracker) {
            DeviceCommand::create([
                'device_id' => $tracker->id,
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
                'admin_id' => auth('admin')->id(),
                'type' => $commandType,
                'payload' => ['reason' => $validated['reason'] ?? 'Admin quick action command'],
                'status' => DeviceCommand::STATUS_SENT,
                'sent_at' => now(),
            ]);
        }

        if ($validated['command_type'] === 'theft_alarm') {
            TheftEvent::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
                'status' => 'investigating',
                'reported_at' => now(),
                'initial_latitude' => $tracker?->last_known_latitude ?? 6.5244,
                'initial_longitude' => $tracker?->last_known_longitude ?? 3.3792,
                'notes' => 'Dispatched from Admin Manage Portal: '.$validated['reason'],
            ]);
        }

        $commandLabel = match ($validated['command_type']) {
            'engine_cut' => 'Remote Engine Cutoff / Immobilization',
            'engine_restore' => 'Remote Engine Restore',
            'theft_alarm' => 'Emergency Theft Mode Activated',
            'locate_now' => 'Instant GPS Telemetry Ping',
            default => $validated['command_type'],
        };

        $this->audit->sensitive('admin.security.remote_command', $vehicle, [
            'description' => "Dispatched security command [{$commandLabel}] to {$vehicle->plate_number} owned by {$user->email}",
            'command' => $validated['command_type'],
            'target_vehicle' => $vehicle->plate_number,
        ]);

        return redirect()->route('manage.users.show', $user)
            ->with('success', "Security command [{$commandLabel}] sent successfully to {$vehicle->plate_number}.");
    }
}
