@extends('manage.layouts.app')

@section('title', 'Hardware & GPS Trackers')

@section('content')
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
            <span>Please correct the errors below:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Header Section -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-md bg-brand-50 border border-brand-200/60 text-brand-700 font-bold text-[10px] tracking-wider uppercase">Hardware Registry</span>
            <span class="text-slate-400 text-xs">•</span>
            <span class="text-slate-500 text-xs font-medium">{{ $stats['total_devices'] }} Registered Devices</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Devices & GPS Trackers</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage live vehicle telematics units, dashcams, SIM lines and remote relay hardware</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('manage.live-map.index') }}" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 hover:text-slate-900 shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg>
            <span>Live Fleet Map</span>
        </a>

        <button onclick="document.getElementById('registerDeviceModal').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Register Device</span>
        </button>
    </div>
</div>

<!-- Metrics Overview Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Inventory</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['total_devices'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">All provisioned units</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 grid place-items-center">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                <rect x="9" y="9" width="6" height="6"></rect>
                <line x1="9" y1="1" x2="9" y2="4"></line>
                <line x1="15" y1="1" x2="15" y2="4"></line>
                <line x1="9" y1="20" x2="9" y2="23"></line>
                <line x1="15" y1="20" x2="15" y2="23"></line>
                <line x1="20" y1="9" x2="23" y2="9"></line>
                <line x1="20" y1="14" x2="23" y2="14"></line>
                <line x1="1" y1="9" x2="4" y2="9"></line>
                <line x1="1" y1="14" x2="4" y2="14"></line>
            </svg>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active & Online</div>
            <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $stats['active_online'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Broadcasting telemetry</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">GPS Trackers</div>
            <div class="text-2xl font-extrabold text-sky-600 mt-1">{{ $stats['trackers'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">e.g. Model GG402</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 grid place-items-center">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="22" y1="12" x2="18" y2="12"></line>
                <line x1="6" y1="12" x2="2" y2="12"></line>
                <line x1="12" y1="6" x2="12" y2="2"></line>
                <line x1="12" y1="22" x2="12" y2="18"></line>
            </svg>
        </div>
    </div>

    <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Unbound Stock</div>
            <div class="text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['unbound'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Ready for installation</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 grid place-items-center">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm mb-6">
    <form method="GET" action="{{ route('manage.devices.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2 relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by IMEI, model, SIM, plate number or customer..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
        </div>

        <div>
            <select name="type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                <option value="">All Device Types</option>
                <option value="tracker" {{ ($filters['type'] ?? '') === 'tracker' ? 'selected' : '' }}>GPS Tracker</option>
                <option value="dashcam" {{ ($filters['type'] ?? '') === 'dashcam' ? 'selected' : '' }}>Dashcam</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <select name="status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="offline" {{ ($filters['status'] ?? '') === 'offline' ? 'selected' : '' }}>Offline</option>
                <option value="unbound" {{ ($filters['status'] ?? '') === 'unbound' ? 'selected' : '' }}>Unbound</option>
            </select>

            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition shrink-0">
                Filter
            </button>
            
            @if(!empty($filters['search']) || !empty($filters['type']) || !empty($filters['status']))
                <a href="{{ route('manage.devices.index') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition shrink-0" title="Clear filters">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Devices Table Card -->
<div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <th class="py-3.5 px-4">Device / Hardware</th>
                    <th class="py-3.5 px-4">Type</th>
                    <th class="py-3.5 px-4">Bound Vehicle</th>
                    <th class="py-3.5 px-4">Customer</th>
                    <th class="py-3.5 px-4">SIM / SOS Line</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse ($devices as $device)
                    <tr class="hover:bg-slate-50/80 transition group">
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl {{ $device->type === 'dashcam' ? 'bg-purple-50 text-purple-600' : 'bg-sky-50 text-sky-600' }} grid place-items-center shrink-0">
                                    @if ($device->type === 'dashcam')
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="22" y1="12" x2="18" y2="12"></line><line x1="6" y1="12" x2="2" y2="12"></line><line x1="12" y1="6" x2="12" y2="2"></line><line x1="12" y1="22" x2="12" y2="18"></line></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 group-hover:text-brand-600 transition flex items-center gap-1.5">
                                        <a href="{{ route('manage.devices.show', $device) }}">{{ $device->label ?: ($device->model ?: 'GPS Tracker') }}</a>
                                        <span class="px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded text-[10px] font-mono">{{ $device->model ?? 'GG402' }}</span>
                                    </div>
                                    <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                        IMEI: <span class="text-slate-700 font-semibold">{{ $device->imei }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="py-4 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $device->type === 'dashcam' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">
                                {{ $device->type }}
                            </span>
                        </td>

                        <td class="py-4 px-4">
                            @if ($device->vehicle)
                                <div class="font-bold text-slate-900">
                                    {{ $device->vehicle->make }} {{ $device->vehicle->model }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono">
                                    {{ $device->vehicle->plate_number ?? $device->vehicle->license_plate ?? 'N/A' }}
                                </div>
                            @else
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 text-[11px] font-medium italic">Unassigned</span>
                            @endif
                        </td>

                        <td class="py-4 px-4">
                            @php $owner = $device->vehicle?->user ?? $device->user; @endphp
                            @if ($owner)
                                <a href="{{ route('manage.users.show', $owner) }}" class="font-bold text-slate-900 hover:text-brand-600 transition block">
                                    {{ $owner->name }}
                                </a>
                                <div class="text-[11px] text-slate-400">{{ $owner->phone ?? $owner->email }}</div>
                            @else
                                <span class="text-slate-400 text-[11px]">Unlinked</span>
                            @endif
                        </td>

                        <td class="py-4 px-4 font-mono text-[11px]">
                            <div class="text-slate-800 font-medium">{{ $device->sim_number ?: 'No SIM recorded' }}</div>
                            <div class="text-slate-400 text-[10px]">SOS: {{ $device->phone_number ?: 'N/A' }}</div>
                        </td>

                        <td class="py-4 px-4">
                            @if ($device->status === 'active')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Active</span>
                                </span>
                            @elseif ($device->status === 'offline')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[11px] font-semibold border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    <span>Offline</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-[11px] font-semibold border border-amber-200">
                                    <span>{{ ucfirst($device->status) }}</span>
                                </span>
                            @endif
                        </td>

                        <td class="py-4 px-4 text-right">
                            <a href="{{ route('manage.devices.show', $device) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition">
                                <span>Inspect</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 px-4 text-center">
                            <div class="max-w-sm mx-auto">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 grid place-items-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 mb-1">No hardware devices found</h3>
                                <p class="text-xs text-slate-400 mb-4">No devices matched your query or filters. You can clear filters or register new inventory.</p>
                                <button onclick="document.getElementById('registerDeviceModal').classList.remove('hidden')" class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs shadow-sm">
                                    + Register New Device
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($devices->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $devices->links() }}
        </div>
    @endif
</div>

<!-- Register Device Modal -->
<div id="registerDeviceModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 animate-scale-up">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Register Hardware Device</h3>
                <p class="text-xs text-slate-500 mt-0.5">Add a new GPS tracker or dashcam to operational stock</p>
            </div>
            <button onclick="document.getElementById('registerDeviceModal').classList.add('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('manage.devices.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Device IMEI <span class="text-rose-500">*</span></label>
                <input type="text" name="imei" required placeholder="e.g. 865167042871039" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Device Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                        <option value="tracker">GPS Tracker</option>
                        <option value="dashcam">Dashcam</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Model</label>
                    <input type="text" name="model" value="GG402" placeholder="e.g. GG402" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Brand / Manufacturer</label>
                    <input type="text" name="brand" value="AUTOSECURE" placeholder="e.g. AUTOSECURE" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Device Nickname / Label</label>
                    <input type="text" name="label" placeholder="e.g. Fleet Tracker 01" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">SIM Phone Number</label>
                    <input type="text" name="sim_number" placeholder="e.g. 0700079956" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Master SOS Number</label>
                    <input type="text" name="phone_number" placeholder="e.g. 09169860996" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('registerDeviceModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    Save Device
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
