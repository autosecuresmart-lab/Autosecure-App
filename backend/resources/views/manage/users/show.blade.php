@extends('manage.layouts.app')

@section('title', $user->name . ' · User Profile')

@section('content')
<div class="space-y-6">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white grid place-items-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-start gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-red-500 text-white grid place-items-center shrink-0 mt-0.5">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="text-sm flex-1">
                <div class="font-bold mb-1">Please fix the following errors:</div>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Breadcrumbs & Navigation -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('manage.dashboard') }}" class="hover:text-slate-800 transition">Dashboard</a>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <a href="{{ route('manage.users.index') }}" class="hover:text-slate-800 transition">Users</a>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="text-slate-900 font-bold truncate max-w-[200px]">{{ $user->name }}</span>
        </div>

        <a href="{{ route('manage.users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Back to Users</span>
        </a>
    </div>

    <!-- Top User Overview Banner -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-950 text-white font-extrabold text-xl sm:text-2xl grid place-items-center shadow-md">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $user->name }}</h1>
                        
                        @if($user->status === 'active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Active
                            </span>
                        @elseif($user->status === 'suspended')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Suspended
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Pending
                            </span>
                        @endif

                        @if($user->account_type === 'business')
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold">
                                Business (Fleet)
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold">
                                Individual (Personal)
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 font-medium">
                        <span class="flex items-center gap-1">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            {{ $user->email }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            {{ $user->phone ?? 'No phone added' }}
                        </span>
                        <span>Member since {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Header Quick Action Buttons -->
            <div class="flex items-center gap-2">
                <button onclick="openEditUserModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-bold transition">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                    <span>Edit Profile</span>
                </button>

                <button onclick="openAddVehicleModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs sm:text-sm font-bold shadow-sm shadow-brand-500/20 transition">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>+ Add Vehicle</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- 2-COLUMN MAIN LAYOUT: COL 8 (LEFT) / COL 4 (RIGHT)   -->
    <!-- ==================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: COL-8 (Vehicles, Devices, Telemetry, Maintenance) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Card 1: Vehicles & Bound Hardware Devices -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span>{{ $user->account_type === 'business' ? 'Fleet Vehicles & Hardware' : 'Personal Vehicles & Hardware' }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-bold">
                                {{ $vehicles->count() }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500">
                            {{ $user->account_type === 'business' ? 'Connected business fleet and bound GPS trackers & AI dashcams' : 'Registered personal vehicles and bound GPS trackers & dashcams' }}
                        </p>
                    </div>

                    <button onclick="openAddVehicleModal()" class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700">
                        <span>+ Assign Vehicle</span>
                    </button>
                </div>

                @if($vehicles->count() > 0)
                    <div class="space-y-4">
                        @foreach($vehicles as $vehicle)
                            <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition">
                                <!-- Vehicle Header -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-900 text-white grid place-items-center shrink-0">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="1" y="3" width="15" height="13"></rect>
                                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900 text-sm sm:text-base">
                                                    {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                                                </span>
                                                @if($vehicle->is_primary)
                                                    <span class="px-2 py-0.5 rounded bg-brand-100 text-brand-700 text-[10px] font-bold">Primary</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span>{{ $vehicle->colour ?? 'Standard' }}</span>
                                                <span>•</span>
                                                <span>{{ $vehicle->fuel_type ?? 'Petrol' }}</span>
                                                <span>•</span>
                                                <span>{{ number_format($vehicle->odometer_km) }} km</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- License Plate Badge -->
                                    <div class="flex items-center gap-2">
                                        <div class="px-3 py-1 rounded-lg bg-yellow-400 border border-yellow-500 text-slate-900 font-extrabold font-mono text-xs tracking-wider shadow-sm">
                                            {{ $vehicle->plate_number }}
                                        </div>
                                        <button onclick="openSecurityCommandModal({{ $vehicle->id }}, '{{ $vehicle->plate_number }}')" class="p-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition" title="Security Command">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Bound Devices for this Vehicle -->
                                <div class="pt-3">
                                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Bound Hardware</div>
                                    @if($vehicle->devices->count() > 0)
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            @foreach($vehicle->devices as $device)
                                                <div class="p-3 rounded-xl bg-white border border-slate-200 flex items-center justify-between shadow-xs">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <div class="w-8 h-8 rounded-lg {{ $device->type === 'tracker' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600' }} grid place-items-center shrink-0">
                                                            @if($device->type === 'tracker')
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <circle cx="12" cy="12" r="10"></circle>
                                                                    <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
                                                                </svg>
                                                            @else
                                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M23 7l-7 5 7 5V7z"></path>
                                                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                                                </svg>
                                                            @endif
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="text-xs font-bold text-slate-800 truncate">
                                                                {{ $device->displayName() }}
                                                            </div>
                                                            <div class="text-[10px] text-slate-400 truncate">
                                                                SN: {{ $device->serial_number }} @if($device->imei)· IMEI: {{ $device->imei }}@endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        @if($device->is_online)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                                Online
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold">
                                                                Offline
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-white border border-dashed border-slate-300 text-xs text-slate-500">
                                            <span>No hardware device bound to this vehicle yet.</span>
                                            <button onclick="openAddDeviceModal({{ $vehicle->id }})" class="text-brand-600 font-bold hover:underline">
                                                + Bind Device
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center rounded-2xl border border-dashed border-slate-300 bg-slate-50">
                        <div class="w-12 h-12 rounded-2xl bg-slate-200 text-slate-500 grid place-items-center mx-auto mb-3">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="3" width="15" height="13"></rect>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">No Vehicles Assigned</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">This customer has not been assigned any vehicles yet. Click below to add their primary vehicle.</p>
                        <button onclick="openAddVehicleModal()" class="mt-4 inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition">
                            + Add Vehicle Now
                        </button>
                    </div>
                @endif
            </div>

            <!-- Card 2: Live Telemetry & GPS Tracking Card -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Live GPS & Telemetry Stream</h2>
                        <p class="text-xs text-slate-500">Real-time status of connected GPS trackers</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        Active Stream
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">GPS Status</div>
                        <div class="text-sm font-extrabold text-slate-900 mt-1 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            3D Fix (9 Satellites)
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Ignition / Engine</div>
                        <div class="text-sm font-extrabold text-slate-900 mt-1">
                            Key OFF / Parked
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Internal Battery</div>
                        <div class="text-sm font-extrabold text-emerald-600 mt-1">
                            12.8V (Normal)
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/10 grid place-items-center text-brand-500">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                                <line x1="8" y1="2" x2="8" y2="18"></line>
                                <line x1="16" y1="6" x2="16" y2="22"></line>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold">{{ $user->account_type === 'business' ? 'Lagos Fleet Zone 1 • Victoria Island' : 'Current Location • Victoria Island' }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">Lat: 6.5244° N, Long: 3.3792° E</div>
                        </div>
                    </div>

                    <a href="{{ route('manage.live-map.index') }}" class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-bold transition">
                        Open in Live Map →
                    </a>
                </div>
            </div>

            <!-- Card 3: Recent Maintenance & Care Logs -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Vehicle Care & Maintenance Records</h2>
                        <p class="text-xs text-slate-500">Service logs, oil changes, and scheduled maintenance</p>
                    </div>
                </div>

                @if($recentMaintenance->count() > 0)
                    <div class="divide-y divide-slate-100">
                        @foreach($recentMaintenance as $record)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 grid place-items-center shrink-0">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900">{{ $record->service_type ?? 'Routine Maintenance' }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ $record->vehicle?->displayName() }} • {{ $record->odometer_km ? number_format($record->odometer_km) . ' km' : 'General' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-bold text-slate-900">₦{{ number_format($record->cost ?? 0) }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $record->service_date ? $record->service_date->format('M d, Y') : $record->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl">
                        No maintenance records logged for this customer yet.
                    </div>
                @endif
            </div>

            <!-- Card 4: Incident & Security Alert Log -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Security & Theft Incident Log</h2>
                        <p class="text-xs text-slate-500">Alarms, geofence breaches, and recovery dispatches</p>
                    </div>
                </div>

                @if($recentTheftEvents->count() > 0)
                    <div class="space-y-2">
                        @foreach($recentTheftEvents as $event)
                            <div class="p-3.5 rounded-xl border border-red-100 bg-red-50/50 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-red-500 text-white grid place-items-center shrink-0">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-900">
                                            Theft Incident #{{ $event->id }} ({{ $event->vehicle?->plate_number }})
                                        </div>
                                        <div class="text-[11px] text-red-600 font-medium">Status: {{ ucfirst($event->status) }}</div>
                                    </div>
                                </div>
                                <div class="text-[11px] text-slate-400 font-medium">
                                    {{ $event->reported_at ? $event->reported_at->diffForHumans() : $event->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-5 rounded-xl bg-emerald-50/60 border border-emerald-100 text-emerald-800 flex items-center gap-3 text-xs">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>{{ $user->account_type === 'business' ? 'No active security threats or theft events reported. Fleet is secure.' : 'No active security threats or theft events reported. Personal vehicles are secure.' }}</span>
                    </div>
                @endif
            </div>

        </div>

        <!-- RIGHT COLUMN: COL-4 (Quick Actions, Subscriptions, Details) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Card 1: Quick Actions Panel -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400 mb-3">Quick Actions</h3>

                <div class="space-y-2">
                    <!-- Edit Profile -->
                    <button onclick="openEditUserModal()" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/80 text-xs font-bold text-slate-800 transition">
                        <div class="flex items-center gap-2.5">
                            <span class="text-brand-500">✏️</span>
                            <span>Edit Customer Profile</span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>

                    <!-- Assign Vehicle -->
                    <button onclick="openAddVehicleModal()" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/80 text-xs font-bold text-slate-800 transition">
                        <div class="flex items-center gap-2.5">
                            <span class="text-blue-500">🚗</span>
                            <span>Assign / Add Vehicle</span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>

                    <!-- Bind Device -->
                    <button onclick="openAddDeviceModal()" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/80 text-xs font-bold text-slate-800 transition">
                        <div class="flex items-center gap-2.5">
                            <span class="text-indigo-500">📡</span>
                            <span>Bind Hardware Device</span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>

                    <!-- Remote Security Command -->
                    <button onclick="openSecurityCommandModal()" class="w-full flex items-center justify-between p-3 rounded-xl bg-red-50 hover:bg-red-100 border border-red-200 text-xs font-bold text-red-700 transition">
                        <div class="flex items-center gap-2.5">
                            <span>🚨</span>
                            <span>Remote Security Command</span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-400">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>

                    <!-- Toggle Status -->
                    <form method="POST" action="{{ route('manage.users.status', $user) }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-between p-3 rounded-xl {{ $user->status === 'active' ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200' }} text-xs font-bold transition">
                            <div class="flex items-center gap-2.5">
                                <span>{{ $user->status === 'active' ? '⏸️' : '▶️' }}</span>
                                <span>{{ $user->status === 'active' ? 'Suspend Account' : 'Activate Account' }}</span>
                            </div>
                            <span class="text-[11px] uppercase font-bold">{{ $user->status === 'active' ? 'Freeze' : 'Unfreeze' }}</span>
                        </button>
                    </form>

                    <!-- Delete User Modal Trigger -->
                    <button onclick="openDeleteUserModal()" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-red-50 hover:text-red-700 border border-slate-200/80 text-xs font-bold text-slate-600 transition">
                        <div class="flex items-center gap-2.5">
                            <span>🗑️</span>
                            <span>Deactivate / Delete User</span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Card 2: Subscription & Loyalty Coins -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">Subscription & Rewards</h3>

                <!-- Active Plan -->
                <div class="p-4 rounded-xl bg-gradient-to-br from-slate-900 to-slate-950 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-brand-400 uppercase tracking-wide">AUTOSECURE Plan</span>
                        @if($activeSubscription)
                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-bold uppercase">
                                {{ $activeSubscription->status }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-white/10 text-slate-300 text-[10px] font-bold uppercase">
                                Standard (Free)
                            </span>
                        @endif
                    </div>
                    <div class="text-lg font-black tracking-tight">
                        {{ $activeSubscription ? ($activeSubscription->plan?->name ?? 'Premium Tier') : 'Basic Tier' }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                        {{ $activeSubscription ? 'Valid until ' . ($activeSubscription->expires_at ? $activeSubscription->expires_at->format('M d, Y') : 'Active') : 'Upgrade available in mobile app' }}
                    </div>
                </div>

                <!-- Coin Wallet -->
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white grid place-items-center font-bold text-sm shadow-sm">
                            🪙
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-900">autoSecure Coins</div>
                            <div class="text-[11px] text-amber-700">Loyalty & Referral balance</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-base font-extrabold text-amber-900">
                            {{ number_format($coinWallet?->balance ?? 250) }}
                        </div>
                        <div class="text-[10px] text-amber-600 font-semibold">Coins</div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Account & Security Metadata -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400 mb-3">Security & Metadata</h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-slate-400 font-medium">Customer UUID</div>
                        <div class="font-mono font-bold text-slate-700 break-all select-all mt-0.5">{{ $user->uuid }}</div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Two-Factor Auth</span>
                        <span class="font-bold {{ $user->two_factor_enabled ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ $user->two_factor_enabled ? 'Enabled (2FA)' : 'Disabled' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Biometric Login</span>
                        <span class="font-bold {{ $user->biometric_enabled ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ $user->biometric_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Timezone</span>
                        <span class="font-bold text-slate-700">{{ $user->timezone ?? 'Africa/Lagos' }}</span>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Last Login</span>
                        <span class="font-bold text-slate-700">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. EDIT USER MODAL                         -->
<!-- ========================================== -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeEditUserModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl w-full max-w-lg border border-slate-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h3 class="text-base font-bold text-slate-900">Edit Customer Profile</h3>
                <button onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form method="POST" action="{{ route('manage.users.update', $user) }}" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ $user->name }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ $user->email }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="{{ $user->phone }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Account Type</label>
                        <select name="account_type" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="individual" {{ $user->account_type === 'individual' ? 'selected' : '' }}>Individual</option>
                            <option value="business" {{ $user->account_type === 'business' ? 'selected' : '' }}>Business</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="pending" {{ $user->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company Name (If Business)</label>
                    <input type="text" name="company_name" value="{{ $user->company_name }}" placeholder="e.g. Apex Logistics" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reset Password (Leave blank to keep current)</label>
                    <input type="password" name="password" minlength="6" placeholder="New password" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 rounded-xl border bg-white text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. ADD VEHICLE MODAL                       -->
<!-- ========================================== -->
<div id="addVehicleModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAddVehicleModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl w-full max-w-lg border border-slate-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h3 class="text-base font-bold text-slate-900">Assign New Vehicle to {{ $user->name }}</h3>
                <button onclick="closeAddVehicleModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form method="POST" action="{{ route('manage.users.vehicles.store', $user) }}" class="p-6 space-y-3">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Make <span class="text-red-500">*</span></label>
                        <input type="text" name="make" required placeholder="e.g. Toyota" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Model <span class="text-red-500">*</span></label>
                        <input type="text" name="model" required placeholder="e.g. Land Cruiser" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Year <span class="text-red-500">*</span></label>
                        <input type="number" name="year" required value="2024" min="1950" max="2050" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">License Plate <span class="text-red-500">*</span></label>
                        <input type="text" name="plate_number" required placeholder="e.g. ABC-123-XY" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs uppercase">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Colour</label>
                        <input type="text" name="colour" placeholder="Black" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Fuel Type</label>
                        <select name="fuel_type" class="w-full px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="hybrid">Hybrid</option>
                            <option value="electric">Electric</option>
                            <option value="cng">CNG</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Odometer (km)</label>
                        <input type="number" name="odometer_km" placeholder="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">VIN Number (Chassis)</label>
                    <input type="text" name="vin" placeholder="17-digit VIN" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddVehicleModal()" class="px-4 py-2 rounded-xl border bg-white text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold">Register Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. ADD DEVICE MODAL                        -->
<!-- ========================================== -->
<div id="addDeviceModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAddDeviceModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl w-full max-w-lg border border-slate-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h3 class="text-base font-bold text-slate-900">Bind Telemetry Hardware Device</h3>
                <button onclick="closeAddDeviceModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form method="POST" action="{{ route('manage.users.devices.store', $user) }}" class="p-6 space-y-3">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Device Type <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="tracker">GPS Tracker (Coban/Teltonika)</option>
                            <option value="dashcam">AI Dashcam (4K / 2CH)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Assign to Vehicle</label>
                        <select name="vehicle_id" id="modalDeviceVehicleSelect" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="">-- No Vehicle (Inventory) --</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->plate_number }} ({{ $v->make }} {{ $v->model }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Brand <span class="text-red-500">*</span></label>
                        <input type="text" name="brand" required placeholder="e.g. Teltonika" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Model <span class="text-red-500">*</span></label>
                        <input type="text" name="model" required placeholder="e.g. FMB920" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Serial Number <span class="text-red-500">*</span></label>
                    <input type="text" name="serial_number" required placeholder="e.g. SN-88392019" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">IMEI Number</label>
                        <input type="text" name="imei" placeholder="15-digit IMEI" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">SIM Card Number</label>
                        <input type="text" name="sim_number" placeholder="+234..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddDeviceModal()" class="px-4 py-2 rounded-xl border bg-white text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Bind Hardware</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 4. SECURITY COMMAND MODAL                  -->
<!-- ========================================== -->
<div id="securityCommandModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeSecurityCommandModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl w-full max-w-lg border border-red-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-red-100 bg-red-50/70">
                <div class="flex items-center gap-2 text-red-700 font-bold text-base">
                    <span>🚨</span>
                    <span>Remote Security Dispatch</span>
                </div>
                <button onclick="closeSecurityCommandModal()" class="text-red-400 hover:text-red-600">✕</button>
            </div>
            <form method="POST" action="{{ route('manage.users.security-command', $user) }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Vehicle <span class="text-red-500">*</span></label>
                    <select name="vehicle_id" id="securityVehicleSelect" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->make }} {{ $v->model }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Command Action <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-red-200 bg-red-50/40 cursor-pointer">
                            <input type="radio" name="command_type" value="engine_cut" checked class="text-red-600 focus:ring-red-500">
                            <div>
                                <div class="text-xs font-bold text-red-900">Remote Engine Cutoff (Immobilize)</div>
                                <div class="text-[11px] text-red-600">Cuts vehicle fuel pump & relay via onboard tracker</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <input type="radio" name="command_type" value="engine_restore" class="text-slate-600 focus:ring-slate-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Restore Engine / Relay Normal</div>
                                <div class="text-[11px] text-slate-500">Enables ignition and normal starting</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <input type="radio" name="command_type" value="theft_alarm" class="text-slate-600 focus:ring-slate-500">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Trigger Theft Incident & Emergency Alert</div>
                                <div class="text-[11px] text-slate-500">Alerts recovery control room & pushes mobile alert</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason / Authorization Note</label>
                    <input type="text" name="reason" placeholder="e.g. Customer reported stolen vehicle #INC-99" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeSecurityCommandModal()" class="px-4 py-2 rounded-xl border bg-white text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm shadow-red-500/20">
                        Dispatch Command Immediately
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 5. DELETE USER CONFIRMATION MODAL          -->
<!-- ========================================== -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDeleteUserModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl w-full max-w-md border border-slate-200">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 grid place-items-center mx-auto mb-4">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Deactivate Customer Account</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Are you sure you want to deactivate <strong class="text-slate-800">{{ $user->name }}</strong> ({{ $user->email }})? 
                    This will disable login access and archive linked resources.
                </p>

                <div class="flex items-center justify-center gap-3 mt-6">
                    <button type="button" onclick="closeDeleteUserModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('manage.users.destroy', $user) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm shadow-red-500/20">
                            Yes, Deactivate User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditUserModal() {
        document.getElementById('editUserModal').classList.remove('hidden');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    function openAddVehicleModal() {
        document.getElementById('addVehicleModal').classList.remove('hidden');
    }
    function closeAddVehicleModal() {
        document.getElementById('addVehicleModal').classList.add('hidden');
    }

    function openAddDeviceModal(vehicleId = null) {
        if (vehicleId) {
            document.getElementById('modalDeviceVehicleSelect').value = vehicleId;
        }
        document.getElementById('addDeviceModal').classList.remove('hidden');
    }
    function closeAddDeviceModal() {
        document.getElementById('addDeviceModal').classList.add('hidden');
    }

    function openSecurityCommandModal(vehicleId = null, plate = '') {
        if (vehicleId) {
            document.getElementById('securityVehicleSelect').value = vehicleId;
        }
        document.getElementById('securityCommandModal').classList.remove('hidden');
    }
    function closeSecurityCommandModal() {
        document.getElementById('securityCommandModal').classList.add('hidden');
    }

    function openDeleteUserModal() {
        document.getElementById('deleteUserModal').classList.remove('hidden');
    }
    function closeDeleteUserModal() {
        document.getElementById('deleteUserModal').classList.add('hidden');
    }

    // Escape listener
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditUserModal();
            closeAddVehicleModal();
            closeAddDeviceModal();
            closeSecurityCommandModal();
            closeDeleteUserModal();
        }
    });
</script>
@endsection
