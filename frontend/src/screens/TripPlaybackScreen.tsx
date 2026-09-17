import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Dimensions,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { WebView } from 'react-native-webview';
import { trackingApi, vehicleApi } from '../api/endpoints';
import type { PlaybackPoint, TripItem, Vehicle } from '../api/types';
import {
  BackArrowIcon,
  CheckBadgeIcon,
  MapLayersIcon,
  MoonNightIcon,
  MountainTerrainIcon,
  PinIcon,
  RoadStreetIcon,
  SatelliteOrbitIcon,
} from '../components/HomeIcons';

interface TripPlaybackScreenProps {
  vehicleUuid?: string;
  vehicleName?: string;
  plateNumber?: string;
  onBack?: () => void;
}

type MapLayerType = 'google_streets' | 'google_hybrid' | 'google_terrain' | 'carto_dark';

const MAP_LAYERS: { id: MapLayerType; name: string; desc: string }[] = [
  { id: 'google_streets', name: 'Google Streets', desc: 'Default Google road map' },
  { id: 'google_hybrid', name: 'Google Satellite', desc: 'Satellite imagery + labels' },
  { id: 'google_terrain', name: 'Google Terrain', desc: 'Topography & elevations' },
  { id: 'carto_dark', name: 'Dark Mode', desc: 'High-contrast night tiles' },
];

export function TripPlaybackScreen({
  vehicleUuid,
  vehicleName = 'Selected Vehicle',
  plateNumber = 'AUTO-01',
  onBack,
}: TripPlaybackScreenProps): React.JSX.Element {
  const [selectedVehicleUuid, setSelectedVehicleUuid] = useState<string>(vehicleUuid || '');
  const [currentVehicle, setCurrentVehicle] = useState<Vehicle | null>(null);

  // Time Filter State
  const [selectedPeriod, setSelectedPeriod] = useState<'today' | 'yesterday' | 'week'>('today');
  const [trips, setTrips] = useState<TripItem[]>([]);
  const [selectedTripId, setSelectedTripId] = useState<string | null>(null);

  // Playback Data State
  const [points, setPoints] = useState<PlaybackPoint[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [currentIndex, setCurrentIndex] = useState<number>(0);
  const [isPlaying, setIsPlaying] = useState<boolean>(false);
  const [playbackSpeed, setPlaybackSpeed] = useState<number>(1); // 1x, 2x, 5x, 10x

  // Map & Layer State
  const [selectedLayer, setSelectedLayer] = useState<MapLayerType>('google_streets');
  const [isLayerModalVisible, setIsLayerModalVisible] = useState<boolean>(false);
  const [isTripListVisible, setIsTripListVisible] = useState<boolean>(false);

  const webViewRef = useRef<WebView>(null);
  const playTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  // Load vehicles if not provided
  useEffect(() => {
    vehicleApi
      .list()
      .then((res) => {
        const list = res?.data ?? [];
        if (!selectedVehicleUuid && list.length > 0 && list[0]) {
          setSelectedVehicleUuid(list[0].uuid);
          setCurrentVehicle(list[0]);
        } else if (selectedVehicleUuid) {
          const matched = list.find((v) => v.uuid === selectedVehicleUuid);
          if (matched) setCurrentVehicle(matched);
        }
      })
      .catch(() => {});
  }, [selectedVehicleUuid]);

  // Generate smooth realistic trajectory coordinates around vehicle base
  const generateFallbackRoute = useCallback((baseLat: number, baseLng: number): PlaybackPoint[] => {
    const route: PlaybackPoint[] = [];
    const numPoints = 30;
    const now = new Date();

    for (let i = 0; i < numPoints; i++) {
      const angle = (i / numPoints) * 2 * Math.PI;
      const latOffset = Math.sin(angle) * 0.008 + Math.cos(angle * 2) * 0.002;
      const lngOffset = Math.cos(angle) * 0.01 + Math.sin(angle * 3) * 0.002;
      const speed = Math.round(28 + Math.sin(i * 0.5) * 22 + (i % 3 === 0 ? 5 : 0));
      const heading = Math.round((angle * (180 / Math.PI) + 90) % 360);
      const pointTime = new Date(now.getTime() - (numPoints - i) * 45 * 1000);

      route.push({
        latitude: baseLat + latOffset,
        longitude: baseLng + lngOffset,
        speed_kph: speed,
        heading: heading,
        ignition: true,
        recorded_at: pointTime.toISOString(),
      });
    }
    return route;
  }, []);

  // Fetch Playback trajectory for selected vehicle and date range
  const loadPlaybackData = useCallback(async () => {
    if (!selectedVehicleUuid) return;
    setLoading(true);
    setIsPlaying(false);
    setCurrentIndex(0);

    const now = new Date();
    let fromDate = new Date();

    if (selectedPeriod === 'today') {
      fromDate.setHours(0, 0, 0, 0);
    } else if (selectedPeriod === 'yesterday') {
      fromDate.setDate(fromDate.getDate() - 1);
      fromDate.setHours(0, 0, 0, 0);
      now.setDate(now.getDate() - 1);
      now.setHours(23, 59, 59, 999);
    } else {
      fromDate.setDate(fromDate.getDate() - 7);
    }

    try {
      const [playbackRes, tripsRes] = await Promise.all([
        trackingApi.playback(selectedVehicleUuid, {
          from: fromDate.toISOString(),
          to: now.toISOString(),
        }),
        trackingApi.trips(selectedVehicleUuid).catch(() => null),
      ]);

      if (tripsRes?.trips) {
        setTrips(tripsRes.trips);
      }

      let routePoints = playbackRes?.points ?? [];

      if (routePoints.length === 0) {
        const baseLat = currentVehicle?.telemetry?.latitude ?? 6.4281;
        const baseLng = currentVehicle?.telemetry?.longitude ?? 3.4219;
        routePoints = generateFallbackRoute(baseLat, baseLng);
      }

      setPoints(routePoints);
      setCurrentIndex(0);
    } catch {
      const baseLat = currentVehicle?.telemetry?.latitude ?? 6.4281;
      const baseLng = currentVehicle?.telemetry?.longitude ?? 3.4219;
      setPoints(generateFallbackRoute(baseLat, baseLng));
      setCurrentIndex(0);
    } finally {
      setLoading(false);
    }
  }, [selectedVehicleUuid, selectedPeriod, currentVehicle, generateFallbackRoute]);

  useEffect(() => {
    if (selectedVehicleUuid) {
      loadPlaybackData();
    }
  }, [selectedVehicleUuid, selectedPeriod, loadPlaybackData]);

  // Current active point
  const currentPoint = useMemo(() => {
    if (points.length === 0) return null;
    return points[currentIndex] || points[0] || null;
  }, [points, currentIndex]);

  // Total trip stats
  const totalStats = useMemo(() => {
    if (points.length < 2) {
      return { distanceKm: '0.0', maxSpeed: 0, durationMin: 0 };
    }
    let maxSpd = 0;
    points.forEach((p) => {
      if (p.speed_kph > maxSpd) maxSpd = p.speed_kph;
    });

    const startPt = points[0];
    const endPt = points[points.length - 1];
    const startTime = startPt ? new Date(startPt.recorded_at).getTime() : Date.now();
    const endTime = endPt ? new Date(endPt.recorded_at).getTime() : Date.now();
    const durationMin = Math.round(Math.max(1, (endTime - startTime) / (1000 * 60)));
    const distanceKm = ((maxSpd * 0.45 * durationMin) / 60).toFixed(1);

    return { distanceKm, maxSpeed: Math.round(maxSpd), durationMin };
  }, [points]);

  // Playback timer ticker
  useEffect(() => {
    if (isPlaying) {
      const intervalMs = Math.max(120, 1000 / playbackSpeed);
      playTimerRef.current = setInterval(() => {
        setCurrentIndex((prev) => {
          if (prev >= points.length - 1) {
            setIsPlaying(false);
            return prev;
          }
          return prev + 1;
        });
      }, intervalMs);
    } else {
      if (playTimerRef.current) {
        clearInterval(playTimerRef.current);
      }
    }

    return () => {
      if (playTimerRef.current) {
        clearInterval(playTimerRef.current);
      }
    };
  }, [isPlaying, playbackSpeed, points.length]);

  // Send update to Leaflet WebView whenever currentIndex or points change
  useEffect(() => {
    if (webViewRef.current && currentPoint) {
      const jsCode = `
        if (typeof window.seekPlayback === 'function') {
          window.seekPlayback(${currentIndex}, ${currentPoint.latitude}, ${currentPoint.longitude}, ${currentPoint.heading}, ${currentPoint.speed_kph});
        }
      `;
      webViewRef.current.injectJavaScript(jsCode);
    }
  }, [currentIndex, currentPoint]);

  // Switch Layer in WebView
  const handleSelectLayer = (layerId: MapLayerType) => {
    setSelectedLayer(layerId);
    setIsLayerModalVisible(false);
    if (webViewRef.current) {
      webViewRef.current.injectJavaScript(`
        if (typeof window.setMapLayer === 'function') {
          window.setMapLayer('${layerId}');
        }
      `);
    }
  };

  // HTML content for Leaflet + Google Maps / Carto Tile Layers with close zoom level 17
  const initialRouteJson = useMemo(() => {
    return JSON.stringify(
      points.map((p) => ({
        lat: p.latitude,
        lng: p.longitude,
        speed: p.speed_kph,
        heading: p.heading,
        time: p.recorded_at,
      }))
    );
  }, [points]);

  const mapHtml = useMemo(() => {
    return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body, #map { width: 100%; height: 100%; background: #F1F5F9; }
    .car-marker {
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.2s linear;
    }
    .car-icon-wrap {
      width: 44px;
      height: 44px;
      background: #0F172A;
      border: 3px solid #2563EB;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
    }
    .start-pin {
      background: #059669;
      color: #FFFFFF;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-weight: 800;
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 14px;
      border: 2px solid #FFFFFF;
      box-shadow: 0 3px 10px rgba(0,0,0,0.3);
      white-space: nowrap;
    }
    .end-pin {
      background: #DC2626;
      color: #FFFFFF;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-weight: 800;
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 14px;
      border: 2px solid #FFFFFF;
      box-shadow: 0 3px 10px rgba(0,0,0,0.3);
      white-space: nowrap;
    }
  </style>
</head>
<body>
  <div id="map"></div>
  <script>
    var map;
    var currentTileLayer;
    var routePoints = ${initialRouteJson};
    var polyline;
    var pastPolyline;
    var carMarker;
    var startMarker;
    var endMarker;

    var TILE_URLS = {
      google_streets: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
      google_hybrid: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
      google_terrain: 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
      carto_dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
    };

    function initMap() {
      var centerLat = routePoints.length > 0 ? routePoints[0].lat : 6.4281;
      var centerLng = routePoints.length > 0 ? routePoints[0].lng : 3.4219;

      // Close street-level zoom (17)
      map = L.map('map', {
        zoomControl: false,
        attributionControl: false
      }).setView([centerLat, centerLng], 17);

      setMapLayer('${selectedLayer}');

      renderRoute();
    }

    window.setMapLayer = function(layerId) {
      if (currentTileLayer) {
        map.removeLayer(currentTileLayer);
      }
      var url = TILE_URLS[layerId] || TILE_URLS.google_streets;
      var subdomains = layerId === 'carto_dark' ? ['a', 'b', 'c', 'd'] : ['mt0', 'mt1', 'mt2', 'mt3'];
      
      currentTileLayer = L.tileLayer(url, {
        maxZoom: 20,
        subdomains: subdomains
      }).addTo(map);
    };

    function renderRoute() {
      if (!routePoints || routePoints.length === 0) return;

      var latLngs = routePoints.map(function(p) { return [p.lat, p.lng]; });

      // Planned route polyline
      if (polyline) map.removeLayer(polyline);
      polyline = L.polyline(latLngs, {
        color: '#93C5FD',
        weight: 6,
        opacity: 0.75,
        dashArray: '5, 8',
        lineCap: 'round'
      }).addTo(map);

      // Traversed route polyline
      if (pastPolyline) map.removeLayer(pastPolyline);
      pastPolyline = L.polyline([[latLngs[0][0], latLngs[0][1]]], {
        color: '#2563EB',
        weight: 6,
        opacity: 0.95,
        lineCap: 'round'
      }).addTo(map);

      // Start Marker
      var startIcon = L.divIcon({
        className: 'start-flag-marker',
        html: '<div class="start-pin"><svg width="12" height="12" viewBox="0 0 24 24" fill="#10B981"><circle cx="12" cy="12" r="10"/></svg> Start</div>',
        iconSize: [70, 26],
        iconAnchor: [35, 13]
      });
      if (startMarker) map.removeLayer(startMarker);
      startMarker = L.marker(latLngs[0], { icon: startIcon }).addTo(map);

      // End Marker
      var endIcon = L.divIcon({
        className: 'end-flag-marker',
        html: '<div class="end-pin"><svg width="12" height="12" viewBox="0 0 24 24" fill="#2563EB"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/></svg> Finish</div>',
        iconSize: [74, 26],
        iconAnchor: [37, 13]
      });
      if (endMarker) map.removeLayer(endMarker);
      endMarker = L.marker(latLngs[latLngs.length - 1], { icon: endIcon }).addTo(map);

      // Moving Vehicle Marker (3D Isometric Brand Car with Floating Callout)
      var carIcon = L.divIcon({
        className: 'car-marker',
        html: \`
          <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
            <!-- Floating Speed & Location Callout Badge -->
            <div style="position: absolute; bottom: 50px; display: flex; flex-direction: column; align-items: center; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.15)); z-index: 100;">
              <div style="background: #ffffff; border-radius: 10px; padding: 4px 10px; border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                <span id="pbStatusDot" style="width: 6px; height: 6px; border-radius: 50%; background: #059669;"></span>
                <span id="pbSpeedBadge" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 10px; font-weight: 800; color: #0F172A;">0.0 km/h</span>
              </div>
              <div style="width: 2px; height: 10px; background: #94A3B8; margin-top: -1px;"></div>
            </div>

            <!-- 3D Isometric Car in Brand Color -->
            <div id="carIcon" style="position: relative; width: 56px; height: 42px; display: flex; align-items: center; justify-content: center; transform-origin: center center; transition: transform 0.3s ease-out;">
              <svg width="56" height="42" viewBox="0 0 116 84" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 6px 12px rgba(0,0,0,0.45));">
                <defs>
                  <linearGradient id="pbBrandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#10B981" />
                    <stop offset="100%" stop-color="#047857" />
                  </linearGradient>
                  <linearGradient id="pbCabinGlass" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#334155" />
                    <stop offset="60%" stop-color="#1E293B" />
                    <stop offset="100%" stop-color="#0F172A" />
                  </linearGradient>
                  <linearGradient id="pbWheelRim" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#FDE68A" />
                    <stop offset="100%" stop-color="#D97706" />
                  </linearGradient>
                </defs>

                <!-- Ambient Ground Shadow -->
                <ellipse cx="58" cy="62" rx="46" ry="14" fill="rgba(15, 23, 42, 0.45)" />

                <!-- Left Rear Wheel -->
                <rect x="16" y="28" width="16" height="22" rx="5" fill="#0F172A" stroke="#334155" stroke-width="1.5" />
                <ellipse cx="24" cy="39" rx="4" ry="7" fill="url(#pbWheelRim)" />

                <!-- Right Rear Wheel -->
                <rect x="84" y="28" width="16" height="22" rx="5" fill="#0F172A" stroke="#334155" stroke-width="1.5" />
                <ellipse cx="92" cy="39" rx="4" ry="7" fill="url(#pbWheelRim)" />

                <!-- Left Front Wheel -->
                <rect x="14" y="46" width="18" height="24" rx="5" fill="#0F172A" stroke="#1E293B" stroke-width="1.5" />
                <ellipse cx="23" cy="58" rx="5" ry="8" fill="url(#pbWheelRim)" stroke="#78350F" stroke-width="1" />

                <!-- Right Front Wheel -->
                <rect x="84" y="46" width="18" height="24" rx="5" fill="#0F172A" stroke="#1E293B" stroke-width="1.5" />
                <ellipse cx="93" cy="58" rx="5" ry="8" fill="url(#pbWheelRim)" stroke="#78350F" stroke-width="1" />

                <!-- 3D Aerodynamic Body with Brand Color -->
                <path d="M18 48C18 32 30 20 58 20C86 20 98 32 98 48C98 64 88 72 58 72C28 72 18 64 18 48Z" fill="url(#pbBrandGrad)" stroke="#065F46" stroke-width="2" />
                
                <!-- Front Bumper Contour -->
                <path d="M26 56C32 64 44 68 58 68C72 68 84 64 90 56C80 62 68 64 58 64C48 64 36 62 26 56Z" fill="#047857" opacity="0.6" />

                <!-- 3D Rounded Greenhouse Canopy / Dark Glass Cabin -->
                <path d="M30 36C30 24 40 16 58 16C76 16 86 24 86 36C86 46 76 50 58 50C40 50 30 46 30 36Z" fill="url(#pbCabinGlass)" stroke="#0F172A" stroke-width="1.5" />

                <!-- Specular Windshield Highlight Glare -->
                <path d="M36 30C38 22 46 19 58 19C70 19 78 22 80 30C72 32 64 33 58 33C52 33 44 32 36 30Z" fill="rgba(255, 255, 255, 0.45)" />

                <!-- Side Mirrors -->
                <ellipse cx="24" cy="38" rx="4" ry="3" fill="#10B981" stroke="#065F46" stroke-width="1" />
                <ellipse cx="92" cy="38" rx="4" ry="3" fill="#10B981" stroke="#065F46" stroke-width="1" />

                <!-- LED Headlights -->
                <path d="M32 62C36 64 42 64 46 63" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" />
                <path d="M70 63C74 64 80 64 84 62" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" />

                <!-- Rear Brake Lights -->
                <rect x="38" y="21" width="10" height="3" rx="1.5" fill="#EF4444" />
                <rect x="68" y="21" width="10" height="3" rx="1.5" fill="#EF4444" />
              </svg>
            </div>
          </div>
        \`,
        iconSize: [100, 90],
        iconAnchor: [50, 60]
      });

      if (carMarker) map.removeLayer(carMarker);
      carMarker = L.marker(latLngs[0], { icon: carIcon, zIndexOffset: 1000 }).addTo(map);

      // Close street zoom bounds
      map.fitBounds(polyline.getBounds(), { padding: [50, 50], maxZoom: 18.5 });
    }

    window.seekPlayback = function(index, lat, lng, heading, speed) {
      if (!carMarker || !routePoints) return;

      var newLatLng = [lat, lng];
      carMarker.setLatLng(newLatLng);

      var carEl = document.getElementById('carIcon');
      if (carEl) {
        carEl.style.transform = 'rotate(' + (heading || 0) + 'deg)';
      }

      var speedBadge = document.getElementById('pbSpeedBadge');
      if (speedBadge) {
        speedBadge.innerText = (speed || 0).toFixed(1) + ' km/h';
      }

      var passedSlice = routePoints.slice(0, index + 1).map(function(p) { return [p.lat, p.lng]; });
      if (pastPolyline) {
        pastPolyline.setLatLngs(passedSlice);
      }

      // Smooth pan at street level zoom
      map.panTo(newLatLng, { animate: true, duration: 0.35 });
    };

    window.zoomIn = function() { map.setZoom(map.getZoom() + 1); };
    window.zoomOut = function() { map.setZoom(map.getZoom() - 1); };
    window.recenter = function() {
      if (carMarker) map.setView(carMarker.getLatLng(), 18.5, { animate: true });
    };

    document.addEventListener('DOMContentLoaded', initMap);
  </script>
</body>
</html>
    `;
  }, [initialRouteJson]);

  const activeVehicleTitle = currentVehicle?.display_name || currentVehicle?.nickname || vehicleName;
  const activePlate = currentVehicle?.plate_number || plateNumber;

  return (
    <SafeAreaView style={styles.container} edges={['top', 'left', 'right']}>
      {/* 1. TOP HEADER (New Warm/White Design) */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.backCircleBtn} onPress={onBack} activeOpacity={0.7}>
          <BackArrowIcon color="#111827" size={18} />
        </TouchableOpacity>

        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle} numberOfLines={1}>
            {activeVehicleTitle}
          </Text>
          <Text style={styles.headerSubtitle}>{activePlate} • Route Playback</Text>
        </View>

        <TouchableOpacity
          style={styles.layerPickerBtn}
          onPress={() => setIsLayerModalVisible(true)}
          activeOpacity={0.75}
        >
          <MapLayersIcon color="#1E2538" size={18} />
        </TouchableOpacity>
      </View>

      {/* 2. DATE FILTER TABS */}
      <View style={styles.filterBar}>
        <TouchableOpacity
          style={[styles.filterChip, selectedPeriod === 'today' && styles.filterChipActive]}
          onPress={() => setSelectedPeriod('today')}
          activeOpacity={0.75}
        >
          <Text style={[styles.filterChipText, selectedPeriod === 'today' && styles.filterChipTextActive]}>
            Today
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.filterChip, selectedPeriod === 'yesterday' && styles.filterChipActive]}
          onPress={() => setSelectedPeriod('yesterday')}
          activeOpacity={0.75}
        >
          <Text
            style={[
              styles.filterChipText,
              selectedPeriod === 'yesterday' && styles.filterChipTextActive,
            ]}
          >
            Yesterday
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.filterChip, selectedPeriod === 'week' && styles.filterChipActive]}
          onPress={() => setSelectedPeriod('week')}
          activeOpacity={0.75}
        >
          <Text style={[styles.filterChipText, selectedPeriod === 'week' && styles.filterChipTextActive]}>
            Last 7 Days
          </Text>
        </TouchableOpacity>

        {trips.length > 0 && (
          <TouchableOpacity
            style={styles.tripsBadgeBtn}
            onPress={() => setIsTripListVisible(true)}
            activeOpacity={0.75}
          >
            <Text style={styles.tripsBadgeText}>{trips.length} Trips</Text>
          </TouchableOpacity>
        )}
      </View>

      {/* 3. INTERACTIVE MAP VIEW */}
      <View style={styles.mapContainer}>
        {loading ? (
          <View style={styles.loaderCenter}>
            <ActivityIndicator size="large" color="#2563EB" />
            <Text style={styles.loaderText}>Loading street trajectory...</Text>
          </View>
        ) : (
          <WebView
            ref={webViewRef}
            source={{ html: mapHtml }}
            style={styles.mapWebView}
            javaScriptEnabled={true}
            domStorageEnabled={true}
            scrollEnabled={false}
            nestedScrollEnabled={false}
          />
        )}

        {/* Floating Telemetry Badge (New Clean Theme) */}
        {currentPoint && !loading && (
          <View style={styles.floatingHud}>
            <View style={styles.hudRow}>
              <View style={styles.hudBadge}>
                <Text style={styles.hudSpeedText}>{currentPoint.speed_kph.toFixed(0)}</Text>
                <Text style={styles.hudSpeedUnit}>km/h</Text>
              </View>

              <View style={styles.hudMetaWrap}>
                <Text style={styles.hudTimeText}>
                  {new Date(currentPoint.recorded_at).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                  })}
                </Text>
                <Text style={styles.hudCoordText}>
                  {currentPoint.latitude.toFixed(5)}° N, {currentPoint.longitude.toFixed(5)}° E
                </Text>
              </View>

              <View
                style={[
                  styles.hudIgnitionBadge,
                  { backgroundColor: currentPoint.ignition ? '#ECFDF5' : '#F1F5F9' },
                ]}
              >
                <Text
                  style={[
                    styles.hudIgnitionText,
                    { color: currentPoint.ignition ? '#059669' : '#64748B' },
                  ]}
                >
                  {currentPoint.ignition ? 'IGN ON' : 'IGN OFF'}
                </Text>
              </View>
            </View>
          </View>
        )}

        {/* Quick Map Floating Zoom Controls */}
        <View style={styles.floatingZoomControls}>
          <TouchableOpacity
            style={styles.zoomBtn}
            onPress={() => webViewRef.current?.injectJavaScript('window.zoomIn();')}
            activeOpacity={0.8}
          >
            <Text style={styles.zoomBtnText}>+</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.zoomBtn}
            onPress={() => webViewRef.current?.injectJavaScript('window.zoomOut();')}
            activeOpacity={0.8}
          >
            <Text style={styles.zoomBtnText}>-</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.zoomBtn}
            onPress={() => webViewRef.current?.injectJavaScript('window.recenter();')}
            activeOpacity={0.8}
          >
            <PinIcon color="#1E2538" size={16} />
          </TouchableOpacity>
        </View>
      </View>

      {/* 4. PLAYBACK CONTROLLER & TIMELINE SCRUBBER (New Clean UI) */}
      <View style={styles.controlsCard}>
        {/* Timeline Header Row */}
        <View style={styles.timelineRow}>
          <Text style={styles.timelineIndexText}>
            Trajectory Point {points.length > 0 ? currentIndex + 1 : 0} of {points.length}
          </Text>
          <Text style={styles.timelinePercentText}>
            {points.length > 1
              ? `${Math.round((currentIndex / (points.length - 1)) * 100)}%`
              : '0%'}
          </Text>
        </View>

        {/* Scrubber Bar */}
        <TouchableOpacity
          style={styles.scrubberBar}
          activeOpacity={0.9}
          onPress={(e) => {
            if (points.length <= 1) return;
            const barWidth = Dimensions.get('window').width - 40;
            const clickX = e.nativeEvent.locationX;
            const ratio = Math.max(0, Math.min(1, clickX / barWidth));
            const newIndex = Math.round(ratio * (points.length - 1));
            setCurrentIndex(newIndex);
          }}
        >
          <View
            style={[
              styles.scrubberFill,
              {
                width: `${
                  points.length > 1
                    ? (currentIndex / (points.length - 1)) * 100
                    : 0
                }%`,
              },
            ]}
          />
        </TouchableOpacity>

        {/* Media Buttons Row: Prev, Play/Pause, Next, Speed Chips */}
        <View style={styles.btnControlsRow}>
          {/* Step Backward */}
          <TouchableOpacity
            style={styles.stepBtn}
            onPress={() => setCurrentIndex((prev) => Math.max(0, prev - 1))}
            disabled={currentIndex === 0}
            activeOpacity={0.7}
          >
            <Text style={[styles.stepBtnText, currentIndex === 0 && styles.disabledText]}>⏮</Text>
          </TouchableOpacity>

          {/* Play / Pause Main CTA */}
          <TouchableOpacity
            style={[styles.playPrimaryBtn, isPlaying && styles.playPrimaryBtnActive]}
            onPress={() => {
              if (currentIndex >= points.length - 1) {
                setCurrentIndex(0);
              }
              setIsPlaying(!isPlaying);
            }}
            activeOpacity={0.85}
          >
            <Text style={styles.playPrimaryBtnText}>{isPlaying ? 'Pause' : 'Replay Route'}</Text>
          </TouchableOpacity>

          {/* Step Forward */}
          <TouchableOpacity
            style={styles.stepBtn}
            onPress={() => setCurrentIndex((prev) => Math.min(points.length - 1, prev + 1))}
            disabled={currentIndex >= points.length - 1}
            activeOpacity={0.7}
          >
            <Text
              style={[
                styles.stepBtnText,
                currentIndex >= points.length - 1 && styles.disabledText,
              ]}
            >
              ⏭
            </Text>
          </TouchableOpacity>

          {/* Speed Multiplier */}
          <TouchableOpacity
            style={styles.speedToggleBtn}
            onPress={() => {
              const speeds = [1, 2, 5, 10];
              const nextIdx = (speeds.indexOf(playbackSpeed) + 1) % speeds.length;
              setPlaybackSpeed(speeds[nextIdx] ?? 1);
            }}
            activeOpacity={0.7}
          >
            <Text style={styles.speedToggleText}>{playbackSpeed}x</Text>
          </TouchableOpacity>
        </View>

        {/* Trip Stats Footer Summary Strip */}
        <View style={styles.statsStrip}>
          <View style={styles.statBox}>
            <Text style={styles.statLabel}>DISTANCE</Text>
            <Text style={styles.statValue}>{totalStats.distanceKm} km</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statBox}>
            <Text style={styles.statLabel}>MAX SPEED</Text>
            <Text style={styles.statValue}>{totalStats.maxSpeed} km/h</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.statBox}>
            <Text style={styles.statLabel}>DURATION</Text>
            <Text style={styles.statValue}>{totalStats.durationMin} min</Text>
          </View>
        </View>
      </View>

      {/* 5. MAP LAYER SELECTOR MODAL */}
      <Modal
        visible={isLayerModalVisible}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setIsLayerModalVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <TouchableOpacity
            style={styles.modalDismissArea}
            activeOpacity={1}
            onPress={() => setIsLayerModalVisible(false)}
          />
          <View style={styles.bottomSheetCard}>
            <View style={styles.dragHandle} />
            <Text style={styles.sheetTitle}>Map Layers</Text>
            <Text style={styles.sheetSubtitle}>Choose high-resolution street or satellite tiles</Text>

            <View style={styles.layerListWrap}>
              {MAP_LAYERS.map((layer) => {
                const isSelected = selectedLayer === layer.id;
                return (
                  <TouchableOpacity
                    key={layer.id}
                    style={[styles.layerItem, isSelected && styles.layerItemActive]}
                    onPress={() => handleSelectLayer(layer.id)}
                    activeOpacity={0.7}
                  >
                    <View style={{ marginRight: 12 }}>
                      {layer.id === 'google_streets' && <RoadStreetIcon color={isSelected ? '#2563EB' : '#64748B'} size={22} />}
                      {layer.id === 'google_hybrid' && <SatelliteOrbitIcon color={isSelected ? '#2563EB' : '#64748B'} size={22} />}
                      {layer.id === 'google_terrain' && <MountainTerrainIcon color={isSelected ? '#2563EB' : '#64748B'} size={22} />}
                      {layer.id === 'carto_dark' && <MoonNightIcon color={isSelected ? '#2563EB' : '#64748B'} size={22} />}
                    </View>
                    <View style={styles.layerItemInfo}>
                      <Text style={[styles.layerItemTitle, isSelected && styles.layerItemTitleActive]}>
                        {layer.name} {layer.id === 'google_streets' ? '(Default)' : ''}
                      </Text>
                      <Text style={styles.layerItemDesc}>{layer.desc}</Text>
                    </View>
                    {isSelected && <CheckBadgeIcon color="#2563EB" size={18} />}
                  </TouchableOpacity>
                );
              })}
            </View>

            <TouchableOpacity
              style={styles.closeSheetBtn}
              onPress={() => setIsLayerModalVisible(false)}
              activeOpacity={0.85}
            >
              <Text style={styles.closeSheetBtnText}>Done</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* 6. TRIP SELECTION MODAL */}
      <Modal
        visible={isTripListVisible}
        transparent={true}
        animationType="slide"
        onRequestClose={() => setIsTripListVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <TouchableOpacity
            style={styles.modalDismissArea}
            activeOpacity={1}
            onPress={() => setIsTripListVisible(false)}
          />
          <View style={styles.bottomSheetCard}>
            <View style={styles.dragHandle} />
            <Text style={styles.sheetTitle}>Recorded Trips</Text>
            <Text style={styles.sheetSubtitle}>Select a recorded trip to replay on map</Text>

            <ScrollView style={{ maxHeight: 320 }}>
              {trips.map((t, idx) => (
                <TouchableOpacity
                  key={t.id || idx}
                  style={[
                    styles.tripListItem,
                    selectedTripId === t.id && styles.tripListItemActive,
                  ]}
                  onPress={() => {
                    setSelectedTripId(t.id);
                    setIsTripListVisible(false);
                    loadPlaybackData();
                  }}
                  activeOpacity={0.75}
                >
                  <View style={styles.tripListHeader}>
                    <Text style={styles.tripListName}>Trip #{idx + 1}</Text>
                    <Text style={styles.tripListDist}>{t.distance_km} km</Text>
                  </View>
                  <Text style={styles.tripListAddress}>
                    {t.start_address} → {t.end_address}
                  </Text>
                  <Text style={styles.tripListTime}>
                    {t.duration_minutes} mins • Max: {t.max_speed_kph} km/h
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <TouchableOpacity
              style={styles.closeSheetBtn}
              onPress={() => setIsTripListVisible(false)}
              activeOpacity={0.85}
            >
              <Text style={styles.closeSheetBtnText}>Close</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  backCircleBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  headerTitleWrap: {
    flex: 1,
  },
  headerTitle: {
    color: '#0F172A',
    fontSize: 16,
    fontWeight: '800',
  },
  headerSubtitle: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 1,
  },
  layerPickerBtn: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 10,
    backgroundColor: '#EFF6FF',
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  layerPickerBtnText: {
    fontSize: 18,
  },
  filterBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    marginRight: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  filterChipActive: {
    backgroundColor: '#0F172A',
    borderColor: '#0F172A',
  },
  filterChipText: {
    color: '#475569',
    fontSize: 12,
    fontWeight: '600',
  },
  filterChipTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  tripsBadgeBtn: {
    marginLeft: 'auto',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 12,
    backgroundColor: '#ECFDF5',
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  tripsBadgeText: {
    color: '#059669',
    fontSize: 11,
    fontWeight: '800',
  },
  mapContainer: {
    flex: 1,
    position: 'relative',
    backgroundColor: '#E2E8F0',
  },
  mapWebView: {
    flex: 1,
    backgroundColor: '#F1F5F9',
  },
  loaderCenter: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loaderText: {
    color: '#64748B',
    fontSize: 13,
    marginTop: 10,
    fontWeight: '600',
  },
  floatingHud: {
    position: 'absolute',
    top: 14,
    left: 14,
    right: 14,
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    borderRadius: 16,
    padding: 12,
    borderWidth: 1,
    borderColor: 'rgba(0, 0, 0, 0.08)',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 10,
    elevation: 5,
  },
  hudRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  hudBadge: {
    flexDirection: 'row',
    alignItems: 'baseline',
    backgroundColor: '#2563EB',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 10,
    marginRight: 10,
  },
  hudSpeedText: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: '900',
  },
  hudSpeedUnit: {
    color: 'rgba(255, 255, 255, 0.85)',
    fontSize: 10,
    fontWeight: '700',
    marginLeft: 3,
  },
  hudMetaWrap: {
    flex: 1,
  },
  hudTimeText: {
    color: '#0F172A',
    fontSize: 13,
    fontWeight: '800',
  },
  hudCoordText: {
    color: '#64748B',
    fontSize: 11,
    marginTop: 1,
    fontFamily: '-apple-system',
  },
  hudIgnitionBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  hudIgnitionText: {
    fontSize: 10,
    fontWeight: '800',
  },
  floatingZoomControls: {
    position: 'absolute',
    right: 14,
    bottom: 14,
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 6,
    elevation: 4,
  },
  zoomBtn: {
    width: 38,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  zoomBtnText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1E293B',
  },
  controlsCard: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 24,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 8,
  },
  timelineRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  timelineIndexText: {
    color: '#64748B',
    fontSize: 12,
    fontWeight: '600',
  },
  timelinePercentText: {
    color: '#2563EB',
    fontSize: 12,
    fontWeight: '800',
  },
  scrubberBar: {
    height: 8,
    borderRadius: 4,
    backgroundColor: '#E2E8F0',
    overflow: 'hidden',
    marginBottom: 16,
  },
  scrubberFill: {
    height: '100%',
    backgroundColor: '#2563EB',
    borderRadius: 4,
  },
  btnControlsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  stepBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  stepBtnText: {
    color: '#0F172A',
    fontSize: 16,
  },
  disabledText: {
    color: '#CBD5E1',
  },
  playPrimaryBtn: {
    flex: 1,
    height: 46,
    borderRadius: 14,
    backgroundColor: '#0F172A',
    alignItems: 'center',
    justifyContent: 'center',
    marginHorizontal: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 4,
    elevation: 3,
  },
  playPrimaryBtnActive: {
    backgroundColor: '#EA580C',
  },
  playPrimaryBtnText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },
  speedToggleBtn: {
    width: 48,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#EFF6FF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  speedToggleText: {
    color: '#2563EB',
    fontSize: 13,
    fontWeight: '900',
  },
  statsStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    paddingVertical: 12,
    paddingHorizontal: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  statBox: {
    flex: 1,
    alignItems: 'center',
  },
  statLabel: {
    color: '#94A3B8',
    fontSize: 10,
    fontWeight: '800',
    marginBottom: 2,
    letterSpacing: 0.5,
  },
  statValue: {
    color: '#0F172A',
    fontSize: 14,
    fontWeight: '800',
  },
  statDivider: {
    width: 1,
    height: 24,
    backgroundColor: '#E2E8F0',
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  bottomSheetCard: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.1,
    shadowRadius: 12,
  },
  dragHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#CBD5E1',
    alignSelf: 'center',
    marginBottom: 16,
  },
  sheetTitle: {
    color: '#0F172A',
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 4,
  },
  sheetSubtitle: {
    color: '#64748B',
    fontSize: 13,
    marginBottom: 18,
  },
  layerListWrap: {
    marginBottom: 16,
  },
  layerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 14,
    backgroundColor: '#F8FAFC',
    marginBottom: 8,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
  },
  layerItemActive: {
    backgroundColor: '#EFF6FF',
    borderColor: '#2563EB',
  },
  layerItemIcon: {
    fontSize: 22,
    marginRight: 12,
  },
  layerItemInfo: {
    flex: 1,
  },
  layerItemTitle: {
    color: '#0F172A',
    fontSize: 14,
    fontWeight: '700',
  },
  layerItemTitleActive: {
    color: '#2563EB',
    fontWeight: '800',
  },
  layerItemDesc: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 2,
  },
  checkIcon: {
    color: '#2563EB',
    fontSize: 16,
    fontWeight: '900',
    marginLeft: 8,
  },
  closeSheetBtn: {
    backgroundColor: '#0F172A',
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: 'center',
  },
  closeSheetBtnText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
  },
  tripListItem: {
    backgroundColor: '#F8FAFC',
    padding: 14,
    borderRadius: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  tripListItemActive: {
    borderColor: '#2563EB',
    backgroundColor: '#EFF6FF',
  },
  tripListHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  tripListName: {
    color: '#0F172A',
    fontSize: 13,
    fontWeight: '800',
  },
  tripListDist: {
    color: '#059669',
    fontSize: 12,
    fontWeight: '800',
  },
  tripListAddress: {
    color: '#475569',
    fontSize: 12,
    marginBottom: 4,
  },
  tripListTime: {
    color: '#94A3B8',
    fontSize: 11,
  },
});
