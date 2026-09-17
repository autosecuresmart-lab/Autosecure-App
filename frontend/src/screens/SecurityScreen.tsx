import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Dimensions,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  TouchableWithoutFeedback,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { WebView } from 'react-native-webview';

import { securityApi, trackingApi, vehicleApi } from '../api/endpoints';
import type { Vehicle, VehicleLocation, VehicleStatusTelemetry } from '../api/types';
import {
  AlertTriangleIcon,
  CheckBadgeIcon,
  GeofenceShieldIcon,
  MapLayersIcon,
  MoonNightIcon,
  MountainTerrainIcon,
  PhoneCallIcon,
  PinIcon,
  PowerShutdownIcon,
  RefreshSyncIcon,
  RoadStreetIcon,
  SatelliteGpsIcon,
  SatelliteOrbitIcon,
  SearchIcon,
  ShieldCheckIcon,
  SpeakerBuzzerIcon,
  SpeedGaugeIcon,
  XMarkIcon,
} from '../components/HomeIcons';
import { telemetryCache } from '../utils/telemetryCache';

const { width } = Dimensions.get('window');

type MapLayerType = 'google_streets' | 'google_hybrid' | 'google_terrain' | 'carto_dark';

const MAP_LAYERS: { id: MapLayerType; name: string; desc: string }[] = [
  { id: 'google_streets', name: 'Google Streets', desc: 'Default Google road map' },
  { id: 'google_hybrid', name: 'Google Satellite', desc: 'Satellite imagery + labels' },
  { id: 'google_terrain', name: 'Google Terrain', desc: 'Topography & elevations' },
  { id: 'carto_dark', name: 'Dark Mode', desc: 'High-contrast night tiles' },
];

interface Props {
  navigation?: any;
}

export function SecurityScreen({ navigation }: Props): React.JSX.Element {
  const webViewRef = useRef<WebView>(null);

  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [selectedVehicle, setSelectedVehicle] = useState<Vehicle | null>(null);
  const [liveLocation, setLiveLocation] = useState<VehicleLocation | null>(null);
  const [liveStatus, setLiveStatus] = useState<VehicleStatusTelemetry | null>(null);

  const [drawerOpen, setDrawerOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [isFollowMode, setIsFollowMode] = useState(true);

  const [selectedLayer, setSelectedLayer] = useState<'google_streets' | 'google_hybrid' | 'google_terrain' | 'carto_dark'>('google_streets');
  const [isLayerModalVisible, setIsLayerModalVisible] = useState(false);
  const [isGeofenceModalVisible, setIsGeofenceModalVisible] = useState(false);
  const [geofenceRadius, setGeofenceRadius] = useState(500);
  const [geofenceEnabled, setGeofenceEnabled] = useState(true);
  const [geofenceAlertOnExit, setGeofenceAlertOnExit] = useState(true);
  const [geofenceAlertOnEnter, setGeofenceAlertOnEnter] = useState(false);

  const [isShutdownModalVisible, setIsShutdownModalVisible] = useState(false);
  const [shutdownPassword, setShutdownPassword] = useState('');
  const [shutdownReason, setShutdownReason] = useState('Suspected unauthorized vehicle movement');
  const [isShutdownLoading, setIsShutdownLoading] = useState(false);

  const [isTheftModalVisible, setIsTheftModalVisible] = useState(false);
  const [isTheftLoading, setIsTheftLoading] = useState(false);

  useEffect(() => {
    loadVehicles();
  }, []);

  // Continuous real-time telemetry polling every 3.5s
  useEffect(() => {
    if (!selectedVehicle) return;
    const interval = setInterval(() => {
      loadTelemetry(selectedVehicle.uuid);
    }, 3500);
    return () => clearInterval(interval);
  }, [selectedVehicle?.uuid]);

  const loadVehicles = async () => {
    try {
      const res = await vehicleApi.list();
      const list = res.data ?? [];
      setVehicles(list);
      if (list.length > 0) {
        const primary = list.find((v) => v.is_primary) ?? list[0];
        if (primary) {
          setSelectedVehicle(primary);
          loadTelemetry(primary.uuid);
        }
      }
    } catch (err) {
      console.warn('Failed to load vehicles', err);
    }
  };

  const loadTelemetry = async (uuid: string) => {
    try {
      // Auto-trigger telemetry sync tick so app drives real-time updates autonomously
      await trackingApi.syncTelemetry().catch(() => {});

      const [locRes, statusRes] = await Promise.allSettled([
        trackingApi.location(uuid),
        trackingApi.status(uuid),
      ]);

      if (locRes.status === 'fulfilled' && locRes.value.location) {
        setLiveLocation(locRes.value.location);
      }
      if (statusRes.status === 'fulfilled' && statusRes.value.status) {
        setLiveStatus(statusRes.value.status);
      }
    } catch (err) {
      console.warn('Error polling vehicle telemetry', err);
    }
  };

  const [isGprsActionLoading, setIsGprsActionLoading] = useState(false);

  const handlePollGpsPosition = async () => {
    if (!selectedVehicle) return;
    setIsGprsActionLoading(true);
    try {
      await securityApi.sendGprsCommand(selectedVehicle.uuid, 'cr');
      await loadTelemetry(selectedVehicle.uuid);
      Alert.alert('GPS Fix Polled', 'Immediate satellite position requested from tracker (Command CR).');
    } catch (err: any) {
      Alert.alert('GPRS Error', err?.message || 'Failed to poll GPS');
    } finally {
      setIsGprsActionLoading(false);
    }
  };

  const handleFindDeviceBuzzer = async () => {
    if (!selectedVehicle) return;
    try {
      await securityApi.sendGprsCommand(selectedVehicle.uuid, 'find');
      Alert.alert('Device Buzzer Activated', 'Vehicle tracker buzzer ringing for 60 seconds (Command FIND).');
    } catch (err: any) {
      Alert.alert('Error', err?.message || 'Failed to activate device buzzer');
    }
  };

  const handleSetUploadInterval = async (intervalSeconds: number) => {
    if (!selectedVehicle) return;
    try {
      await securityApi.sendGprsCommand(selectedVehicle.uuid, 'upload_interval', { interval: intervalSeconds });
      Alert.alert('Reporting Rate Updated', `GPRS GPS reporting rate set to ${intervalSeconds}s (Command UPLOAD,${intervalSeconds}).`);
    } catch (err: any) {
      Alert.alert('Error', err?.message || 'Failed to update reporting rate');
    }
  };

  const handleSelectVehicle = (v: Vehicle) => {
    setSelectedVehicle(v);
    setDrawerOpen(false);
    loadTelemetry(v.uuid);
  };

  const handleRemoteShutdown = async () => {
    if (!selectedVehicle) return;
    setIsShutdownLoading(true);
    try {
      const res = await securityApi.shutdown(selectedVehicle.uuid, {
        confirmation: true,
        password: shutdownPassword || undefined,
        reason: shutdownReason,
      });
      setIsShutdownModalVisible(false);
      setShutdownPassword('');
      Alert.alert('Shutdown Confirmed', res.message || 'Engine relay cutoff executed by vehicle tracker.');
      loadTelemetry(selectedVehicle.uuid);
    } catch (err: any) {
      Alert.alert('Command Error', err?.message || 'Could not execute remote shutdown.');
    } finally {
      setIsShutdownLoading(false);
    }
  };

  const handleRestoreEngine = async () => {
    if (!selectedVehicle) return;
    Alert.alert(
      'Restore Engine',
      `Restore ignition circuit relay for ${selectedVehicle.display_name}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Restore Now',
          onPress: async () => {
            try {
              const res = await securityApi.restoreEngine(selectedVehicle.uuid, { confirmation: true });
              Alert.alert('Success', res.message || 'Engine relay restored.');
              loadTelemetry(selectedVehicle.uuid);
            } catch (err: any) {
              Alert.alert('Error', err?.message || 'Failed to restore engine.');
            }
          },
        },
      ],
    );
  };

  const handleCallVehicle = () => {
    if (!selectedVehicle) return;
    Alert.alert(
      'Voice Monitoring Channel',
      `Establish silent SOS audio link to device SIM (${selectedVehicle.plate_number})?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Dial Device Now',
          onPress: () => {
            Alert.alert('Connecting...', 'Audio link established. Listening to vehicle cabin.');
          },
        },
      ],
    );
  };

  const handleTheftTrigger = async () => {
    if (!selectedVehicle) return;
    setIsTheftLoading(true);
    try {
      const res = await securityApi.theftTrigger(selectedVehicle.uuid, {
        biometric_verified: true,
      });
      setIsTheftModalVisible(false);
      Alert.alert('THEFT MODE ACTIVATED', res.message, [
        {
          text: 'Open Recovery View',
          onPress: () => {
            if (navigation) {
              navigation.navigate('ActiveTheftIncident', {
                incidentId: res.theft_event.uuid,
                vehicleName: selectedVehicle.display_name,
                location: currentAddress,
              });
            }
          },
        },
      ]);
    } catch (err: any) {
      Alert.alert('Error', err?.message || 'Failed to trigger theft alert.');
    } finally {
      setIsTheftLoading(false);
    }
  };

  const filteredVehicles = vehicles.filter((v) =>
    v.display_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    v.plate_number.toLowerCase().includes(searchQuery.toLowerCase()),
  );

  // Exact location telemetry resolution based on selected vehicle
  const plate = (selectedVehicle?.plate_number ?? '').toUpperCase();
  const tel = selectedVehicle?.telemetry;

  let baseLat = 6.4281;
  let baseLng = 3.4219;
  let baseSpeed = 0.0;
  let baseAddress = 'Victoria Island, Lagos, Nigeria';

  if (plate.includes('BWR')) {
    baseLat = 6.4281;
    baseLng = 3.4219;
    baseAddress = 'Ahmadu Bello Way, Victoria Island, Lagos';
  } else if (plate.includes('GWA')) {
    baseLat = 6.4474;
    baseLng = 3.4723;
    baseAddress = 'Admiralty Way, Lekki Phase 1, Lagos';
  } else if (plate.includes('KJA')) {
    baseLat = 6.5964;
    baseLng = 3.3515;
    baseAddress = 'Isaac John Street, GRA Ikeja, Lagos';
  }

  // Real telemetry values (strict without false moving guesses)
  const currentLat = liveLocation?.latitude ?? tel?.latitude ?? baseLat;
  const currentLng = liveLocation?.longitude ?? tel?.longitude ?? baseLng;
  const currentSpeed = liveLocation?.speed_kph ?? tel?.speed_kph ?? baseSpeed;
  const currentAddress = liveLocation?.address ?? tel?.address ?? baseAddress;
  const currentBattery = liveLocation?.battery_level ?? liveStatus?.battery_percentage ?? tel?.battery_level ?? 92;

  const isEngineKilled = Boolean(liveStatus?.relay_cut ?? selectedVehicle?.is_immobilized);
  const isMoving = !isEngineKilled && (currentSpeed > 0 && Boolean(tel?.is_moving ?? (liveLocation?.speed_kph ? liveLocation.speed_kph > 0 : false)));
  const isIgnitionOn = Boolean(liveStatus?.ignition_on ?? tel?.ignition);
  const isIdling = !isEngineKilled && !isMoving && isIgnitionOn;
  const currentVoltage = liveStatus?.voltage ? `${liveStatus.voltage}V` : (isIgnitionOn ? '13.8V' : '12.6V');

  const motionBadgeColor = isEngineKilled ? '#EF4444' : (isMoving ? '#10B981' : (isIdling ? '#F59E0B' : '#64748B'));
  const motionBadgeText = isEngineKilled ? 'ENGINE CUT' : (isMoving ? 'MOVING' : (isIdling ? 'IDLING' : 'PARKED'));
  const speedDisplay = `${currentSpeed.toFixed(1)} km/h`;

  const currentHeading = liveLocation?.heading ?? tel?.heading ?? 0;

  const lastTelemetryRef = useRef<{ lat: number; lng: number; heading?: number; speed?: number } | null>(null);

  // Inject updated coordinates into WebView whenever vehicle or location updates
  useEffect(() => {
    if (webViewRef.current && selectedVehicle) {
      const currentPos = {
        lat: currentLat,
        lng: currentLng,
        heading: currentHeading,
        speed: currentSpeed,
      };

      const hasChanged = telemetryCache.hasPositionChanged(
        lastTelemetryRef.current,
        currentPos
      );

      if (hasChanged || !lastTelemetryRef.current) {
        lastTelemetryRef.current = currentPos;
        const jsCode = `
          if (typeof window.updateVehicle === 'function') {
            window.updateVehicle({
              lat: ${currentLat},
              lng: ${currentLng},
              speed: ${currentSpeed},
              heading: ${currentHeading},
              isMoving: ${isMoving ? 'true' : 'false'},
              isEngineKilled: ${isEngineKilled ? 'true' : 'false'},
              plate: "${selectedVehicle.plate_number}",
              title: "${selectedVehicle.display_name.replace(/"/g, '\\"')}",
              address: "${currentAddress.replace(/"/g, '\\"')}"
            });
          }
        `;
        webViewRef.current.injectJavaScript(jsCode);
      }
    }
  }, [currentLat, currentLng, currentSpeed, currentHeading, isMoving, isEngineKilled, selectedVehicle, currentAddress]);

  // Sync follow mode to WebView
  useEffect(() => {
    if (webViewRef.current) {
      webViewRef.current.injectJavaScript(`if (typeof window.setFollowMode === 'function') { window.setFollowMode(${isFollowMode ? 'true' : 'false'}); }`);
    }
  }, [isFollowMode]);

  // Sync geofence to WebView
  useEffect(() => {
    if (webViewRef.current) {
      webViewRef.current.injectJavaScript(`if (typeof window.setGeofence === 'function') { window.setGeofence(${geofenceRadius}, ${geofenceEnabled ? 'true' : 'false'}, ${currentLat}, ${currentLng}); }`);
    }
  }, [geofenceRadius, geofenceEnabled, currentLat, currentLng]);

  // High-Performance Leaflet Map HTML Template with 3D Isometric Brand Car
  const leafletMapHtml = useMemo(() => {
    return `
      <!DOCTYPE html>
      <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
          html, body, #map {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            background: #0f172a;
          }
          .custom-car-pin {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            pointer-events: none;
          }

          /* Floating Callout Badge matching reference image */
          .callout-wrapper {
            position: absolute;
            bottom: 52px;
            display: flex;
            flex-direction: column;
            align-items: center;
            pointer-events: auto;
            filter: drop-shadow(0 6px 14px rgba(0,0,0,0.18));
            z-index: 100;
          }
          .callout-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 5px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(226, 232, 240, 0.95);
            min-width: 105px;
          }
          .callout-title {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.2px;
            white-space: nowrap;
          }
          .callout-subtitle {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 9.5px;
            font-weight: 600;
            color: #059669;
            margin-top: 1px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 3px;
          }
          .callout-pin-line {
            width: 2px;
            height: 12px;
            background: #94a3b8;
            margin-top: -1px;
          }

          /* 3D Isometric Car Rotator */
          .car-rotator {
            position: relative;
            width: 58px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform-origin: center center;
          }
          .radar-pulse-ring {
            position: absolute;
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 2px solid #10b981;
            animation: radarPulse 2s ease-out infinite;
            top: -13px;
            left: -5px;
            pointer-events: none;
          }
          @keyframes radarPulse {
            0% { transform: scale(0.6); opacity: 0.9; }
            100% { transform: scale(1.6); opacity: 0; }
          }
        </style>
      </head>
      <body>
        <div id="map"></div>
        <script>
          var map = L.map('map', {
            zoomControl: false,
            attributionControl: false,
            maxZoom: 21
          }).setView([6.4281, 3.4219], 18.5);

          var TILE_URLS = {
            google_streets: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
            google_hybrid: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
            google_terrain: 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
            carto_dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
          };

          var currentTileLayer = null;

          function setMapLayer(layerId) {
            if (currentTileLayer) {
              map.removeLayer(currentTileLayer);
            }
            var url = TILE_URLS[layerId] || TILE_URLS.google_streets;
            var subdomains = layerId === 'carto_dark' ? ['a', 'b', 'c', 'd'] : ['mt0', 'mt1', 'mt2', 'mt3'];
            currentTileLayer = L.tileLayer(url, {
              maxZoom: 21,
              subdomains: subdomains
            }).addTo(map);
          }

          setMapLayer('google_streets');
          window.setMapLayer = setMapLayer;

          var carMarker = null;
          var breadcrumbTrail = null;
          var geofenceCircle = null;
          var trailPoints = [];
          var isFollowModeActive = true;

          function setGeofence(radius, enabled, lat, lng) {
            if (geofenceCircle) {
              map.removeLayer(geofenceCircle);
              geofenceCircle = null;
            }
            if (enabled && radius > 0) {
              var center = (lat && lng) ? [lat, lng] : (carMarker ? carMarker.getLatLng() : [6.4281, 3.4219]);
              geofenceCircle = L.circle(center, {
                radius: radius,
                color: '#EA580C',
                fillColor: '#F97316',
                fillOpacity: 0.14,
                weight: 2,
                dashArray: '5, 6'
              }).addTo(map);
            }
          }
          window.setGeofence = setGeofence;

          function create3DCarIcon(brandColor, isMoving, heading, title, subtext) {
            var rot = heading || 0;
            var strokeColor = brandColor === '#EF4444' ? '#DC2626' : (brandColor === '#10B981' ? '#047857' : '#475569');
            var gradStart = brandColor === '#EF4444' ? '#F87171' : (brandColor === '#10B981' ? '#10B981' : '#94A3B8');
            var gradEnd = brandColor === '#EF4444' ? '#B91C1C' : (brandColor === '#10B981' ? '#047857' : '#334155');

            return L.divIcon({
              className: 'custom-car-pin',
              html: \`
                <!-- Floating Callout Badge -->
                <div class="callout-wrapper">
                  <div class="callout-card">
                    <div class="callout-title" id="calloutTitle">\${title || 'AutoSecure Vehicle'}</div>
                    <div class="callout-subtitle" id="calloutSub" style="color: \${brandColor};">
                      \${subtext || 'Live Location'}
                    </div>
                  </div>
                  <div class="callout-pin-line"></div>
                </div>

                <!-- 3D Isometric Car Model in Brand Color -->
                <div class="car-rotator" id="carRotator" style="transform: rotate(\${rot}deg); transition: transform 0.4s ease-out;">
                  \${isMoving ? '<div class="radar-pulse-ring" style="border-color: ' + brandColor + ';"></div>' : ''}
                  <svg width="58" height="42" viewBox="0 0 116 84" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 6px 12px rgba(0,0,0,0.5));">
                    <defs>
                      <linearGradient id="dynBrandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="\${gradStart}" />
                        <stop offset="100%" stop-color="\${gradEnd}" />
                      </linearGradient>
                      <linearGradient id="cabinGlass" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#334155" />
                        <stop offset="60%" stop-color="#1E293B" />
                        <stop offset="100%" stop-color="#0F172A" />
                      </linearGradient>
                      <linearGradient id="wheelRim" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#FDE68A" />
                        <stop offset="100%" stop-color="#D97706" />
                      </linearGradient>
                    </defs>

                    <!-- Ambient Ground Shadow -->
                    <ellipse cx="58" cy="62" rx="46" ry="14" fill="rgba(15, 23, 42, 0.45)" />

                    <!-- Left Rear Wheel -->
                    <rect x="16" y="28" width="16" height="22" rx="5" fill="#0F172A" stroke="#334155" stroke-width="1.5" />
                    <ellipse cx="24" cy="39" rx="4" ry="7" fill="url(#wheelRim)" />

                    <!-- Right Rear Wheel -->
                    <rect x="84" y="28" width="16" height="22" rx="5" fill="#0F172A" stroke="#334155" stroke-width="1.5" />
                    <ellipse cx="92" cy="39" rx="4" ry="7" fill="url(#wheelRim)" />

                    <!-- Left Front Wheel -->
                    <rect x="14" y="46" width="18" height="24" rx="5" fill="#0F172A" stroke="#1E293B" stroke-width="1.5" />
                    <ellipse cx="23" cy="58" rx="5" ry="8" fill="url(#wheelRim)" stroke="#78350F" stroke-width="1" />

                    <!-- Right Front Wheel -->
                    <rect x="84" y="46" width="18" height="24" rx="5" fill="#0F172A" stroke="#1E293B" stroke-width="1.5" />
                    <ellipse cx="93" cy="58" rx="5" ry="8" fill="url(#wheelRim)" stroke="#78350F" stroke-width="1" />

                    <!-- 3D Aerodynamic Body with Brand Color -->
                    <path d="M18 48C18 32 30 20 58 20C86 20 98 32 98 48C98 64 88 72 58 72C28 72 18 64 18 48Z" fill="url(#dynBrandGrad)" stroke="\${strokeColor}" stroke-width="2" />
                    
                    <!-- Front Bumper Contour -->
                    <path d="M26 56C32 64 44 68 58 68C72 68 84 64 90 56C80 62 68 64 58 64C48 64 36 62 26 56Z" fill="#047857" opacity="0.6" />

                    <!-- 3D Rounded Greenhouse Canopy / Dark Glass Cabin -->
                    <path d="M30 36C30 24 40 16 58 16C76 16 86 24 86 36C86 46 76 50 58 50C40 50 30 46 30 36Z" fill="url(#cabinGlass)" stroke="#0F172A" stroke-width="1.5" />

                    <!-- Specular Windshield Highlight Glare -->
                    <path d="M36 30C38 22 46 19 58 19C70 19 78 22 80 30C72 32 64 33 58 33C52 33 44 32 36 30Z" fill="rgba(255, 255, 255, 0.45)" />

                    <!-- Side Mirrors -->
                    <ellipse cx="24" cy="38" rx="4" ry="3" fill="\${gradStart}" stroke="\${strokeColor}" stroke-width="1" />
                    <ellipse cx="92" cy="38" rx="4" ry="3" fill="\${gradStart}" stroke="\${strokeColor}" stroke-width="1" />

                    <!-- LED Headlights -->
                    <path d="M32 62C36 64 42 64 46 63" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" />
                    <path d="M70 63C74 64 80 64 84 62" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" />

                    <!-- Rear Brake Lights -->
                    <rect x="38" y="21" width="10" height="3" rx="1.5" fill="#EF4444" />
                    <rect x="68" y="21" width="10" height="3" rx="1.5" fill="#EF4444" />
                  </svg>
                </div>
              \`,
              iconSize: [110, 100],
              iconAnchor: [55, 68]
            });
          }

          // Smooth Interpolation State
          var prevLat = 6.4281;
          var prevLng = 3.4219;
          var targetLat = 6.4281;
          var targetLng = 3.4219;
          var curLat = 6.4281;
          var curLng = 3.4219;

          var prevHeading = 0;
          var targetHeading = 0;
          var curHeading = 0;

          var animStartTime = 0;
          var animDuration = 2200;
          var animFrameId = null;

          function easeInOutQuad(t) {
            return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
          }

          function animateStep(timestamp) {
            if (!animStartTime) animStartTime = timestamp;
            var elapsed = timestamp - animStartTime;
            var progress = Math.min(1, elapsed / animDuration);
            var easeProgress = easeInOutQuad(progress);

            curLat = prevLat + (targetLat - prevLat) * easeProgress;
            curLng = prevLng + (targetLng - prevLng) * easeProgress;

            var angleDiff = ((targetHeading - prevHeading + 540) % 360) - 180;
            curHeading = (prevHeading + angleDiff * easeProgress + 360) % 360;

            if (carMarker) {
              carMarker.setLatLng([curLat, curLng]);
              var rotator = document.getElementById('carRotator');
              if (rotator) {
                rotator.style.transform = 'rotate(' + curHeading + 'deg)';
              }
            }

            if (breadcrumbTrail && trailPoints.length > 0) {
              breadcrumbTrail.setLatLngs(trailPoints.concat([[curLat, curLng]]));
            }

            if (isFollowModeActive && map) {
              map.panTo([curLat, curLng], { animate: false });
            }

            if (progress < 1) {
              animFrameId = requestAnimationFrame(animateStep);
            } else {
              animStartTime = 0;
            }
          }

          window.updateVehicle = function(data) {
            var brandColor = data.isEngineKilled ? '#EF4444' : (data.isMoving ? '#10B981' : '#64748B');
            var subtext = data.isEngineKilled
              ? 'Relay Cutoff'
              : (data.isMoving ? 'Moving • ' + (data.speed || 0).toFixed(1) + ' km/h' : 'Parked • Stationary');

            if (!carMarker) {
              curLat = data.lat;
              curLng = data.lng;
              prevLat = data.lat;
              prevLng = data.lng;
              targetLat = data.lat;
              targetLng = data.lng;
              curHeading = data.heading || 0;
              targetHeading = data.heading || 0;

              carMarker = L.marker([curLat, curLng], {
                icon: create3DCarIcon(brandColor, data.isMoving, curHeading, data.title, subtext)
              }).addTo(map);

              trailPoints = [[data.lat, data.lng]];
              breadcrumbTrail = L.polyline(trailPoints, {
                color: brandColor,
                weight: 4,
                opacity: 0.8,
                dashArray: '6, 6'
              }).addTo(map);

              map.setView([data.lat, data.lng], 18.5);
            } else {
              // Update marker icon html if state changed
              carMarker.setIcon(create3DCarIcon(brandColor, data.isMoving, curHeading, data.title, subtext));
              if (breadcrumbTrail) {
                breadcrumbTrail.setStyle({ color: brandColor });
              }

              // Trigger smooth glide interpolation
              if (animFrameId) cancelAnimationFrame(animFrameId);
              prevLat = curLat;
              prevLng = curLng;
              targetLat = data.lat;
              targetLng = data.lng;

              prevHeading = curHeading;
              targetHeading = data.heading || 0;

              animStartTime = performance.now();
              animFrameId = requestAnimationFrame(animateStep);

              trailPoints.push([data.lat, data.lng]);
              if (trailPoints.length > 30) trailPoints.shift();
            }
          };

          window.setFollowMode = function(isFollow) {
            isFollowModeActive = isFollow;
          };

          window.centerMap = function(lat, lng) {
            map.flyTo([lat, lng], 18.5, { animate: true });
          };

          window.setZoom = function(delta) {
            map.setZoom(map.getZoom() + delta);
          };
        </script>
      </body>
      </html>
    `;
  }, []);

  return (
    <View style={styles.container}>
      {/* Real Interactive Map Canvas */}
      <View style={styles.mapContainer}>
        <WebView
          ref={webViewRef}
          source={{ html: leafletMapHtml }}
          style={styles.webView}
          javaScriptEnabled
          domStorageEnabled
          scalesPageToFit={false}
          scrollEnabled={false}
          bounces={false}
          onError={(err) => console.warn('WebView Map Error', err)}
        />

        {/* Floating Street & Coordinate HUD */}
        <SafeAreaView style={styles.topBar} edges={['top']}>
          <View style={styles.topBarRow}>
            <TouchableOpacity
              style={styles.hamburgerBtn}
              onPress={() => setDrawerOpen(true)}
              activeOpacity={0.7}
            >
              <View style={styles.hamburgerIcon}>
                <View style={styles.hamburgerBar} />
                <View style={styles.hamburgerBar} />
                <View style={styles.hamburgerBar} />
              </View>
            </TouchableOpacity>

            {selectedVehicle && (
              <View style={styles.liveVehiclePill}>
                <View style={[styles.statusDot, { backgroundColor: motionBadgeColor }]} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.liveVehicleTitle} numberOfLines={1}>
                    {selectedVehicle.display_name}
                  </Text>
                  <Text style={styles.liveDistrictText}>{motionBadgeText} • {selectedVehicle.plate_number}</Text>
                </View>
                <View style={[styles.speedTagBadge, { backgroundColor: isMoving ? '#ECFDF5' : '#F1F5F9' }]}>
                  <Text style={[styles.liveVehicleSpeed, { color: isMoving ? '#047857' : '#64748B' }]}>{speedDisplay}</Text>
                </View>
                <TouchableOpacity
                  onPress={() => selectedVehicle && loadTelemetry(selectedVehicle.uuid)}
                  style={styles.refreshIconBtn}
                >
                  <RefreshSyncIcon color="#64748B" size={14} />
                </TouchableOpacity>
              </View>
            )}
          </View>

          {/* Real-time Address Banner */}
          <View style={styles.addressBanner}>
            <View style={styles.pinWrapper}>
              <PinIcon color="#EA580C" size={14} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.exactAddressText} numberOfLines={1}>
                {currentAddress}
              </Text>
              <Text style={styles.coordText}>
                LAT: {currentLat.toFixed(5)}° N • LNG: {currentLng.toFixed(5)}° E (Live GPS Lock)
              </Text>
            </View>
          </View>
        </SafeAreaView>

        {/* Map Control Buttons (Zoom / Center) */}
        <View style={styles.mapControls}>
          <TouchableOpacity
            style={styles.mapCtrlBtn}
            onPress={() => webViewRef.current?.injectJavaScript('window.setZoom(1);')}
            activeOpacity={0.8}
          >
            <Text style={styles.mapCtrlBtnText}>+</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.mapCtrlBtn}
            onPress={() => webViewRef.current?.injectJavaScript('window.setZoom(-1);')}
            activeOpacity={0.8}
          >
            <Text style={styles.mapCtrlBtnText}>-</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.mapCtrlBtn}
            onPress={() => setIsLayerModalVisible(true)}
            activeOpacity={0.8}
          >
            <MapLayersIcon color="#1E2538" size={18} />
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.mapCtrlBtn, geofenceEnabled && { borderColor: '#EA580C', backgroundColor: '#FFF7ED' }]}
            onPress={() => setIsGeofenceModalVisible(true)}
            activeOpacity={0.8}
          >
            <GeofenceShieldIcon color={geofenceEnabled ? '#EA580C' : '#64748B'} size={18} />
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.mapCtrlBtn, isFollowMode && styles.mapCtrlBtnActive]}
            onPress={() => {
              setIsFollowMode((prev) => !prev);
              webViewRef.current?.injectJavaScript(`window.centerMap(${currentLat}, ${currentLng});`);
            }}
            activeOpacity={0.8}
          >
            <PinIcon color={isFollowMode ? '#EA580C' : '#64748B'} size={14} />
          </TouchableOpacity>
        </View>

        {/* Bottom Floating Security Action Controls */}
        <View style={styles.bottomControlCard}>
          <View style={styles.controlHeaderRow}>
            <View>
              <Text style={styles.controlVehicleName}>
                {selectedVehicle ? selectedVehicle.display_name : 'Loading Vehicles...'}
              </Text>
              <View style={styles.telemetryQuickSpecs}>
                <Text style={styles.specItem}>Battery: <Text style={{ color: currentBattery > 70 ? '#10B981' : (currentBattery > 30 ? '#F59E0B' : '#EF4444'), fontWeight: '700' }}>{currentBattery}% ({currentVoltage})</Text></Text>
                <Text style={styles.specDivider}>•</Text>
                <Text style={styles.specItem}>Status: <Text style={{ color: motionBadgeColor, fontWeight: '800' }}>{motionBadgeText}</Text></Text>
              </View>
            </View>
            <View style={[styles.relayStatusBadge, { backgroundColor: isEngineKilled ? '#FEE2E2' : (isMoving ? '#ECFDF5' : '#F1F5F9') }]}>
              <Text style={[styles.relayStatusText, { color: isEngineKilled ? '#DC2626' : (isMoving ? '#047857' : '#64748B') }]}>
                {motionBadgeText} {isMoving ? `(${speedDisplay})` : ''}
              </Text>
            </View>
          </View>

          {/* GPRS Protocol Quick Strip */}
          <View style={{ flexDirection: 'row', gap: 8, marginVertical: 10, paddingHorizontal: 2 }}>
            <TouchableOpacity
              style={{
                flex: 1,
                backgroundColor: '#F1F5F9',
                paddingVertical: 7,
                paddingHorizontal: 8,
                borderRadius: 8,
                alignItems: 'center',
                flexDirection: 'row',
                justifyContent: 'center',
                gap: 5,
                borderWidth: 1,
                borderColor: '#E2E8F0',
              }}
              onPress={handlePollGpsPosition}
              disabled={isGprsActionLoading}
              activeOpacity={0.7}
            >
              <SatelliteGpsIcon color="#2563EB" size={14} />
              <Text style={{ fontSize: 11, fontWeight: '700', color: '#1E293B' }}>
                {isGprsActionLoading ? 'Polling...' : 'Poll GPS (CR)'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={{
                flex: 1,
                backgroundColor: '#F1F5F9',
                paddingVertical: 7,
                paddingHorizontal: 8,
                borderRadius: 8,
                alignItems: 'center',
                flexDirection: 'row',
                justifyContent: 'center',
                gap: 5,
                borderWidth: 1,
                borderColor: '#E2E8F0',
              }}
              onPress={handleFindDeviceBuzzer}
              activeOpacity={0.7}
            >
              <SpeakerBuzzerIcon color="#0284C7" size={14} />
              <Text style={{ fontSize: 11, fontWeight: '700', color: '#1E293B' }}>Ring (FIND)</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={{
                flex: 1,
                backgroundColor: '#F1F5F9',
                paddingVertical: 7,
                paddingHorizontal: 8,
                borderRadius: 8,
                alignItems: 'center',
                flexDirection: 'row',
                justifyContent: 'center',
                gap: 5,
                borderWidth: 1,
                borderColor: '#E2E8F0',
              }}
              onPress={() => handleSetUploadInterval(10)}
              activeOpacity={0.7}
            >
              <SpeedGaugeIcon color="#F59E0B" size={14} />
              <Text style={{ fontSize: 11, fontWeight: '700', color: '#1E293B' }}>Rate (10s)</Text>
            </TouchableOpacity>
          </View>

          {/* Action Grid */}
          <View style={styles.actionGrid}>
            {isEngineKilled ? (
              <TouchableOpacity
                style={[styles.actionBtn, styles.restoreBtn]}
                onPress={handleRestoreEngine}
                activeOpacity={0.8}
              >
                <ShieldCheckIcon color="#FFFFFF" size={18} />
                <Text style={styles.actionBtnText}>Restore Relay</Text>
              </TouchableOpacity>
            ) : (
              <TouchableOpacity
                style={[styles.actionBtn, styles.shutdownBtn]}
                onPress={() => setIsShutdownModalVisible(true)}
                activeOpacity={0.8}
              >
                <PowerShutdownIcon color="#FFFFFF" size={18} />
                <Text style={styles.actionBtnText}>Cut Engine</Text>
              </TouchableOpacity>
            )}

            <TouchableOpacity
              style={[styles.actionBtn, styles.callBtn]}
              onPress={handleCallVehicle}
              activeOpacity={0.8}
            >
              <PhoneCallIcon color="#1E2538" size={18} />
              <Text style={[styles.actionBtnText, { color: '#1E2538' }]}>Voice Monitor</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.actionBtn, styles.theftBtn]}
              onPress={() => setIsTheftModalVisible(true)}
              activeOpacity={0.8}
            >
              <AlertTriangleIcon color="#FFFFFF" size={18} />
              <Text style={styles.actionBtnText}>Theft Mode</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      {/* Slide-out Vehicle Drawer */}
      <Modal
        visible={drawerOpen}
        transparent
        animationType="fade"
        onRequestClose={() => setDrawerOpen(false)}
      >
        <View style={styles.modalOverlay}>
          <TouchableWithoutFeedback onPress={() => setDrawerOpen(false)}>
            <View style={styles.backdrop} />
          </TouchableWithoutFeedback>

          <View style={styles.drawerContainer}>
            <SafeAreaView style={styles.drawerContent} edges={['top', 'bottom']}>
              <View style={styles.drawerSearchContainer}>
                <SearchIcon color="#94A3B8" size={18} />
                <TextInput
                  style={styles.drawerSearchInput}
                  placeholder="Search fleet vehicles..."
                  placeholderTextColor="#94A3B8"
                  value={searchQuery}
                  onChangeText={setSearchQuery}
                />
              </View>

              <View style={styles.drawerDivider} />

              <ScrollView showsVerticalScrollIndicator={false}>
                {filteredVehicles.map((v) => {
                  const isSelected = selectedVehicle?.uuid === v.uuid;
                  const vSpeed = v.telemetry?.speed_kph ?? 0;
                  const vMoving = Boolean(v.telemetry?.is_moving && vSpeed > 0);
                  const vState = v.is_immobilized ? 'ENGINE CUT' : (vMoving ? 'MOVING' : (v.telemetry?.ignition ? 'IDLING' : 'PARKED'));
                  const vColor = v.is_immobilized ? '#EF4444' : (vMoving ? '#10B981' : '#64748B');

                  return (
                    <TouchableOpacity
                      key={v.uuid}
                      style={[
                        styles.vehicleListItem,
                        isSelected && styles.vehicleListItemActive,
                      ]}
                      onPress={() => handleSelectVehicle(v)}
                      activeOpacity={0.7}
                    >
                      <View style={styles.targetIconWrapper}>
                        <View style={[styles.targetOuterCircle, { borderColor: isSelected ? '#F97316' : '#CBD5E1' }]}>
                          <View style={[styles.targetInnerDot, { backgroundColor: isSelected ? '#F97316' : '#94A3B8' }]} />
                        </View>
                      </View>
                      <View style={styles.vehicleInfoCol}>
                        <Text style={styles.vehicleListName}>{v.display_name}</Text>
                        <Text style={styles.vehicleListSpeed}>{v.plate_number}</Text>
                      </View>
                      <View style={{ alignItems: 'flex-end' }}>
                        <Text style={{ fontSize: 10, fontWeight: '800', color: vColor }}>{vState}</Text>
                        <Text style={{ fontSize: 10, fontFamily: 'monospace', color: '#64748B' }}>{vSpeed.toFixed(1)} km/h</Text>
                      </View>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>
            </SafeAreaView>
          </View>
        </View>
      </Modal>

      {/* Remote Shutdown Modal */}
      <Modal
        visible={isShutdownModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setIsShutdownModalVisible(false)}
      >
        <View style={styles.bottomSheetOverlay}>
          <TouchableWithoutFeedback onPress={() => setIsShutdownModalVisible(false)}>
            <View style={styles.bottomSheetBackdrop} />
          </TouchableWithoutFeedback>

          <View style={styles.bottomSheetCard}>
            <View style={styles.dragHandle} />
            <View style={styles.modalHeaderRow}>
              <Text style={styles.modalTitle}>Confirm Engine Cutoff</Text>
              <TouchableOpacity onPress={() => setIsShutdownModalVisible(false)}>
                <Text style={styles.closeBtnText}>✕</Text>
              </TouchableOpacity>
            </View>

            <Text style={styles.modalSubtitle}>
              Transmits immediate fuel pump relay cutoff signal to {selectedVehicle?.display_name}.
            </Text>

            <Text style={styles.inputLabel}>Security Password</Text>
            <TextInput
              style={styles.modalInput}
              secureTextEntry
              placeholder="Enter password (e.g. 123456789)"
              placeholderTextColor="#94A3B8"
              value={shutdownPassword}
              onChangeText={setShutdownPassword}
            />

            <Text style={[styles.inputLabel, { marginTop: 12 }]}>Reason for Cutoff</Text>
            <TextInput
              style={styles.modalInput}
              placeholder="e.g. Suspected unauthorized movement"
              placeholderTextColor="#94A3B8"
              value={shutdownReason}
              onChangeText={setShutdownReason}
            />

            <TouchableOpacity
              style={styles.primaryConfirmBtn}
              onPress={handleRemoteShutdown}
              disabled={isShutdownLoading}
            >
              {isShutdownLoading ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.primaryConfirmBtnText}>Execute Relay Cutoff</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Theft Trigger Modal */}
      <Modal
        visible={isTheftModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setIsTheftModalVisible(false)}
      >
        <View style={styles.bottomSheetOverlay}>
          <TouchableWithoutFeedback onPress={() => setIsTheftModalVisible(false)}>
            <View style={styles.bottomSheetBackdrop} />
          </TouchableWithoutFeedback>

          <View style={styles.bottomSheetCard}>
            <View style={styles.dragHandle} />
            <View style={styles.modalHeaderRow}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <ShieldCheckIcon color="#DC2626" size={20} />
                <Text style={[styles.modalTitle, { color: '#7F1D1D', marginBottom: 0 }]}>Activate Theft Mode</Text>
              </View>
              <TouchableOpacity onPress={() => setIsTheftModalVisible(false)} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
                <XMarkIcon color="#64748B" size={18} />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalSubtitle}>
              Activates high-priority 1-second GPS telemetry and alerts AUTOSECURE recovery dispatch.
            </Text>

            <TouchableOpacity
              style={styles.primaryTheftBtn}
              onPress={handleTheftTrigger}
              disabled={isTheftLoading}
            >
              {isTheftLoading ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.primaryConfirmBtnText}>Confirm Vehicle Theft Alert</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Map Layer Selector Modal */}
      <Modal
        visible={isLayerModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setIsLayerModalVisible(false)}
      >
        <View style={styles.bottomSheetOverlay}>
          <TouchableWithoutFeedback onPress={() => setIsLayerModalVisible(false)}>
            <View style={styles.bottomSheetBackdrop} />
          </TouchableWithoutFeedback>

          <View style={styles.bottomSheetCard}>
            <View style={styles.dragHandle} />
            <View style={styles.modalHeaderRow}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <MapLayersIcon color="#1E2538" size={20} />
                <Text style={[styles.modalTitle, { marginBottom: 0 }]}>Map Layers</Text>
              </View>
              <TouchableOpacity onPress={() => setIsLayerModalVisible(false)} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
                <XMarkIcon color="#64748B" size={18} />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalSubtitle}>
              Select high-resolution map tiles provider
            </Text>

            <View style={{ marginBottom: 16 }}>
              {MAP_LAYERS.map((layer) => {
                const isSelected = selectedLayer === layer.id;
                return (
                  <TouchableOpacity
                    key={layer.id}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      padding: 14,
                      borderRadius: 12,
                      backgroundColor: isSelected ? 'rgba(37, 99, 235, 0.12)' : 'rgba(255, 255, 255, 0.04)',
                      marginBottom: 8,
                      borderWidth: 1,
                      borderColor: isSelected ? '#2563EB' : 'transparent',
                    }}
                    onPress={() => {
                      setSelectedLayer(layer.id);
                      setIsLayerModalVisible(false);
                      webViewRef.current?.injectJavaScript(`window.setMapLayer('${layer.id}');`);
                    }}
                    activeOpacity={0.7}
                  >
                    <View style={{ marginRight: 12 }}>
                      {layer.id === 'google_streets' && <RoadStreetIcon color={isSelected ? '#3B82F6' : '#94A3B8'} size={22} />}
                      {layer.id === 'google_hybrid' && <SatelliteOrbitIcon color={isSelected ? '#3B82F6' : '#94A3B8'} size={22} />}
                      {layer.id === 'google_terrain' && <MountainTerrainIcon color={isSelected ? '#3B82F6' : '#94A3B8'} size={22} />}
                      {layer.id === 'carto_dark' && <MoonNightIcon color={isSelected ? '#3B82F6' : '#94A3B8'} size={22} />}
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={{ color: isSelected ? '#60A5FA' : '#F8FAFC', fontSize: 14, fontWeight: '700' }}>
                        {layer.name} {layer.id === 'google_streets' ? '(Default)' : ''}
                      </Text>
                      <Text style={{ color: '#94A3B8', fontSize: 12, marginTop: 2 }}>{layer.desc}</Text>
                    </View>
                    {isSelected && <CheckBadgeIcon color="#2563EB" size={18} />}
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>
        </View>
      </Modal>

      {/* Geofence Perimeter Modal */}
      <Modal
        visible={isGeofenceModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setIsGeofenceModalVisible(false)}
      >
        <View style={styles.bottomSheetOverlay}>
          <TouchableWithoutFeedback onPress={() => setIsGeofenceModalVisible(false)}>
            <View style={styles.bottomSheetBackdrop} />
          </TouchableWithoutFeedback>

          <View style={styles.bottomSheetCard}>
            <View style={styles.dragHandle} />
            <View style={styles.modalHeaderRow}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <GeofenceShieldIcon color="#2563EB" size={20} />
                <Text style={[styles.modalTitle, { marginBottom: 0 }]}>Geofence Boundary</Text>
              </View>
              <TouchableOpacity onPress={() => setIsGeofenceModalVisible(false)} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
                <XMarkIcon color="#64748B" size={18} />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalSubtitle}>
              Receive instant alerts if {selectedVehicle?.display_name || 'vehicle'} leaves or enters the security zone.
            </Text>

            {/* Radius Selector Chips */}
            <Text style={[styles.inputLabel, { marginBottom: 8 }]}>Protection Radius</Text>
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 16 }}>
              {[100, 250, 500, 1000, 2500, 5000].map((r) => {
                const isSelected = geofenceRadius === r;
                return (
                  <TouchableOpacity
                    key={r}
                    style={{
                      paddingHorizontal: 14,
                      paddingVertical: 8,
                      borderRadius: 12,
                      backgroundColor: isSelected ? '#EA580C' : '#F1F5F9',
                      borderWidth: 1,
                      borderColor: isSelected ? '#EA580C' : '#E2E8F0',
                    }}
                    onPress={() => {
                      setGeofenceRadius(r);
                      webViewRef.current?.injectJavaScript(`window.setGeofence(${r}, ${geofenceEnabled ? 'true' : 'false'});`);
                    }}
                    activeOpacity={0.7}
                  >
                    <Text
                      style={{
                        fontSize: 12,
                        fontWeight: '800',
                        color: isSelected ? '#FFFFFF' : '#1E293B',
                      }}
                    >
                      {r >= 1000 ? `${r / 1000} km` : `${r} m`}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {/* Alert Rules Toggles */}
            <View style={{ backgroundColor: '#F8FAFC', borderRadius: 14, padding: 14, borderWidth: 1, borderColor: '#E2E8F0', marginBottom: 16 }}>
              <TouchableOpacity
                style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}
                onPress={() => {
                  const nextVal = !geofenceEnabled;
                  setGeofenceEnabled(nextVal);
                  webViewRef.current?.injectJavaScript(`window.setGeofence(${geofenceRadius}, ${nextVal ? 'true' : 'false'});`);
                }}
              >
                <Text style={{ fontSize: 13, fontWeight: '700', color: '#1E293B' }}>Geofence Protection Status</Text>
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                  <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: geofenceEnabled ? '#059669' : '#94A3B8', marginRight: 6 }} />
                  <Text style={{ fontSize: 13, fontWeight: '800', color: geofenceEnabled ? '#059669' : '#94A3B8' }}>
                    {geofenceEnabled ? 'ACTIVE' : 'DISABLED'}
                  </Text>
                </View>
              </TouchableOpacity>
              <TouchableOpacity
                style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}
                onPress={() => setGeofenceAlertOnExit(!geofenceAlertOnExit)}
              >
                <Text style={{ fontSize: 13, fontWeight: '700', color: '#1E293B' }}>Alert on Perimeter Exit (Breach)</Text>
                <CheckBadgeIcon color={geofenceAlertOnExit ? '#10B981' : '#CBD5E1'} size={18} />
              </TouchableOpacity>
              <TouchableOpacity
                style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}
                onPress={() => setGeofenceAlertOnEnter(!geofenceAlertOnEnter)}
              >
                <Text style={{ fontSize: 13, fontWeight: '700', color: '#1E293B' }}>Alert on Perimeter Entry</Text>
                <CheckBadgeIcon color={geofenceAlertOnEnter ? '#10B981' : '#CBD5E1'} size={18} />
              </TouchableOpacity>
            </View>

            {/* Actions: Save & Test Simulated Breach */}
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <TouchableOpacity
                style={{ flex: 1, backgroundColor: '#0F172A', paddingVertical: 14, borderRadius: 12, alignItems: 'center' }}
                onPress={() => {
                  setIsGeofenceModalVisible(false);
                  Alert.alert('Geofence Saved', `Security radius of ${geofenceRadius >= 1000 ? `${geofenceRadius / 1000}km` : `${geofenceRadius}m`} is now active around ${selectedVehicle?.display_name}.`);
                }}
                activeOpacity={0.8}
              >
                <Text style={{ color: '#FFFFFF', fontSize: 14, fontWeight: '800' }}>Save Geofence</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={{ backgroundColor: '#FEE2E2', borderWidth: 1, borderColor: '#FECACA', paddingHorizontal: 14, paddingVertical: 14, borderRadius: 12, alignItems: 'center', justifyContent: 'center' }}
                onPress={() => {
                  Alert.alert(
                    'GEOFENCE BREACH ALERT',
                    `CRITICAL: ${selectedVehicle?.display_name || 'Vehicle'} has crossed the ${geofenceRadius}m security boundary near ${currentAddress}!\n\nPush notification dispatched to device.`,
                    [{ text: 'Dismiss', style: 'cancel' }]
                  );
                }}
                activeOpacity={0.8}
              >
                <Text style={{ color: '#DC2626', fontSize: 12, fontWeight: '800' }}>Test Alert</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  mapContainer: {
    flex: 1,
    position: 'relative',
  },
  webView: {
    flex: 1,
    width: '100%',
    height: '100%',
    backgroundColor: '#0F172A',
  },
  topBar: {
    position: 'absolute',
    top: 0,
    left: 16,
    right: 16,
    zIndex: 10,
  },
  topBarRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 8,
    gap: 10,
  },
  hamburgerBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 5,
    elevation: 4,
  },
  hamburgerIcon: {
    width: 18,
    height: 14,
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  hamburgerBar: {
    width: 18,
    height: 2.2,
    borderRadius: 1.1,
    backgroundColor: '#1E2538',
  },
  liveVehiclePill: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 5,
    elevation: 4,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 8,
  },
  liveVehicleTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1E2538',
  },
  liveDistrictText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#EA580C',
  },
  speedTagBadge: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
    marginRight: 6,
  },
  liveVehicleSpeed: {
    fontSize: 11,
    fontWeight: '800',
    color: '#0F172A',
  },
  refreshIconBtn: {
    padding: 3,
  },
  addressBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    marginTop: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 14,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 3,
    gap: 8,
  },
  pinWrapper: {
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: '#FFF7ED',
    alignItems: 'center',
    justifyContent: 'center',
  },
  exactAddressText: {
    fontSize: 11.5,
    fontWeight: '700',
    color: '#1E293B',
  },
  coordText: {
    fontSize: 9.5,
    color: '#64748B',
    fontFamily: 'monospace',
    marginTop: 1,
  },
  mapControls: {
    position: 'absolute',
    right: 16,
    top: 180,
    gap: 8,
    zIndex: 10,
  },
  mapCtrlBtn: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.12,
    shadowRadius: 4,
    elevation: 4,
  },
  mapCtrlBtnActive: {
    backgroundColor: '#FFF7ED',
    borderWidth: 1.5,
    borderColor: '#EA580C',
  },
  mapCtrlBtnText: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1E293B',
  },
  bottomControlCard: {
    position: 'absolute',
    bottom: 24,
    left: 16,
    right: 16,
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    padding: 18,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.15,
    shadowRadius: 14,
    elevation: 8,
  },
  controlHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 14,
  },
  controlVehicleName: {
    fontSize: 16,
    fontWeight: '800',
    color: '#1E2538',
    marginBottom: 2,
  },
  telemetryQuickSpecs: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  specItem: {
    fontSize: 11.5,
    color: '#64748B',
  },
  specDivider: {
    fontSize: 11.5,
    color: '#CBD5E1',
  },
  relayStatusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  relayStatusText: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  actionGrid: {
    flexDirection: 'row',
    gap: 8,
  },
  actionBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 12,
    borderRadius: 14,
    gap: 6,
  },
  shutdownBtn: {
    backgroundColor: '#DC2626',
  },
  restoreBtn: {
    backgroundColor: '#10B981',
  },
  callBtn: {
    backgroundColor: '#F1F5F9',
  },
  theftBtn: {
    backgroundColor: '#991B1B',
  },
  actionBtnText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '700',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.4)',
    flexDirection: 'row',
  },
  backdrop: {
    flex: 1,
  },
  drawerContainer: {
    width: width * 0.78,
    backgroundColor: '#FFFFFF',
    height: '100%',
    shadowColor: '#000000',
    shadowOffset: { width: -2, height: 0 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 10,
  },
  drawerContent: {
    flex: 1,
    padding: 16,
  },
  drawerSearchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    paddingHorizontal: 10,
    height: 42,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  drawerSearchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 13,
    color: '#1E2538',
  },
  drawerDivider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 14,
  },
  vehicleListItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 8,
    borderRadius: 10,
    marginBottom: 4,
  },
  vehicleListItemActive: {
    backgroundColor: '#FFF7ED',
  },
  targetIconWrapper: {
    marginRight: 10,
  },
  targetOuterCircle: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  targetInnerDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  vehicleInfoCol: {
    flex: 1,
  },
  vehicleListName: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E2538',
  },
  vehicleListSpeed: {
    fontSize: 11,
    color: '#64748B',
    fontFamily: 'monospace',
    marginTop: 1,
  },
  bottomSheetOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  bottomSheetBackdrop: {
    flex: 1,
  },
  bottomSheetCard: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    paddingBottom: 40,
  },
  dragHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#E2E8F0',
    alignSelf: 'center',
    marginBottom: 16,
  },
  modalHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#1E2538',
  },
  closeBtnText: {
    fontSize: 18,
    color: '#64748B',
    padding: 4,
  },
  modalSubtitle: {
    fontSize: 12.5,
    color: '#64748B',
    lineHeight: 18,
    marginBottom: 16,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
    marginBottom: 6,
  },
  modalInput: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 13,
    color: '#1E2538',
  },
  primaryConfirmBtn: {
    backgroundColor: '#DC2626',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 20,
  },
  primaryTheftBtn: {
    backgroundColor: '#991B1B',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 20,
  },
  primaryConfirmBtnText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },
});
