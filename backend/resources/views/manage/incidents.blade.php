@extends('manage.layouts.app')

@section('title', 'Incidents Management')

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8">
    <div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Active Incidents & Recovery</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Real-time theft mitigation, emergency telemetry and recovery dispatch</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('manage.live-map.index') }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 shadow-sm transition">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
            </svg>
            <span>Open Live Map</span>
        </a>
    </div>
</div>

<!-- Quick Stat Counters -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="text-xs font-semibold text-slate-500 mb-1">Active High-Priority Thefts</div>
        <div class="text-2xl sm:text-3xl font-extrabold {{ ($active_count ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">
            {{ number_format($active_count ?? 0) }} Vehicles
        </div>
        <div class="text-xs text-slate-400 mt-1">
            {{ ($active_count ?? 0) > 0 ? 'Live tracking and remote immobilisation active' : 'No active theft alarms reported' }}
        </div>
    </div>

    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="text-xs font-semibold text-slate-500 mb-1">Resolved Incidents</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600">
            {{ number_format($resolved_count ?? 0) }} Cases
        </div>
        <div class="text-xs text-slate-400 mt-1">Successfully mitigated and recovered</div>
    </div>

    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="text-xs font-semibold text-slate-500 mb-1">Total Incident History</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
            {{ number_format($total_count ?? 0) }} Total
        </div>
        <div class="text-xs text-slate-400 mt-1">Recorded across all registered vehicles</div>
    </div>
</div>

<!-- Filter Bar & Table Container -->
<div class="bg-white border border-slate-200/90 rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900">Theft & Security Log</h2>
            <p class="text-xs text-slate-400">Chronological list of all trigger events and dispatch operations</p>
        </div>

        <div class="text-xs text-slate-500 font-semibold">
            <span>Showing {{ count($incidents ?? []) }} record(s)</span>
        </div>
    </div>

    <!-- Responsive Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/70 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="py-3.5 px-5">Incident Ref</th>
                    <th class="py-3.5 px-5">Vehicle & Customer</th>
                    <th class="py-3.5 px-5">Hardware Device</th>
                    <th class="py-3.5 px-5">Last Known GPS</th>
                    <th class="py-3.5 px-5">Status</th>
                    <th class="py-3.5 px-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                @forelse ($incidents as $incident)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-4 px-5">
                            <div class="font-bold text-slate-900">INC-{{ substr($incident->id, 0, 8) }}</div>
                            <div class="text-[11px] text-slate-400">{{ $incident->triggered_at?->format('d M Y • H:i') ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="font-semibold text-slate-800">{{ $incident->vehicle?->make ?? 'Unknown' }} {{ $incident->vehicle?->model }}</div>
                            <div class="text-[11px] text-slate-400">
                                {{ $incident->vehicle?->license_plate ?? 'No Plate' }} • {{ $incident->user?->name ?? 'Unassigned' }}
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="font-medium text-slate-700">{{ $incident->device?->imei ?? 'No device' }}</div>
                            <div class="text-[11px] text-slate-400">{{ ucfirst($incident->device?->type ?? 'Tracker') }}</div>
                        </td>
                        <td class="py-4 px-5">
                            @if ($incident->last_known_latitude && $incident->last_known_longitude)
                                <div class="font-mono text-xs text-slate-700">{{ $incident->last_known_latitude }}, {{ $incident->last_known_longitude }}</div>
                            @else
                                <div class="text-slate-400 text-xs">No GPS recorded</div>
                            @endif
                        </td>
                        <td class="py-4 px-5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold uppercase {{ $incident->status === 'open' ? 'bg-red-50 border border-red-200 text-red-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-600' }}">
                                @if ($incident->status === 'open')
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                @endif
                                <span>{{ $incident->status }}</span>
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right">
                            @if ($incident->vehicle && $incident->user)
                                <a href="{{ route('manage.users.show', $incident->user) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold text-xs transition">
                                    <span>Inspect User</span>
                                </a>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 grid place-items-center mx-auto mb-3">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <polyline points="9 12 11 14 15 10"></polyline>
                                </svg>
                            </div>
                            <div class="text-sm font-bold text-slate-800">No security incidents reported</div>
                            <div class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                The system is clean with zero active theft alerts. All vehicles and telemetry devices are secure.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (isset($incidents) && method_exists($incidents, 'hasPages') && $incidents->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $incidents->links() }}
        </div>
    @endif
</div>
@endsection

