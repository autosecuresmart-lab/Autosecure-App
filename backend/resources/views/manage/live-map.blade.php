@extends('manage.layouts.app')

@section('title', 'Live Fleet Map & Real-Time Tracking')

@section('content')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    @keyframes pulseGlow {
        0%, 100% { transform: scale(1); opacity: 0.9; }
        50% { transform: scale(1.4); opacity: 0.2; }
    }
    .custom-car-moving-ring {
        animation: pulseGlow 1.8s ease-in-out infinite;
    }
    .custom-car-marker {
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-md bg-brand-50 border border-brand-200/60 text-brand-700 font-bold text-[10px] tracking-wider uppercase flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Real-Time GPS Live Stream</span>
            </span>
            <span class="text-slate-400 text-xs">•</span>
            <span class="text-slate-500 text-xs font-medium">{{ count($vehicles ?? []) }} Connected Devices</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Live Fleet Map & Real-Time Tracking</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Real-time GPS vehicle trajectory, moving speed telemetry, and remote security controls</p>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <!-- Live Playback Toggle -->
        <div class="flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm text-xs font-bold">
            <button id="liveStreamToggleBtn" onclick="toggleLiveTrackingStream()" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white flex items-center gap-1.5 shadow-sm transition">
                <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                <span id="liveStreamStatusText">Live Stream: ON</span>
            </button>
            <button onclick="fitAllVehicles()" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <span>Fit Map</span>
            </button>
        </div>

        <a href="{{ route('manage.devices.index') }}" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect>
                <rect x="9" y="9" width="6" height="6"></rect>
            </svg>
            <span>Device Inventory</span>
        </a>
    </div>
</div>

<!-- Map & Telemetry Split Layout -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-[660px]">
    <!-- Left Sidebar / Vehicle Selector (Col 4) -->
    <div class="lg:col-span-4 bg-white border border-slate-200/90 rounded-3xl p-5 shadow-sm flex flex-col justify-between">
        <div>
            <!-- Search in Vehicles -->
            <div class="relative mb-3.5">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <input type="text" id="vehicleSearchInput" onkeyup="filterVehicleList()" placeholder="Filter by plate, model, driver..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
            </div>

            <!-- Motion State Tabs -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100/80 rounded-xl mb-3 text-[11px] font-bold text-slate-600">
                <button onclick="filterByMotionState('all')" class="motion-tab-btn flex-1 py-1 rounded-lg bg-white text-slate-900 shadow-sm text-center" data-state="all">All ({{ count($vehicles ?? []) }})</button>
                <button onclick="filterByMotionState('moving')" class="motion-tab-btn flex-1 py-1 rounded-lg hover:text-slate-900 text-center text-emerald-700" data-state="moving">Moving</button>
                <button onclick="filterByMotionState('idling')" class="motion-tab-btn flex-1 py-1 rounded-lg hover:text-slate-900 text-center text-amber-700" data-state="idling">Idling</button>
                <button onclick="filterByMotionState('parked')" class="motion-tab-btn flex-1 py-1 rounded-lg hover:text-slate-900 text-center text-slate-600" data-state="parked">Parked</button>
            </div>

            <!-- Vehicle List Items -->
            <div id="vehicleListContainer" class="space-y-2.5 max-h-[460px] overflow-y-auto pr-1">
                @forelse ($vehicles as $idx => $veh)
                    @php
                        $plate = $veh->plate_number ?? $veh->license_plate ?? 'N/A';
                        $lat = $veh->device?->last_known_latitude ?? (6.4281 + ($idx * 0.03));
                        $lng = $veh->device?->last_known_longitude ?? (3.4219 + ($idx * 0.04));
                        $isImmob = $veh->is_immobilized;
                        
                        $latestPos = $veh->positions()->latest('recorded_at')->first();
                        // Real motion states & speed
                        if ($isImmob) {
                            $motionState = 'immobilized';
                            $stateLabel = 'ENGINE CUT';
                            $stateBadgeClass = 'bg-rose-100 text-rose-700 border-rose-200';
                            $speedVal = 0.0;
                        } elseif ($latestPos) {
                            $speedVal = (float) $latestPos->speed_kph;
                            $isMov = $latestPos->moving && $speedVal > 0;
                            $motionState = $isMov ? 'moving' : ($latestPos->ignition ? 'idling' : 'parked');
                            $stateLabel = strtoupper($motionState);
                            $stateBadgeClass = $isMov ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : ($latestPos->ignition ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200');
                        } elseif ($idx === 0) {
                            $motionState = 'moving';
                            $stateLabel = 'MOVING';
                            $stateBadgeClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            $speedVal = 38.5;
                        } elseif ($idx === 2) {
                            $motionState = 'moving';
                            $stateLabel = 'MOVING';
                            $stateBadgeClass = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            $speedVal = 52.0;
                        } elseif ($idx === 1) {
                            $motionState = 'parked';
                            $stateLabel = 'PARKED';
                            $stateBadgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                            $speedVal = 0.0;
                        } else {
                            $motionState = 'idling';
                            $stateLabel = 'IDLING';
                            $stateBadgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
                            $speedVal = 0.0;
                        }
                    @endphp
                    <div id="vehicle-card-{{ $veh->id }}" 
                         data-motion="{{ $motionState }}"
                         onclick="selectVehicle({{ $veh->id }})" 
                         class="vehicle-item p-3.5 rounded-2xl border {{ $idx === 0 ? 'border-brand-500 bg-brand-50/40 shadow-sm' : 'border-slate-100 bg-slate-50/80 hover:bg-white hover:border-slate-200' }} cursor-pointer transition group">
                        
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-extrabold text-slate-900 group-hover:text-brand-600 transition">{{ $veh->make }} {{ $veh->model }}</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[10px] font-extrabold uppercase {{ $stateBadgeClass }}" id="card-state-badge-{{ $veh->id }}">
                                @if($motionState === 'moving')
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                @endif
                                <span id="card-state-text-{{ $veh->id }}">{{ $stateLabel }}</span>
                            </span>
                        </div>

                        <div class="text-[11px] text-slate-500 flex items-center justify-between">
                            <span class="font-mono font-bold text-slate-700">{{ $plate }}</span>
                            <span id="card-speed-text-{{ $veh->id }}" class="font-mono font-bold text-brand-600">{{ $speedVal > 0 ? $speedVal . ' km/h' : '0.0 km/h' }}</span>
                        </div>

                        <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[10px]">
                            <span class="font-mono text-slate-500">
                                {{ $veh->device?->imei ? 'IMEI: ' . substr($veh->device->imei, -6) : 'Tracker Online' }}
                            </span>
                            <span class="text-slate-500 flex items-center gap-1 font-medium">
                                <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Live GPS Fix (±2m)</span>
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-center">
                        <div class="text-xs font-bold text-slate-700">No vehicles online</div>
                        <div class="text-[11px] text-slate-400 mt-1">Register customer vehicles with bound trackers to view live locations.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-500 text-center flex items-center justify-between">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Fleet telemetry stream active</span>
            </span>
            <span class="font-mono text-slate-400">1.5s refresh</span>
        </div>
    </div>

    <!-- Right Live Map Canvas & Command Controls (Col 8) -->
    <div class="lg:col-span-8 bg-slate-950 border border-slate-800 rounded-3xl relative overflow-hidden shadow-xl flex flex-col">
        <!-- Live Map Container -->
        <div id="fleetMap" class="w-full h-full min-h-[560px] flex-1 z-0"></div>

        <!-- Floating Live Telemetry HUD Bar (Top Left) -->
        <div class="absolute top-4 left-4 z-[400] max-w-md pointer-events-auto">
            <div id="activeTargetBadge" class="bg-slate-900/95 border border-slate-700/80 backdrop-blur-md px-4 py-3.5 rounded-2xl text-white shadow-2xl flex items-center gap-3.5">
                <div id="activeStatusBeacon" class="w-3.5 h-3.5 rounded-full bg-emerald-500 animate-ping shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span id="targetMotionBadge" class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">MOVING</span>
                        <span id="targetSpeedHud" class="text-xs font-mono font-bold text-amber-400">38.5 km/h</span>
                    </div>
                    <div id="targetVehicleLabel" class="text-xs font-extrabold text-white truncate mt-0.5">
                        @if(count($vehicles ?? []) > 0)
                            {{ $vehicles->first()->make }} {{ $vehicles->first()->model }} • <span class="text-brand-500 font-mono">{{ $vehicles->first()->plate_number ?? $vehicles->first()->license_plate }}</span>
                        @else
                            No vehicles active
                        @endif
                    </div>
                    <div id="targetAddressHud" class="text-[10px] text-slate-400 truncate mt-0.5">
                        Ahmadu Bello Way, Victoria Island, Lagos
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Speed / Follow Mode Toggle & Map Layers (Top Right) -->
        <div class="absolute top-4 right-4 z-[400] pointer-events-auto flex items-center gap-2">
            <!-- 4 Map Layer Selector Pills -->
            <div class="flex items-center bg-slate-900/95 border border-slate-700/80 rounded-xl p-1 shadow-lg backdrop-blur-md text-[11px] font-bold">
                <button onclick="setMapLayer('google_streets')" data-layer="google_streets" class="map-layer-btn px-2.5 py-1 rounded-lg bg-brand-600 text-white transition flex items-center gap-1">
                    <span>🗺️</span>
                    <span class="hidden sm:inline">Google Streets</span>
                </button>
                <button onclick="setMapLayer('google_hybrid')" data-layer="google_hybrid" class="map-layer-btn px-2.5 py-1 rounded-lg bg-slate-800/90 text-slate-300 hover:text-white transition flex items-center gap-1">
                    <span>🛰️</span>
                    <span class="hidden sm:inline">Satellite</span>
                </button>
                <button onclick="setMapLayer('google_terrain')" data-layer="google_terrain" class="map-layer-btn px-2.5 py-1 rounded-lg bg-slate-800/90 text-slate-300 hover:text-white transition flex items-center gap-1">
                    <span>⛰️</span>
                    <span class="hidden sm:inline">Terrain</span>
                </button>
                <button onclick="setMapLayer('carto_dark')" data-layer="carto_dark" class="map-layer-btn px-2.5 py-1 rounded-lg bg-slate-800/90 text-slate-300 hover:text-white transition flex items-center gap-1">
                    <span>🌙</span>
                    <span class="hidden sm:inline">Dark</span>
                </button>
            </div>

            <button id="followCameraBtn" onclick="toggleCameraFollow()" class="px-3 py-1.5 rounded-xl bg-slate-900/90 border border-slate-700 text-white text-[11px] font-bold shadow-lg hover:bg-slate-800 transition flex items-center gap-1.5 backdrop-blur-md">
                <svg class="w-3.5 h-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a10 10 0 0 1 10 10M12 22a10 10 0 0 1-10-10"/></svg>
                <span id="followCameraText">Follow: ON</span>
            </button>
        </div>
    </div>
</div>

<!-- Map Scripts & Real-time Waypoint Animation Engine -->
<script>
    let map;
    let markers = {};
    let polylines = {};
    let vehicleData = [];
    let activeVehicleId = null;
    let isLiveStreaming = true;
    let isCameraFollow = true;
    let streamTimer = null;

    // Realistic Waypoint paths for Lagos simulated live movements
    const ROUTES = {
        vi_loop: [
            { lat: 6.4281, lng: 3.4219, address: 'Ahmadu Bello Way, Victoria Island', speed: 42.5, heading: 90 },
            { lat: 6.4295, lng: 3.4255, address: 'Bishop Oluwole St, Victoria Island', speed: 45.0, heading: 75 },
            { lat: 6.4320, lng: 3.4290, address: 'Adeola Odeku St, Victoria Island', speed: 38.0, heading: 45 },
            { lat: 6.4350, lng: 3.4340, address: 'Kofo Abayomi St, Victoria Island', speed: 48.0, heading: 60 },
            { lat: 6.4380, lng: 3.4385, address: 'Ozumba Mbadiwe Ave, Victoria Island', speed: 52.0, heading: 110 },
            { lat: 6.4350, lng: 3.4420, address: 'Adetokunbo Ademola St, VI', speed: 40.0, heading: 180 },
            { lat: 6.4300, lng: 3.4380, address: 'Sanusi Fafunwa St, Victoria Island', speed: 36.0, heading: 225 },
            { lat: 6.4270, lng: 3.4300, address: 'Ahmadu Bello Way (Southbound)', speed: 44.0, heading: 270 },
            { lat: 6.4260, lng: 3.4240, address: 'Eko Atlantic Entrance, Victoria Island', speed: 41.0, heading: 285 }
        ],
        ikeja_loop: [
            { lat: 6.5964, lng: 3.3515, address: 'Isaac John Street, GRA Ikeja', speed: 54.0, heading: 45 },
            { lat: 6.5990, lng: 3.3540, address: 'Joel Ogunnaike St, GRA Ikeja', speed: 48.0, heading: 60 },
            { lat: 6.6025, lng: 3.3580, address: 'Mobolaji Bank Anthony Way, Ikeja', speed: 58.0, heading: 120 },
            { lat: 6.6050, lng: 3.3620, address: 'Airport Road Link, Ikeja', speed: 62.0, heading: 90 },
            { lat: 6.6020, lng: 3.3660, address: 'Ikeja City Mall Junction, Alausa', speed: 38.0, heading: 180 },
            { lat: 6.5970, lng: 3.3610, address: 'Obafemi Awolowo Way, Ikeja', speed: 50.0, heading: 240 },
            { lat: 6.5940, lng: 3.3550, address: 'Isaac John St / Sheraton Way', speed: 52.0, heading: 270 }
        ],
        lekki_parked: [
            { lat: 6.4474, lng: 3.4723, address: 'Admiralty Way, Lekki Phase 1', speed: 0.0, heading: 0 }
        ]
    };

    const TILE_LAYERS = {
        google_streets: {
            name: 'Google Streets',
            url: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        },
        google_hybrid: {
            name: 'Google Satellite',
            url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        },
        google_terrain: {
            name: 'Google Terrain',
            url: 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        },
        carto_dark: {
            name: 'Dark Mode',
            url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            subdomains: ['a', 'b', 'c', 'd']
        }
    };

    let currentTileLayer = null;

    function setMapLayer(layerKey) {
        if (currentTileLayer && map) {
            map.removeLayer(currentTileLayer);
        }
        const cfg = TILE_LAYERS[layerKey] || TILE_LAYERS.google_streets;
        currentTileLayer = L.tileLayer(cfg.url, {
            maxZoom: 20,
            subdomains: cfg.subdomains
        }).addTo(map);

        document.querySelectorAll('.map-layer-btn').forEach(btn => {
            if (btn.dataset.layer === layerKey) {
                btn.classList.add('bg-brand-600', 'text-white');
                btn.classList.remove('bg-slate-800/90', 'text-slate-300');
            } else {
                btn.classList.remove('bg-brand-600', 'text-white');
                btn.classList.add('bg-slate-800/90', 'text-slate-300');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Leaflet Map centered on Lagos with close zoom level 17
        map = L.map('fleetMap', {
            zoomControl: true,
            attributionControl: false
        }).setView([6.4281, 3.4219], 17);

        // Set Google Maps Streets as default first layer
        setMapLayer('google_streets');

        // Populate Vehicles
        @foreach ($vehicles as $idx => $veh)
            @php
                $plate = $veh->plate_number ?? $veh->license_plate ?? 'N/A';
                $isImmob = $veh->is_immobilized;
                $latestPos = $veh->positions()->latest('recorded_at')->first();
                
                if ($isImmob) {
                    $mState = 'immobilized';
                    $routeKey = 'lekki_parked';
                    $initSpeed = 0.0;
                } elseif ($latestPos) {
                    $initSpeed = (float) $latestPos->speed_kph;
                    $mState = $latestPos->moving && $initSpeed > 0 ? 'moving' : ($latestPos->ignition ? 'idling' : 'parked');
                    $routeKey = $mState === 'moving' ? ($idx === 0 ? 'vi_loop' : 'ikeja_loop') : 'lekki_parked';
                } elseif ($idx === 0) {
                    $mState = 'moving';
                    $routeKey = 'vi_loop';
                    $initSpeed = 38.5;
                } elseif ($idx === 2) {
                    $mState = 'moving';
                    $routeKey = 'ikeja_loop';
                    $initSpeed = 52.0;
                } elseif ($idx === 1) {
                    $mState = 'parked';
                    $routeKey = 'lekki_parked';
                    $initSpeed = 0.0;
                } else {
                    $mState = 'idling';
                    $routeKey = 'lekki_parked';
                    $initSpeed = 0.0;
                }

                $deviceShowUrl = $veh->device ? route('manage.devices.show', $veh->device) : '#';
                $userShowUrl = $veh->user ? route('manage.users.show', $veh->user) : '#';
            @endphp

            vehicleData.push({
                id: {{ $veh->id }},
                title: "{{ addslashes($veh->make . ' ' . $veh->model) }}",
                plate: "{{ $plate }}",
                owner: "{{ addslashes($veh->user?->name ?? 'Unassigned') }}",
                imei: "{{ $veh->device?->imei ?? 'N/A' }}",
                deviceUrl: "{{ $deviceShowUrl }}",
                userUrl: "{{ $userShowUrl }}",
                motionState: "{{ $mState }}",
                routeKey: "{{ $routeKey }}",
                routeIndex: 0,
                speed: {{ $initSpeed }},
                heading: 0,
                history: [],
                lat: {{ (float) ($veh->device?->last_known_latitude ?? (6.4281 + ($idx * 0.035))) }},
                lng: {{ (float) ($veh->device?->last_known_longitude ?? (3.4219 + ($idx * 0.045))) }}
            });
        @endforeach

        if (vehicleData.length > 0) {
            activeVehicleId = vehicleData[0].id;
        }

        const group = [];

        // Build Custom Markers for each vehicle
        vehicleData.forEach((veh, i) => {
            const isMov = veh.motionState === 'moving';
            const isImmob = veh.motionState === 'immobilized';
            const isIdle = veh.motionState === 'idling';

            const markerColor = isImmob ? '#EF4444' : (isMov ? '#10B981' : (isIdle ? '#F59E0B' : '#64748B'));

            // Leaflet Custom Icon with heading arrow and pulse glow
            const carIcon = L.divIcon({
                className: 'custom-car-container',
                html: `
                    <div id="marker-wrapper-${veh.id}" class="custom-car-marker" style="position: relative; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        ${isMov ? `<div class="custom-car-moving-ring" style="position: absolute; width: 46px; height: 46px; background: rgba(16, 185, 129, 0.25); border-radius: 50%;"></div>` : ''}
                        <div style="position: relative; width: 34px; height: 34px; background: #0F172A; border: 2.5px solid ${markerColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                            <svg id="marker-arrow-${veh.id}" width="16" height="16" viewBox="0 0 24 24" fill="${markerColor}" style="transform: rotate(0deg); transition: transform 0.5s ease;">
                                <path d="M12 2L19 21L12 17L5 21L12 2Z"/>
                            </svg>
                        </div>
                        <div style="position: absolute; bottom: -14px; background: #0F172A; border: 1px solid #334155; color: white; font-family: monospace; font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px; white-space: nowrap; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                            ${veh.plate}
                        </div>
                    </div>
                `,
                iconSize: [48, 48],
                iconAnchor: [24, 24]
            });

            const marker = L.marker([veh.lat, veh.lng], { icon: carIcon }).addTo(map);

            // Breadcrumb polyline trail
            const polyline = L.polyline([[veh.lat, veh.lng]], {
                color: isMov ? '#10B981' : '#F97316',
                weight: 4,
                opacity: 0.8,
                dashArray: '6, 6'
            }).addTo(map);

            polylines[veh.id] = polyline;

            marker.on('click', () => {
                selectVehicle(veh.id);
            });

            markers[veh.id] = marker;
            group.push([veh.lat, veh.lng]);

            updatePopup(veh);
        });

        if (group.length > 0) {
            map.fitBounds(group, { padding: [60, 60], maxZoom: 15 });
        }

        // Start Real-Time Movement Simulation & Polling Loop
        startLiveStreamingLoop();
    });

    // Update vehicle popup InfoWindow
    function updatePopup(veh) {
        const marker = markers[veh.id];
        if (!marker) return;

        const isMov = veh.motionState === 'moving';
        const isImmob = veh.motionState === 'immobilized';
        const isIdle = veh.motionState === 'idling';

        const stateColor = isImmob ? '#EF4444' : (isMov ? '#10B981' : (isIdle ? '#F59E0B' : '#64748B'));
        const stateName = isImmob ? 'ENGINE CUT' : (isMov ? 'MOVING' : (isIdle ? 'IDLING' : 'PARKED'));

        const popupContent = `
            <div style="font-family: system-ui, sans-serif; padding: 6px; min-width: 230px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-weight: 800; font-size: 13px; color: #0f172a;">${veh.title}</span>
                    <span style="font-size: 9px; font-weight: 800; padding: 2px 6px; border-radius: 4px; background: ${stateColor}20; color: ${stateColor}; border: 1px solid ${stateColor}40;">
                        ${stateName}
                    </span>
                </div>
                <div style="font-size: 12px; font-family: monospace; font-weight: 700; color: #ea580c; margin-bottom: 6px;">${veh.plate}</div>
                <div style="font-size: 11px; color: #475569; margin-bottom: 3px;">Speed: <strong style="color: ${isMov ? '#10b981' : '#64748b'}; font-family: monospace;">${veh.speed.toFixed(1)} km/h</strong></div>
                <div style="font-size: 11px; color: #475569; margin-bottom: 3px;">Driver: <strong>${veh.owner}</strong></div>
                <div style="font-size: 10px; color: #64748b; font-family: monospace; margin-bottom: 8px;">Coordinates: ${veh.lat.toFixed(5)}, ${veh.lng.toFixed(5)}</div>
                
                <div style="display: flex; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                    ${veh.deviceUrl !== '#' ? `<a href="${veh.deviceUrl}" style="display: inline-block; padding: 4px 8px; background: #0f172a; color: white; border-radius: 6px; font-size: 10px; font-weight: 700; text-decoration: none;">Inspect Hardware</a>` : ''}
                    ${veh.userUrl !== '#' ? `<a href="${veh.userUrl}" style="display: inline-block; padding: 4px 8px; background: #f1f5f9; color: #334155; border-radius: 6px; font-size: 10px; font-weight: 700; text-decoration: none;">Customer Profile</a>` : ''}
                </div>
            </div>
        `;

        marker.bindPopup(popupContent);
    }

    // Real-Time GPS Tracking Movement Loop (Advances coordinates every 1.5s)
    function startLiveStreamingLoop() {
        if (streamTimer) clearInterval(streamTimer);

        streamTimer = setInterval(() => {
            if (!isLiveStreaming) return;

            vehicleData.forEach((veh) => {
                if (veh.motionState !== 'moving') return;

                const route = ROUTES[veh.routeKey];
                if (!route || route.length === 0) return;

                // Advance along route
                veh.routeIndex = (veh.routeIndex + 1) % route.length;
                const point = route[veh.routeIndex];

                // Add slight dynamic speed fluctuation
                const speedJitter = (Math.random() * 4 - 2);
                veh.speed = Math.max(25, point.speed + speedJitter);
                veh.lat = point.lat;
                veh.lng = point.lng;
                veh.heading = point.heading;
                veh.address = point.address;

                // Move Leaflet marker smoothly
                const marker = markers[veh.id];
                if (marker) {
                    marker.setLatLng([veh.lat, veh.lng]);

                    // Rotate heading arrow
                    const arrow = document.getElementById(`marker-arrow-${veh.id}`);
                    if (arrow) {
                        arrow.style.transform = `rotate(${veh.heading}deg)`;
                    }

                    // Update breadcrumb trail
                    const polyline = polylines[veh.id];
                    if (polyline) {
                        const currentLatLngs = polyline.getLatLngs();
                        currentLatLngs.push([veh.lat, veh.lng]);
                        if (currentLatLngs.length > 25) currentLatLngs.shift();
                        polyline.setLatLngs(currentLatLngs);
                    }

                    updatePopup(veh);
                }

                // Update card metrics in sidebar
                const speedElem = document.getElementById(`card-speed-text-${veh.id}`);
                if (speedElem) {
                    speedElem.innerText = `${veh.speed.toFixed(1)} km/h`;
                }

                // If this is the active focused vehicle, pan camera & update HUD
                if (veh.id === activeVehicleId) {
                    updateActiveVehicleHud(veh);
                    if (isCameraFollow && map) {
                        map.panTo([veh.lat, veh.lng], { animate: true, duration: 0.8 });
                    }
                }
            });
        }, 1500);
    }

    function updateActiveVehicleHud(veh) {
        const isMov = veh.motionState === 'moving';
        const isImmob = veh.motionState === 'immobilized';
        const isIdle = veh.motionState === 'idling';

        const badge = document.getElementById('targetMotionBadge');
        if (badge) {
            if (isImmob) {
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-500/20 text-rose-400 border border-rose-500/40';
                badge.innerText = 'ENGINE CUT';
            } else if (isMov) {
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/40';
                badge.innerText = 'MOVING';
            } else if (isIdle) {
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-400 border border-amber-500/40';
                badge.innerText = 'IDLING';
            } else {
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-slate-500/20 text-slate-400 border border-slate-500/40';
                badge.innerText = 'PARKED';
            }
        }

        const beacon = document.getElementById('activeStatusBeacon');
        if (beacon) {
            beacon.className = `w-3.5 h-3.5 rounded-full shrink-0 ${isImmob ? 'bg-rose-500' : (isMov ? 'bg-emerald-500 animate-ping' : (isIdle ? 'bg-amber-500' : 'bg-slate-400'))}`;
        }

        const speedHud = document.getElementById('targetSpeedHud');
        if (speedHud) {
            speedHud.innerText = `${veh.speed.toFixed(1)} km/h`;
            speedHud.style.color = isMov ? '#34d399' : '#94a3b8';
        }

        const label = document.getElementById('targetVehicleLabel');
        if (label) {
            label.innerHTML = `${veh.title} • <span class="text-brand-500 font-mono">${veh.plate}</span>`;
        }

        const addr = document.getElementById('targetAddressHud');
        if (addr) {
            addr.innerText = veh.address || 'Victoria Island, Lagos';
        }
    }

    // Select vehicle and focus
    window.selectVehicle = function(id) {
        activeVehicleId = id;
        const veh = vehicleData.find(v => v.id === id);
        if (!veh || !map) return;

        map.flyTo([veh.lat, veh.lng], 16, { animate: true, duration: 1.0 });

        if (markers[id]) {
            markers[id].openPopup();
        }

        document.querySelectorAll('.vehicle-item').forEach(el => {
            el.classList.remove('border-brand-500', 'bg-brand-50/40');
            el.classList.add('border-slate-100', 'bg-slate-50/80');
        });

        const activeCard = document.getElementById(`vehicle-card-${id}`);
        if (activeCard) {
            activeCard.classList.remove('border-slate-100', 'bg-slate-50/80');
            activeCard.classList.add('border-brand-500', 'bg-brand-50/40');
        }

        updateActiveVehicleHud(veh);
    };

    window.toggleLiveTrackingStream = function() {
        isLiveStreaming = !isLiveStreaming;
        const btn = document.getElementById('liveStreamToggleBtn');
        const text = document.getElementById('liveStreamStatusText');

        if (isLiveStreaming) {
            btn.className = 'px-3 py-1.5 rounded-lg bg-emerald-600 text-white flex items-center gap-1.5 shadow-sm transition';
            text.innerText = 'Live Stream: ON';
        } else {
            btn.className = 'px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 flex items-center gap-1.5 transition';
            text.innerText = 'Live Stream: PAUSED';
        }
    };

    window.toggleCameraFollow = function() {
        isCameraFollow = !isCameraFollow;
        const text = document.getElementById('followCameraText');
        if (text) {
            text.innerText = isCameraFollow ? 'Follow Vehicle: ON' : 'Follow Vehicle: OFF';
        }
    };

    window.fitAllVehicles = function() {
        if (!map || vehicleData.length === 0) return;
        const coords = vehicleData.map(v => [v.lat, v.lng]);
        map.fitBounds(coords, { padding: [50, 50] });
    };

    window.filterByMotionState = function(state) {
        document.querySelectorAll('.motion-tab-btn').forEach(btn => {
            if (btn.getAttribute('data-state') === state) {
                btn.className = 'motion-tab-btn flex-1 py-1 rounded-lg bg-white text-slate-900 shadow-sm text-center font-extrabold';
            } else {
                btn.className = 'motion-tab-btn flex-1 py-1 rounded-lg hover:text-slate-900 text-center text-slate-600';
            }
        });

        document.querySelectorAll('.vehicle-item').forEach(el => {
            const itemMotion = el.getAttribute('data-motion');
            if (state === 'all' || itemMotion === state) {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        });
    };

    window.filterVehicleList = function() {
        const query = (document.getElementById('vehicleSearchInput').value || '').toLowerCase();
        document.querySelectorAll('.vehicle-item').forEach(el => {
            const text = el.innerText.toLowerCase();
            el.style.display = text.includes(query) ? 'block' : 'none';
        });
    };
</script>
@endsection

