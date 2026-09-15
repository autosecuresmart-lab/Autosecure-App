@extends('manage.layouts.app')

@section('title', $definition['title'])

@section('content')
    <h1>{{ $definition['title'] }}</h1>
    <p class="muted">{{ $definition['summary'] }}</p>

    <p>
        <span class="pill pill-neutral">{{ $definition['phase'] }}</span>
        <span class="pill pill-neutral">requires <code>{{ \App\Http\Controllers\Manage\ModuleController::permissionFor($module) }}</code></span>
    </p>

    <h2>Planned in this module</h2>
    <div class="card">
        <ul class="list">
            @foreach ($definition['features'] as $feature)
                <li>{{ $feature }}</li>
            @endforeach
        </ul>
    </div>

    <h2>Why this screen is empty</h2>
    <div class="card">
        <p>
            AUTOSECURE 2.0 is being delivered in phases. This module's database tables, models and
            permission boundary exist already, but the screens are built in the phase listed above.
            Nothing here is stubbed to look finished.
        </p>
        <p class="muted">
            See <code>docs/phase-1/ARCHITECTURE.md</code> for the module map and
            <code>docs/phase-1/PENDING-INFORMATION.md</code> for anything blocked on external input.
        </p>
    </div>
@endsection
