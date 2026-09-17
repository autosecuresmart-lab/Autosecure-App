@extends('manage.layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8">
    <div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Dashboard Overview</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Platform monitoring, user operations and active security state</p>
    </div>
    
    <div class="flex items-center gap-2.5">
        <div class="inline-flex items-center gap-2 bg-white border border-slate-200 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 shadow-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>{{ $overview['date_label'] }}</span>
        </div>

        <a href="{{ route('manage.users.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs sm:text-sm font-bold shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Manage Users</span>
        </a>
    </div>
</div>


<!-- 4 Top Metric Cards (Responsive Grid) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-6 sm:mb-8">
    <!-- Card 1: Total Users -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500">Total Users</span>
                <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </span>
            </div>
            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-none mb-2">
                {{ number_format($overview['total_users']) }}
            </div>
        </div>
        <div class="text-xs text-slate-500 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
            <span>{{ $overview['individual_users'] }} Individual</span>
            <span>•</span>
            <span>{{ $overview['business_users'] }} Business</span>
        </div>
    </div>

    <!-- Card 2: Vehicles Registered -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500">Registered Vehicles</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </span>
            </div>
            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-none mb-2">
                {{ number_format($overview['total_vehicles']) }}
            </div>
        </div>
        <div class="text-xs text-slate-500 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
            <span>{{ $overview['total_devices'] }} Devices Bound</span>
            <span class="font-bold text-emerald-600">{{ $overview['active_devices'] }} Active</span>
        </div>
    </div>

    <!-- Card 3: Active Incidents / Thefts -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500">Active Security Thefts</span>
                <span class="p-1.5 rounded-lg bg-red-50 text-red-600">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </span>
            </div>
            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-none mb-2 {{ $overview['open_incidents'] > 0 ? 'text-red-600' : 'text-slate-900' }}">
                {{ number_format($overview['open_incidents']) }}
            </div>
        </div>
        <div class="text-xs text-slate-500 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
            <span>{{ $overview['resolved_incidents'] }} Resolved</span>
            <span class="font-bold text-emerald-600">Perimeter Normal</span>
        </div>
    </div>

    <!-- Card 4: Hardware Commands & Telemetry -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-500">Hardware Commands</span>
                <span class="p-1.5 rounded-lg bg-orange-50 text-orange-600">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                </span>
            </div>
            <div class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-none mb-2">
                {{ number_format($overview['total_commands']) }}
            </div>
        </div>
        <div class="text-xs text-slate-500 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
            <span>GPS, Relays & Alerts</span>
            <span class="font-bold text-blue-600">{{ $stats['payments_successful'] }} Paid Orders</span>
        </div>
    </div>
</div>

<!-- Main 2-Column Analytics Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Left Section (Col-Span 2): Registration & Activity Trends Chart Card -->
    <div class="lg:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
        <!-- Chart Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <div class="text-base font-bold text-slate-900">
                    Platform Growth & Activity <span class="text-slate-400 font-normal text-sm">(Last 7 Days)</span>
                </div>
                <div class="text-xs text-slate-400 mt-0.5">Daily onboarding velocity for customers and linked vehicles</div>
            </div>
            
            <div class="flex items-center gap-4 text-xs font-semibold text-slate-600">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span>Users</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Vehicles</span>
                </div>
            </div>
        </div>

        <!-- Dynamic Bars Visualization -->
        <div class="w-full min-h-[220px] flex flex-col justify-end">
            @php
                $maxVal = max(1, max(array_merge($chart_data['users'], $chart_data['vehicles'])));
            @endphp
            <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-44 pb-3 border-b border-slate-100">
                @foreach ($chart_data['days'] as $idx => $day)
                    @php
                        $uVal = $chart_data['users'][$idx] ?? 0;
                        $vVal = $chart_data['vehicles'][$idx] ?? 0;
                        $uHeight = max(6, round(($uVal / $maxVal) * 100));
                        $vHeight = max(6, round(($vVal / $maxVal) * 100));
                    @endphp
                    <div class="flex flex-col items-center gap-1.5 h-full justify-end">
                        <div class="w-full flex items-end justify-center gap-1 h-36">
                            <!-- User bar -->
                            <div class="w-3 sm:w-4 bg-blue-500 rounded-t-md transition-all duration-300 relative group" style="height: {{ $uVal > 0 ? $uHeight : 4 }}%;">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-20">
                                    {{ $uVal }} users
                                </span>
                            </div>
                            <!-- Vehicle bar -->
                            <div class="w-3 sm:w-4 bg-emerald-500 rounded-t-md transition-all duration-300 relative group" style="height: {{ $vVal > 0 ? $vHeight : 4 }}%;">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-20">
                                    {{ $vVal }} vehicles
                                </span>
                            </div>
                        </div>
                        <span class="text-[11px] font-medium text-slate-400">{{ $day }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400 pt-3">
                <span>Real-time aggregate data</span>
                <span>Max peak: {{ $maxVal }}</span>
            </div>
        </div>
    </div>

    <!-- Right Section (Col-Span 1): Recent Incidents & Security State Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="text-base font-bold text-slate-900">Security & Alerts</div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $overview['open_incidents'] > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $overview['open_incidents'] > 0 ? 'bg-red-500 animate-ping' : 'bg-emerald-500' }}"></span>
                    {{ $overview['open_incidents'] > 0 ? $overview['open_incidents'] . ' Active' : 'Normal' }}
                </span>
            </div>

            <div class="space-y-3">
                @forelse ($recent_incidents as $inc)
                    <div class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-white hover:border-slate-200 hover:shadow-sm transition">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-slate-900">INC-{{ substr($inc->id, 0, 8) }}</span>
                            <span class="text-xs text-slate-400 font-medium">{{ $inc->triggered_at?->diffForHumans() ?? 'Just now' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">{{ $inc->vehicle?->make ?? 'Vehicle' }} {{ $inc->vehicle?->model }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $inc->status === 'open' ? 'bg-red-100 text-red-600' : 'bg-slate-200 text-slate-700' }} uppercase tracking-wide">
                                {{ $inc->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 rounded-xl border border-dashed border-slate-200 bg-slate-50/50 text-center flex flex-col items-center justify-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 grid place-items-center mb-2">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                <polyline points="9 12 11 14 15 10"></polyline>
                            </svg>
                        </div>
                        <div class="text-xs font-bold text-slate-700">No active incidents</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">All monitored hardware and vehicles operating securely.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <a href="{{ route('manage.incidents.index') }}" class="mt-4 w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 font-bold text-xs sm:text-sm text-center block transition">
            View All Incidents
        </a>
    </div>
</div>

<!-- Recent Onboarded Users & Quick Navigation Table -->
<div class="bg-white border border-slate-200/90 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <div class="text-base font-bold text-slate-900">Recently Registered Users</div>
            <div class="text-xs text-slate-400">Latest customer accounts created on the platform</div>
        </div>

        <a href="{{ route('manage.users.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
            <span>View All Users</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-5">User</th>
                    <th class="py-3 px-5">Account Type</th>
                    <th class="py-3 px-5">Vehicles</th>
                    <th class="py-3 px-5">Devices</th>
                    <th class="py-3 px-5">Status</th>
                    <th class="py-3 px-5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse ($recent_users as $u)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-5">
                            <div class="font-bold text-slate-900">{{ $u->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $u->email }} • {{ $u->phone ?? 'No phone' }}</div>
                        </td>
                        <td class="py-3.5 px-5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $u->isBusiness() ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $u->isBusiness() ? 'Business' : 'Individual' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 font-semibold text-slate-700">
                            {{ $u->vehicles_count }}
                        </td>
                        <td class="py-3.5 px-5 font-semibold text-slate-700">
                            {{ $u->devices_count }}
                        </td>
                        <td class="py-3.5 px-5">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold {{ $u->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $u->status === 'active' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ ucfirst($u->status ?? 'active') }}
                            </span>
                        </td>
                        <td class="py-3.5 px-5 text-right">
                            <a href="{{ route('manage.users.show', $u) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800">
                                <span>View</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <div class="text-sm font-semibold text-slate-700">No users registered yet</div>
                            <div class="text-xs text-slate-400 mt-1">Start by creating your first individual or business customer account.</div>
                            <a href="{{ route('manage.users.index') }}" class="inline-block mt-3 px-4 py-2.5 bg-brand-500 text-white rounded-xl font-bold text-xs hover:bg-brand-600 shadow-sm shadow-brand-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                                Create New User
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection


