@extends('manage.layouts.app')

@section('title', 'Forbidden')

@section('content')
    <h1>Not permitted</h1>
    <p class="muted">Your staff account does not hold the permission required for this action.</p>

    <div class="card">
        <p>Required permission:</p>
        <ul class="list">
            @foreach ($required as $permission)
                <li><code>{{ $permission }}</code></li>
            @endforeach
        </ul>
        <p class="muted">
            Ask an AUTOSECURE administrator to grant the matching role, or to review the audit trail
            if you believe this is unexpected.
        </p>
    </div>
@endsection
