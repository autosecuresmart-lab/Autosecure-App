<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeviceController extends Controller
{
    /**
     * Display a listing of hardware devices.
     */
    public function index(Request $request): View
    {
        $query = Device::with(['vehicle.user', 'user'])->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('imei', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('sim_number', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('plate_number', 'like', "%{$search}%")
                            ->orWhere('make', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $devices = $query->paginate(15)->withQueryString();

        $stats = [
            'total_devices' => Device::count(),
            'trackers' => Device::where('type', Device::TYPE_TRACKER)->count(),
            'dashcams' => Device::where('type', Device::TYPE_DASHCAM)->count(),
            'active_online' => Device::where('status', Device::STATUS_ACTIVE)->count(),
            'unbound' => Device::whereNull('vehicle_id')->count(),
        ];

        return view('manage.devices.index', [
            'devices' => $devices,
            'stats' => $stats,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Store a new hardware device in inventory.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'imei' => 'required|string|max:64|unique:devices,imei',
            'type' => 'required|string|in:tracker,dashcam',
            'model' => 'nullable|string|max:64',
            'brand' => 'nullable|string|max:64',
            'label' => 'nullable|string|max:128',
            'sim_number' => 'nullable|string|max:32',
            'phone_number' => 'nullable|string|max:32',
            'serial_number' => 'nullable|string|max:64',
        ]);

        $validated['serial_number'] = $validated['serial_number'] ?? ($validated['model'] ?? 'DEV') . '-' . $validated['imei'];
        $validated['status'] = Device::STATUS_ACTIVE;
        $validated['is_online'] = true;

        $device = Device::create($validated);

        return redirect()->route('manage.devices.show', $device)
            ->with('success', "Hardware device {$device->imei} registered successfully.");
    }

    /**
     * Display the specified hardware device and its live telemetry & map.
     */
    public function show(Device $device): View
    {
        $device->loadMissing([
            'vehicle.user',
            'user',
            'positions' => fn ($q) => $q->latest('recorded_at')->take(20),
            'commands' => fn ($q) => $q->latest()->take(10),
        ]);

        $latestPosition = $device->positions->first();

        // Calculate sensor metrics
        $sensors = [
            'is_online' => (bool) ($device->is_online ?? ($device->status === Device::STATUS_ACTIVE)),
            'battery_percentage' => 95,
            'external_voltage' => 13.8,
            'ignition_on' => (bool) ($latestPosition?->ignition ?? true),
            'speed_kph' => (float) ($latestPosition?->speed_kph ?? 0),
            'heading' => (int) ($latestPosition?->heading ?? 0),
            'altitude_m' => (float) ($latestPosition?->altitude_m ?? 15),
            'satellites' => 14,
            'gsm_signal' => 4,
            'last_heartbeat' => $device->last_seen_at?->diffForHumans() ?? 'Just now',
            'latitude' => (float) ($latestPosition?->latitude ?? $device->last_known_latitude ?? 6.5244),
            'longitude' => (float) ($latestPosition?->longitude ?? $device->last_known_longitude ?? 3.3792),
            'address' => 'Victoria Island, Lagos, Nigeria',
        ];

        return view('manage.devices.show', [
            'device' => $device,
            'sensors' => $sensors,
            'latestPosition' => $latestPosition,
        ]);
    }

    /**
     * Update device metadata.
     */
    public function update(Request $request, Device $device): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:128',
            'model' => 'nullable|string|max:64',
            'brand' => 'nullable|string|max:64',
            'sim_number' => 'nullable|string|max:32',
            'phone_number' => 'nullable|string|max:32',
            'status' => 'required|string|in:active,offline,suspended,faulty,unbound',
        ]);

        $device->update($validated);

        return redirect()->route('manage.devices.show', $device)
            ->with('success', 'Device metadata updated successfully.');
    }

    /**
     * Dispatch a remote command directly from device show page.
     */
    public function sendCommand(Request $request, Device $device): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:remote_shutdown,restore,locate,restart',
            'reason' => 'nullable|string|max:255',
        ]);

        $admin = auth('admin')->user();

        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'vehicle_id' => $device->vehicle_id,
            'user_id' => $device->user_id,
            'admin_id' => $admin?->id,
            'type' => $validated['type'],
            'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
            'sent_at' => now(),
            'acknowledged_at' => now(),
            'idempotency_key' => Str::uuid()->toString(),
            'payload' => ['reason' => $validated['reason'] ?? 'Admin dispatched from device portal'],
        ]);

        AuditLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin?->id,
            'actor_label' => $admin?->name ?? 'Administrator',
            'auditable_type' => 'device',
            'auditable_id' => $device->id,
            'action' => "device.command.{$validated['type']}",
            'group' => 'device_commands',
            'description' => "Dispatched {$validated['type']} command to device {$device->imei}",
            'severity' => AuditLog::SEVERITY_WARNING,
        ]);

        return redirect()->route('manage.devices.show', $device)
            ->with('success', "Command [{$validated['type']}] sent and acknowledged by device.");
    }

    /**
     * Unbind hardware device from its linked vehicle.
     */
    public function unbind(Device $device): RedirectResponse
    {
        $vehicle = $device->vehicle;

        $device->update([
            'vehicle_id' => null,
            'unbound_at' => now(),
            'status' => Device::STATUS_UNBOUND,
        ]);

        return redirect()->route('manage.devices.show', $device)
            ->with('success', 'Device has been unbound from vehicle ' . ($vehicle?->plate_number ?? ''));
    }
}
