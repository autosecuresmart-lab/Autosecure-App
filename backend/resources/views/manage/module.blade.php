@extends('manage.layouts.app')

@section('title', $definition['title'])

@section('content')
<!-- Module Header -->
<div class="mb-6 sm:mb-8">
    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $definition['title'] }}</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-1">{{ $definition['summary'] }}</p>

    <div class="flex flex-wrap items-center gap-2 mt-3.5">
        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
            {{ $definition['phase'] }}
        </span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
            Permission: <code class="ml-1 font-mono text-[11px]">{{ \App\Http\Controllers\Manage\ModuleController::permissionFor($module) }}</code>
        </span>
    </div>
</div>

<!-- Planned Features Box -->
<div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm mb-6">
    <div class="text-sm sm:text-base font-bold text-slate-900 mb-4">Planned in this module</div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($definition['features'] as $feature)
            <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                <svg class="text-emerald-500 shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>{{ $feature }}</span>
            </div>
        @endforeach
    </div>
</div>

<!-- Architecture & Status Box -->
<div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-sm">
    <div class="text-sm sm:text-base font-bold text-slate-900 mb-2">Module Readiness</div>
    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-3">
        AUTOSECURE 2.0 is structured in phased rollouts. This module's database schemas, permission boundaries, and API interfaces are defined. High-speed interactive views and telematics pipeline integration will connect during the designated module rollout.
    </p>
    <div class="text-xs text-slate-400">
        Architecture documentation available in <code class="px-1.5 py-0.5 rounded bg-slate-100 font-mono text-slate-600">docs/phase-1/ARCHITECTURE.md</code>.
    </div>
</div>
@endsection
