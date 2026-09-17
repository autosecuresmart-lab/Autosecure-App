@extends('manage.layouts.app')

@section('title', 'Device ' . $device->imei)

@section('content')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- Top Alerts / Flash Messages -->
@if (session('success'))
    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-between text-emerald-800 text-xs font-semibold shadow-sm animate-fade-in">
        <div class="flex items-center gap-2.5">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 p-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-800 text-xs font-semibold shadow-sm">
        <div class="flex items-center gap-2 mb-1.5 text-rose-900 font-bold">
            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>Please review the errors below:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Breadcrumbs -->
<div class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('manage.dashboard') }}" class="hover:text-slate-900 transition">Dashboard</a>
    <span>/</span>
    <a href="{{ route('manage.devices.index') }}" class="hover:text-slate-900 transition">Devices</a>
    <span>/</span>
    <span class="font-bold text-slate-900 font-mono">{{ $device->imei }}</span>
</div>

<!-- Header Card -->
<div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-sm mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl {{ $device->type === 'dashcam' ? 'bg-purple-100 text-purple-600' : 'bg-brand-50 text-brand-500' }} border border-brand-200/60 grid place-items-center shrink-0 shadow-sm">
                @if ($device->type === 'dashcam')
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                @else
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="22" y1="12" x2="18" y2="12"></line><line x1="6" y1="12" x2="2" y2="12"></line><line x1="12" y1="6" x2="12" y2="2"></line><line x1="12" y1="22" x2="12" y2="18"></line></svg>
                @endif
            </div>

            <div>
                <div class="flex flex-wrap items-center gap-2.5 mb-1.5">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $device->label ?: ($device->model ?: 'GPS Tracker') }}</h1>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-mono font-bold">{{ $device->model ?? 'GG402' }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $device->type === 'dashcam' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">
                        {{ $device->type }}
                    </span>
                    @if ($sensors['is_online'])
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Online</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px] font-semibold border border-slate-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>Offline</span>
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-mono">
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-sans">IMEI:</span>
                        <span class="text-slate-800 font-bold select-all">{{ $device->imei }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-sans">SIM:</span>
                        <span class="text-slate-800 font-bold">{{ $device->sim_number ?: 'Not specified' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400 font-sans">Heartbeat:</span>
                        <span class="text-slate-800">{{ $sensors['last_heartbeat'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5">
            <button onclick="document.getElementById('editDeviceModal').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
                Edit Metadata
            </button>

            <form method="POST" action="{{ route('manage.devices.command', $device) }}" class="inline">
                @csrf
                <input type="hidden" name="type" value="locate">
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Ping GPS</span>
                </button>
            </form>

            @if ($device->vehicle)
                @if ($device->vehicle->is_immobilized)
                    <form method="POST" action="{{ route('manage.devices.command', $device) }}" onsubmit="return confirm('Restore engine ignition relay for this vehicle?')" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="restore">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-600/20 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Restore Engine Relay</span>
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('manage.devices.command', $device) }}" onsubmit="return confirm('WARNING: Are you sure you want to cut the engine relay and immobilize this vehicle?')" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="remote_shutdown">
                        <input type="hidden" name="reason" value="Manual emergency shutdown from device portal">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm shadow-rose-600/20 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Cut Engine Relay</span>
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Main Split Content (Col 8 / Col 4) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column: Map, Telemetry, Position History, Commands (Col 8) -->
    <div class="lg:col-span-8 space-y-6">
        <!-- Interactive Leaflet Map Card -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Live GPS Location</h3>
                </div>
                <div class="flex items-center gap-2 text-[11px] font-mono text-slate-500 bg-slate-50 px-3 py-1 rounded-lg border border-slate-200">
                    <span>LAT: {{ number_format($sensors['latitude'], 5) }}</span>
                    <span>•</span>
                    <span>LNG: {{ number_format($sensors['longitude'], 5) }}</span>
                </div>
            </div>

            <!-- Map View Container -->
            <div id="deviceMap" class="w-full h-[380px] bg-slate-900 relative z-0"></div>

            <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="font-medium text-slate-800">{{ $sensors['address'] }}</span>
                </div>
                <div class="text-[11px] text-slate-400 font-mono">
                    Speed: {{ $sensors['speed_kph'] }} km/h • Heading: {{ $sensors['heading'] }}°
                </div>
            </div>
        </div>

        <!-- Sensor & Telemetry Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Battery & Voltage</div>
                <div class="text-xl font-black text-slate-900 mt-1 flex items-baseline gap-1.5">
                    <span>{{ $sensors['external_voltage'] }}V</span>
                    <span class="text-xs font-semibold text-emerald-600">({{ $sensors['battery_percentage'] }}%)</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">External car battery</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ignition State</div>
                <div class="text-xl font-black {{ $sensors['ignition_on'] ? 'text-emerald-600' : 'text-slate-600' }} mt-1 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full {{ $sensors['ignition_on'] ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    <span>{{ $sensors['ignition_on'] ? 'IGNITION ON' : 'IGNITION OFF' }}</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">ACC Wire Sensor</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Speed & Motion</div>
                <div class="text-xl font-black text-slate-900 mt-1">
                    {{ $sensors['speed_kph'] }} <span class="text-xs font-semibold text-slate-500">KM/H</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">{{ $sensors['speed_kph'] > 0 ? 'In Transit' : 'Stationary / Parked' }}</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Satellites Locked</div>
                <div class="text-xl font-black text-sky-600 mt-1 flex items-center gap-1">
                    <span>{{ $sensors['satellites'] }}</span>
                    <span class="text-xs font-semibold text-slate-500">GPS/GLONASS</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">High precision fix</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">GSM Signal</div>
                <div class="text-xl font-black text-emerald-600 mt-1 flex items-center gap-1">
                    <span>4G LTE</span>
                    <span class="text-xs font-semibold text-slate-500">({{ $sensors['gsm_signal'] }}/5 Bars)</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">MTN / Airtel Nigeria</div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Relay State</div>
                <div class="text-xl font-black {{ ($device->vehicle?->is_immobilized ?? false) ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                    {{ ($device->vehicle?->is_immobilized ?? false) ? 'CUT OFF' : 'NORMAL' }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">Fuel pump circuit</div>
            </div>
        </div>

        <!-- Position History / GPS Breadcrumbs Table -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Recent GPS Fixes & Telemetry Breadcrumbs</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Latest timestamped coordinates broadcasted by this device</p>
                </div>
                <span class="text-xs font-mono font-semibold bg-slate-100 px-2.5 py-1 rounded-lg text-slate-600">
                    {{ $device->positions->count() }} records
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Coordinates</th>
                            <th class="py-3 px-4">Speed</th>
                            <th class="py-3 px-4">Ignition</th>
                            <th class="py-3 px-4">Heading</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-mono">
                        @forelse ($device->positions as $pos)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-sans text-slate-800">
                                    {{ $pos->recorded_at ? $pos->recorded_at->format('M d, H:i:s') : 'N/A' }}
                                    <span class="text-[10px] text-slate-400 block font-sans">{{ $pos->recorded_at ? $pos->recorded_at->diffForHumans() : '' }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ number_format($pos->latitude, 5) }}, {{ number_format($pos->longitude, 5) }}
                                </td>
                                <td class="py-3 px-4 font-sans font-semibold text-slate-900">
                                    {{ $pos->speed_kph ?? 0 }} km/h
                                </td>
                                <td class="py-3 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $pos->ignition ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $pos->ignition ? 'ON' : 'OFF' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    {{ $pos->heading ?? 0 }}°
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 font-sans text-xs">
                                    No position breadcrumbs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Command Audit Log -->
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Command History & Remote Actions</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Audit log of commands sent to this hardware tracker</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Command Type</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Dispatched At</th>
                            <th class="py-3 px-4">Acknowledged</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse ($device->commands as $cmd)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono">
                                    {{ $cmd->type }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $cmd->status === 'acknowledged' ? 'bg-emerald-100 text-emerald-700' : ($cmd->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $cmd->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $cmd->sent_at ? $cmd->sent_at->format('M d, H:i:s') : $cmd->created_at->format('M d, H:i:s') }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $cmd->acknowledged_at ? $cmd->acknowledged_at->diffForHumans() : 'Pending' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                                    No commands have been dispatched to this unit yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Vehicle, SIM & Specs (Col 4) -->
    <div class="lg:col-span-4 space-y-6">
        <!-- Bound Vehicle Card -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/90 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Bound Vehicle</h3>
                @if ($device->vehicle)
                    <form method="POST" action="{{ route('manage.devices.unbind', $device) }}" onsubmit="return confirm('Unbind this hardware device from vehicle {{ $device->vehicle->plate_number }}?')" class="inline">
                        @csrf
                        <button type="submit" class="text-[11px] font-bold text-rose-600 hover:text-rose-700 transition">
                            Unbind
                        </button>
                    </form>
                @endif
            </div>

            @if ($device->vehicle)
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 mb-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-extrabold text-slate-900 text-sm">{{ $device->vehicle->make }} {{ $device->vehicle->model }}</span>
                        <span class="text-xs font-mono font-bold bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700">
                            {{ $device->vehicle->year ?? '2022' }}
                        </span>
                    </div>
                    <div class="text-xs font-mono text-brand-600 font-bold mb-2">
                        {{ $device->vehicle->plate_number ?? $device->vehicle->license_plate ?? 'NO PLATE' }}
                    </div>

                    <div class="pt-2.5 border-t border-slate-200/60 space-y-1 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span class="text-slate-400">VIN:</span>
                            <span class="font-mono text-slate-800">{{ $device->vehicle->vin ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Color:</span>
                            <span class="text-slate-800">{{ $device->vehicle->color ?? 'Black' }}</span>
                        </div>
                    </div>
                </div>

                @php $owner = $device->vehicle->user; @endphp
                @if ($owner)
                    <div class="flex items-center justify-between pt-2">
                        <div>
                            <div class="text-[11px] text-slate-400 font-medium">Customer Account</div>
                            <div class="text-xs font-bold text-slate-900 mt-0.5">{{ $owner->name }}</div>
                            <div class="text-[11px] text-slate-500">{{ $owner->phone ?? $owner->email }}</div>
                        </div>
                        <a href="{{ route('manage.users.show', $owner) }}" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                            View User
                        </a>
                    </div>
                @endif
            @else
                <div class="p-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-center">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 grid place-items-center mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div class="text-xs font-bold text-slate-800">No Vehicle Bound</div>
                    <div class="text-[11px] text-slate-400 mt-0.5 mb-3">This hardware device is currently sitting in spare inventory stock.</div>
                    <a href="{{ route('manage.users.index') }}" class="px-3.5 py-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs shadow-sm">
                        Assign to Customer
                    </a>
                </div>
            @endif
        </div>

        <!-- SIM & Connectivity Specs Card -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200/90 shadow-sm">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">Hardware Specifications</h3>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400 font-medium">Hardware Model</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $device->model ?? 'GG402' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400 font-medium">Manufacturer</span>
                    <span class="font-bold text-slate-800">{{ $device->brand ?? 'AUTOSECURE' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400 font-medium">SIM Line Number</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $device->sim_number ?: 'Not assigned' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400 font-medium">Master SOS Phone</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $device->phone_number ?: 'Not assigned' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-400 font-medium">Protocol</span>
                    <span class="font-bold text-slate-800 font-mono">GT06 / Huabao</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400 font-medium">Firmware</span>
                    <span class="font-bold text-slate-800 font-mono">v4.2.1-SECURE</span>
                </div>
            </div>
        </div>

        <!-- Emergency Controls Card -->
        <div class="bg-slate-900 text-white rounded-3xl p-5 border border-slate-800 shadow-md">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white">Direct Terminal Commands</h3>
            </div>
            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                Direct commands are transmitted via SMS / TCP gateway to the physical hardware unit.
            </p>

            <div class="space-y-2.5">
                <form method="POST" action="{{ route('manage.devices.command', $device) }}">
                    @csrf
                    <input type="hidden" name="type" value="restart">
                    <button type="submit" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold border border-slate-700 transition text-left flex items-center justify-between">
                        <span>Reboot Device Hardware</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </form>

                <form method="POST" action="{{ route('manage.devices.command', $device) }}">
                    @csrf
                    <input type="hidden" name="type" value="locate">
                    <button type="submit" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold border border-slate-700 transition text-left flex items-center justify-between">
                        <span>Request Satellite Coordinates</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Device Modal -->
<div id="editDeviceModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-scale-up">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Edit Device Metadata</h3>
                <p class="text-xs text-slate-500 mt-0.5">IMEI: {{ $device->imei }}</p>
            </div>
            <button onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('manage.devices.update', $device) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nickname / Label</label>
                    <input type="text" name="label" value="{{ old('label', $device->label) }}" placeholder="e.g. GLK Tracker" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                        <option value="active" {{ $device->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="offline" {{ $device->status === 'offline' ? 'selected' : '' }}>Offline</option>
                        <option value="suspended" {{ $device->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="unbound" {{ $device->status === 'unbound' ? 'selected' : '' }}>Unbound</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ old('model', $device->model) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand', $device->brand) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SIM Phone Number</label>
                    <input type="text" name="sim_number" value="{{ old('sim_number', $device->sim_number) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Master SOS Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $device->phone_number) }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('editDeviceModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Initialize Leaflet Map -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $sensors['latitude'] }};
        const lng = {{ $sensors['longitude'] }};
        
        const map = L.map('deviceMap', {
            zoomControl: true,
            attributionControl: false
        }).setView([lat, lng], 16);

        // OpenStreetMap Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        // Custom Vehicle Marker Icon
        const carIcon = L.divIcon({
            className: 'custom-car-pin',
            html: `
                <div style="position: relative; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; width: 44px; height: 44px; background: rgba(249, 115, 22, 0.25); border-radius: 50%; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    <div style="position: relative; width: 34px; height: 34px; background: #111827; border: 2.5px solid #F97316; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                </div>
            `,
            iconSize: [44, 44],
            iconAnchor: [22, 22]
        });

        // Marker with Info Popup
        const marker = L.marker([lat, lng], { icon: carIcon }).addTo(map);
        marker.bindPopup(`
            <div style="font-family: inherit; padding: 4px; min-width: 180px;">
                <div style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 2px;">{{ $device->label ?: ($device->model ?: 'GPS Tracker') }}</div>
                <div style="font-size: 11px; color: #64748b; font-family: monospace;">IMEI: {{ $device->imei }}</div>
                <div style="margin-top: 6px; font-size: 11px; font-weight: 700; color: #16a34a;">● Live Online Fix</div>
            </div>
        `).openPopup();

        // Accuracy Circle
        L.circle([lat, lng], {
            color: '#F97316',
            fillColor: '#F97316',
            fillOpacity: 0.12,
            radius: 25
        }).addTo(map);

        // Adjust map size on container rendering
        setTimeout(() => { map.invalidateSize(); }, 300);
    });
</script>
@endsection
