import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Alert,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { trackingApi, vehicleApi } from '../api/endpoints';
import type { Vehicle } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import {
  BatteryIcon,
  BellIcon,
  ClockIcon,
  DashCamIllustration,
  FilterSlidersIcon,
  NetworkIcon,
  PinIcon,
  SearchIcon,
  SpeedArrowIcon,
} from '../components/HomeIcons';

export interface VehicleCardData {
  id: string;
  name: string;
  status: 'Moving' | 'Parked' | 'Idling' | 'Online' | 'Offline' | 'Stopped';
  speed: string;
  location: string;
  recentUpdate: string;
  battery: string;
  network: string;
  plateNumber?: string;
  vin?: string;
  odometerKm?: number;
  devicesCount?: number;
}

interface HomeScreenProps {
  onNavigateToCamera?: () => void;
  onNavigateToMap?: () => void;
  onNavigateToAccount?: () => void;
  onNavigateToVehicleDetail?: (vehicle: VehicleCardData) => void;
  onNavigateToEventReport?: () => void;
  onNavigateToNotifications?: () => void;
}

type DeviceCategory = 'All Devices' | 'GPS Tracker' | 'DashCam';

export function HomeScreen({
  onNavigateToCamera,
  onNavigateToMap,
  onNavigateToAccount,
  onNavigateToVehicleDetail,
  onNavigateToEventReport,
  onNavigateToNotifications,
}: HomeScreenProps): React.JSX.Element {
  const { user } = useAuth();

  const [rawVehicles, setRawVehicles] = useState<Vehicle[]>([]);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeCategory, setActiveCategory] = useState<DeviceCategory>('All Devices');
  const [activeStatusKey, setActiveStatusKey] = useState<'all' | 'moving' | 'parked' | 'online' | 'offline'>('all');

  // Load real user vehicles from backend API
  const loadVehicles = useCallback(async () => {
    try {
      // Auto-trigger telemetry sync tick so app drives real-time updates autonomously
      await trackingApi.syncTelemetry().catch(() => {});

      const response = await vehicleApi.list();
      if (response && Array.isArray(response.data)) {
        setRawVehicles(response.data);
      }
    } catch {
      setRawVehicles([]);
    }
  }, []);

  useEffect(() => {
    loadVehicles();
    const interval = setInterval(loadVehicles, 8000);
    return () => clearInterval(interval);
  }, [loadVehicles]);

  const onRefresh = async () => {
    setIsRefreshing(true);
    await loadVehicles();
    setIsRefreshing(false);
  };

  // Convert raw API vehicles to display cards
  const vehicleCards: VehicleCardData[] = useMemo(() => {
    return rawVehicles.map((v, idx) => {
      const tel = v.telemetry;
      const hasOnlineDevice = tel?.is_online ?? v.devices?.some((d) => d.is_online) ?? true;
      const rawSpeed = tel?.speed_kph ?? 0.0;
      const isMoving = Boolean(tel?.is_moving && rawSpeed > 0);
      const isIgnitionOn = Boolean(tel?.ignition);
      const isImmobilized = Boolean(v.is_immobilized);

      const speed = `${rawSpeed.toFixed(1)} km/h`;

      let status: 'Moving' | 'Parked' | 'Idling' | 'Online' | 'Offline' | 'Stopped' = 'Parked';
      if (!hasOnlineDevice) {
        status = 'Offline';
      } else if (isImmobilized) {
        status = 'Stopped';
      } else if (isMoving && rawSpeed > 0) {
        status = 'Moving';
      } else if (isIgnitionOn && rawSpeed === 0) {
        status = 'Idling';
      } else {
        status = 'Parked';
      }

      const displayName =
        v.display_name ||
        v.nickname ||
        (v.make && v.model ? `${v.make} ${v.model}` : v.plate_number);

      const locationStr = tel?.address ?? 'Victoria Island, Lagos, Nigeria';
      const batteryVal = tel?.battery_level ?? 94;
      const batteryStr = `${batteryVal}%`;

      const updateStr = tel?.recorded_at
        ? new Date(tel.recorded_at).toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
          })
        : (v.odometer_updated_at
          ? new Date(v.odometer_updated_at).toLocaleTimeString('en-GB', {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit',
            })
          : 'Live Telemetry');

      return {
        id: v.uuid,
        name: displayName,
        status,
        speed,
        location: locationStr,
        recentUpdate: updateStr,
        battery: batteryStr,
        network: hasOnlineDevice ? 'Online' : 'Offline',
        plateNumber: v.plate_number,
        vin: v.vin ?? undefined,
        odometerKm: v.odometer_km ?? undefined,
        devicesCount: v.devices?.length ?? 1,
      };
    });
  }, [rawVehicles]);

  // Compute live counts for status filter pills
  const statusCounts = useMemo(() => {
    const all = vehicleCards.length;
    const moving = vehicleCards.filter((v) => v.status === 'Moving').length;
    const parked = vehicleCards.filter((v) => v.status === 'Parked' || v.status === 'Idling').length;
    const online = vehicleCards.filter((v) => v.network === 'Online').length;
    const offline = vehicleCards.filter((v) => v.network === 'Offline').length;

    return { all, moving, parked, online, offline };
  }, [vehicleCards]);

  // User display name from auth or default 'Iyanu'
  const firstName = useMemo(() => {
    if (user?.name) return user.name.split(' ')[0];
    return 'Iyanu';
  }, [user]);

  // Real paired Dashcams across user's vehicles
  const dashcamDevices = useMemo(() => {
    return rawVehicles.flatMap((v) =>
      (v.devices || [])
        .filter((d) => d.type === 'dashcam')
        .map((d) => ({
          uuid: d.uuid,
          model: d.model || d.brand || 'AutoSecure AI DashCam',
          serialNumber: d.serial_number,
          isOnline: Boolean(d.is_online),
          vehiclePlate: v.plate_number,
          vehicleName: v.display_name || v.nickname || (v.make && v.model ? `${v.make} ${v.model}` : v.plate_number),
        }))
    );
  }, [rawVehicles]);

  // Filtered vehicle cards
  const filteredVehicles = useMemo(() => {
    return vehicleCards.filter((v) => {
      // 1. Search filter
      const search = searchQuery.toLowerCase().trim();
      if (search) {
        const matchesName = v.name.toLowerCase().includes(search);
        const matchesPlate = v.plateNumber?.toLowerCase().includes(search);
        if (!matchesName && !matchesPlate) return false;
      }

      // 2. Status filter
      if (activeStatusKey === 'online' && v.status !== 'Online') return false;
      if (activeStatusKey === 'offline' && (v.status !== 'Stopped' && v.status !== 'Offline')) return false;
      if (activeStatusKey === 'moving' && v.status !== 'Moving') return false;

      return true;
    });
  }, [vehicleCards, searchQuery, activeStatusKey]);

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => void onRefresh()}
            tintColor="#DE8635"
          />
        }
      >
        {/* Header: Greeting & Profile / Notifications */}
        <View style={styles.headerRow}>
          <View style={styles.greetingContainer}>
            <Text style={styles.greetingText}>
              Good Morning <Text style={styles.greetingName}>{firstName}!</Text>
            </Text>
            <Text style={styles.dateText}>16th September, 2026</Text>
          </View>

          <View style={styles.headerRightActions}>
            {/* Notification Bell */}
            <TouchableOpacity
              style={styles.bellButton}
              activeOpacity={0.7}
              onPress={() => {
                if (onNavigateToNotifications) {
                  onNavigateToNotifications();
                } else if (onNavigateToEventReport) {
                  onNavigateToEventReport();
                } else {
                  Alert.alert('Notifications', 'You have 2 unread notifications');
                }
              }}
            >
              <BellIcon color="#1E2538" size={22} />
              <View style={styles.bellBadge}>
                <Text style={styles.bellBadgeText}>2</Text>
              </View>
            </TouchableOpacity>

            {/* Profile Avatar */}
            <TouchableOpacity
              style={styles.avatarButton}
              activeOpacity={0.8}
              onPress={onNavigateToAccount}
            >
              <View style={styles.avatarPlaceholder}>
                <Text style={styles.avatarInitials}>
                  {(firstName ?? 'I').charAt(0).toUpperCase()}
                </Text>
              </View>
            </TouchableOpacity>
          </View>
        </View>

        {/* Search Bar */}
        <View style={styles.searchBarContainer}>
          <SearchIcon color="#94A3B8" size={18} />
          <TextInput
            style={styles.searchInput}
            placeholder="Enter Vehicle Name or Plate"
            placeholderTextColor="#94A3B8"
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          <TouchableOpacity
            style={styles.filterSlidersBtn}
            activeOpacity={0.7}
            onPress={() => Alert.alert('Filter', 'Filter vehicles by status, device type, or group.')}
          >
            <FilterSlidersIcon color="#1E2538" size={18} />
          </TouchableOpacity>
        </View>

        {/* Filter Pills Row 1: Categories */}
        <View style={styles.pillsRow}>
          {(['All Devices', 'GPS Tracker', 'DashCam'] as DeviceCategory[]).map((cat) => {
            const isActive = activeCategory === cat;
            return (
              <TouchableOpacity
                key={cat}
                style={[styles.pillBtn, isActive && styles.pillBtnActive]}
                onPress={() => setActiveCategory(cat)}
                activeOpacity={0.75}
              >
                <Text style={[styles.pillText, isActive && styles.pillTextActive]}>
                  {cat}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {/* Filter Pills Row 2: Live Statuses */}
        <View style={styles.pillsRow}>
          <TouchableOpacity
            style={[styles.statusPillBtn, activeStatusKey === 'all' && styles.pillBtnActive]}
            onPress={() => setActiveStatusKey('all')}
            activeOpacity={0.75}
          >
            <Text style={[styles.statusPillText, activeStatusKey === 'all' && styles.pillTextActive]}>
              All({statusCounts.all})
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.statusPillBtn, activeStatusKey === 'moving' && styles.pillBtnActive]}
            onPress={() => setActiveStatusKey('moving')}
            activeOpacity={0.75}
          >
            <Text style={[styles.statusPillText, activeStatusKey === 'moving' && styles.pillTextActive]}>
              Moving({statusCounts.moving})
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.statusPillBtn, activeStatusKey === 'parked' && styles.pillBtnActive]}
            onPress={() => setActiveStatusKey('parked')}
            activeOpacity={0.75}
          >
            <Text style={[styles.statusPillText, activeStatusKey === 'parked' && styles.pillTextActive]}>
              Parked({statusCounts.parked})
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.statusPillBtn, activeStatusKey === 'online' && styles.pillBtnActive]}
            onPress={() => setActiveStatusKey('online')}
            activeOpacity={0.75}
          >
            <Text style={[styles.statusPillText, activeStatusKey === 'online' && styles.pillTextActive]}>
              Online({statusCounts.online})
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.statusPillBtn, activeStatusKey === 'offline' && styles.pillBtnActive]}
            onPress={() => setActiveStatusKey('offline')}
            activeOpacity={0.75}
          >
            <Text style={[styles.statusPillText, activeStatusKey === 'offline' && styles.pillTextActive]}>
              Offline({statusCounts.offline})
            </Text>
          </TouchableOpacity>
        </View>

        {/* Section: Dynamic based on Active Category */}
        {activeCategory === 'DashCam' ? (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>Dual HD DashCams</Text>

            {dashcamDevices.length === 0 ? (
              <View style={styles.emptyContainer}>
                <DashCamIllustration size={56} />
                <Text style={styles.emptyTitle}>No DashCams Paired</Text>
                <Text style={styles.emptySubtitle}>
                  Pair an AI DashCam to your vehicle in your settings to view live feeds, snapshots, and emergency video recordings.
                </Text>
              </View>
            ) : (
              dashcamDevices.map((dashcam) => (
                <View key={dashcam.uuid} style={styles.dashcamCard}>
                  <View style={styles.dashcamLensContainer}>
                    <DashCamIllustration size={72} />
                  </View>

                  <View style={styles.dashcamInfo}>
                    <Text style={styles.dashcamTitle}>{dashcam.model}</Text>
                    <View style={styles.locationRow}>
                      <PinIcon color="#DE8635" size={13} />
                      <Text style={styles.locationText}>{dashcam.vehiclePlate} • {dashcam.vehicleName}</Text>
                    </View>

                    <View style={styles.dashcamBtnRow}>
                      <TouchableOpacity
                        style={styles.dashcamPhotoBtn}
                        activeOpacity={0.8}
                        onPress={() => Alert.alert('Snapshot', `Snapshot captured from ${dashcam.vehiclePlate}.`)}
                      >
                        <Text style={styles.dashcamPhotoBtnText}>Take Photo</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.dashcamLiveBtn}
                        activeOpacity={0.8}
                        onPress={onNavigateToCamera}
                      >
                        <Text style={styles.dashcamLiveBtnText}>Live View</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              ))
            )}
          </View>
        ) : (
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionTitle}>
              {activeCategory === 'GPS Tracker' ? 'Tracked Vehicles' : 'All Devices & Vehicles'}
            </Text>

            {/* Vehicle Cards List */}
            {filteredVehicles.length === 0 ? (
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyTitle}>No Vehicles Found</Text>
                <Text style={styles.emptySubtitle}>
                  {searchQuery ? 'No vehicles match your search query.' : 'No vehicles in this status category.'}
                </Text>
              </View>
            ) : (
              filteredVehicles.map((vehicle) => {
                const isMoving = vehicle.status === 'Moving';
                const isIdling = vehicle.status === 'Idling';
                const isStopped = vehicle.status === 'Stopped';
                const isOffline = vehicle.status === 'Offline';

                const statusBgColor = isMoving ? '#ECFDF5' : isIdling ? '#FFFBEB' : isStopped ? '#FFF1F2' : isOffline ? '#FEF2F2' : '#F1F5F9';
                const statusBorderColor = isMoving ? '#A7F3D0' : isIdling ? '#FDE68A' : isStopped ? '#FECDD3' : isOffline ? '#FECACA' : '#E2E8F0';
                const statusTextColor = isMoving ? '#047857' : isIdling ? '#B45309' : isStopped ? '#BE123C' : isOffline ? '#B91C1C' : '#475569';
                const statusLabel = isMoving ? 'MOVING' : isIdling ? 'IDLING (Engine ON)' : isStopped ? 'ENGINE CUT' : isOffline ? 'OFFLINE' : 'PARKED';

                return (
                  <TouchableOpacity
                    key={vehicle.id}
                    style={styles.vehicleCard}
                    activeOpacity={0.85}
                    onPress={() => {
                      if (onNavigateToVehicleDetail) {
                        onNavigateToVehicleDetail(vehicle);
                      }
                    }}
                  >
                    {/* Top row: Status tag & Speed badge */}
                    <View style={styles.cardHeaderRow}>
                      <View style={[styles.statusBadgePill, { backgroundColor: statusBgColor, borderColor: statusBorderColor }]}>
                        <View style={[styles.statusDotSmall, { backgroundColor: statusTextColor }]} />
                        <Text style={[styles.statusBadgePillText, { color: statusTextColor }]}>
                          {statusLabel}
                        </Text>
                      </View>

                      <View style={styles.speedBadge}>
                        <SpeedArrowIcon color={isMoving ? '#047857' : '#64748B'} size={13} />
                        <Text style={[styles.speedText, { color: isMoving ? '#047857' : '#64748B' }]}>{vehicle.speed}</Text>
                      </View>
                    </View>

                  {/* Main Title & Plate */}
                  <View style={styles.vehicleTitlePlateRow}>
                    <Text style={styles.vehicleCardTitle}>{vehicle.name}</Text>
                    {vehicle.plateNumber ? (
                      <View style={styles.plateBadge}>
                        <Text style={styles.plateBadgeText}>{vehicle.plateNumber}</Text>
                      </View>
                    ) : null}
                  </View>

                  {/* Location */}
                  <View style={styles.locationRow}>
                    <PinIcon color="#DE8635" size={13} />
                    <Text style={styles.locationText}>{vehicle.location}</Text>
                  </View>

                  {/* Meta 1: Recent Update */}
                  <View style={styles.metaRow}>
                    <ClockIcon color="#94A3B8" size={12} />
                    <Text style={styles.metaText}>
                      Recent Update: {vehicle.recentUpdate}
                    </Text>
                  </View>

                  {/* Meta 2: Battery & Network */}
                  <View style={styles.metaRow}>
                    <View style={styles.metaItem}>
                      <BatteryIcon color={parseInt(vehicle.battery, 10) > 70 ? '#10B981' : parseInt(vehicle.battery, 10) > 30 ? '#F59E0B' : '#EF4444'} size={13} />
                      <Text style={[styles.metaText, { color: '#475569', fontWeight: '600' }]}>
                        Battery: <Text style={{ color: parseInt(vehicle.battery, 10) > 70 ? '#10B981' : '#F59E0B', fontWeight: '700' }}>{vehicle.battery}</Text>
                      </Text>
                    </View>
                    <Text style={styles.metaDivider}>|</Text>
                    <View style={styles.metaItem}>
                      <NetworkIcon color={vehicle.network === 'Online' ? '#10B981' : '#EF4444'} size={13} />
                      <Text style={[styles.metaText, { color: '#475569', fontWeight: '600' }]}>
                        Network: <Text style={{ color: vehicle.network === 'Online' ? '#10B981' : '#EF4444', fontWeight: '700' }}>
                          ● {vehicle.network}
                        </Text>
                      </Text>
                    </View>
                  </View>
                </TouchableOpacity>
              );
            }))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 110,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  greetingContainer: {
    flex: 1,
  },
  greetingText: {
    fontFamily: 'Helvetica',
    fontSize: 22,
    fontWeight: '700',
    color: '#1E2538',
  },
  greetingName: {
    color: '#DE8635',
  },
  dateText: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    color: '#94A3B8',
    marginTop: 3,
  },
  headerRightActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  bellButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 4,
    elevation: 2,
    position: 'relative',
  },
  bellBadge: {
    position: 'absolute',
    top: 5,
    right: 5,
    backgroundColor: '#EF4444',
    width: 15,
    height: 15,
    borderRadius: 7.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellBadgeText: {
    color: '#FFFFFF',
    fontSize: 9,
    fontWeight: '700',
  },
  avatarButton: {
    width: 42,
    height: 42,
    borderRadius: 21,
    overflow: 'hidden',
    borderWidth: 2,
    borderColor: '#DE8635',
  },
  avatarPlaceholder: {
    width: '100%',
    height: '100%',
    backgroundColor: '#1E2538',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitials: {
    fontFamily: 'Helvetica',
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
  },
  searchBarContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    height: 48,
    paddingHorizontal: 14,
    marginBottom: 16,
  },
  searchInput: {
    flex: 1,
    marginLeft: 10,
    fontSize: 14,
    fontFamily: 'Helvetica',
    color: '#1E2538',
  },
  filterSlidersBtn: {
    padding: 6,
  },
  pillsRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 12,
  },
  pillBtn: {
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  pillBtnActive: {
    backgroundColor: '#1E2538',
    borderColor: '#1E2538',
  },
  pillText: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    fontWeight: '600',
    color: '#64748B',
  },
  pillTextActive: {
    color: '#FFFFFF',
  },
  statusPillBtn: {
    paddingHorizontal: 13,
    paddingVertical: 7,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  statusPillText: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    fontWeight: '600',
    color: '#64748B',
  },
  sectionContainer: {
    marginTop: 10,
  },
  sectionTitle: {
    fontFamily: 'Helvetica',
    fontSize: 16,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 14,
  },
  vehicleCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  cardHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  statusBadgePill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 3.5,
    borderRadius: 8,
    borderWidth: 1,
    gap: 5,
  },
  statusDotSmall: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  statusBadgePillText: {
    fontFamily: 'Helvetica',
    fontSize: 10.5,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  statusTag: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  speedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    gap: 4,
  },
  speedText: {
    fontFamily: 'Helvetica',
    fontSize: 11,
    fontWeight: '700',
    color: '#1E2538',
  },
  vehicleTitlePlateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  vehicleCardTitle: {
    fontFamily: 'Helvetica',
    fontSize: 17,
    fontWeight: '700',
    color: '#1E2538',
  },
  plateBadge: {
    backgroundColor: '#F3F4F6',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  plateBadgeText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#4B5563',
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 8,
  },
  locationText: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    color: '#DE8635',
    fontWeight: '500',
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 4,
  },
  metaText: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    color: '#94A3B8',
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  metaDivider: {
    color: '#CBD5E1',
    marginHorizontal: 4,
    fontSize: 12,
  },
  dashcamCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  dashcamLensContainer: {
    marginRight: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dashcamInfo: {
    flex: 1,
  },
  dashcamTitle: {
    fontFamily: 'Helvetica',
    fontSize: 15,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 4,
  },
  dashcamBtnRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 10,
  },
  dashcamPhotoBtn: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  dashcamPhotoBtnText: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    fontWeight: '600',
    color: '#1E2538',
  },
  dashcamLiveBtn: {
    backgroundColor: '#1E2538',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 8,
  },
    dashcamLiveBtnText: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  emptyContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginTop: 8,
  },
  emptyTitle: {
    fontFamily: 'Helvetica',
    fontSize: 16,
    fontWeight: '700',
    color: '#1E2538',
    marginTop: 12,
    marginBottom: 4,
  },
  emptySubtitle: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 18,
    maxWidth: 280,
  },
});
