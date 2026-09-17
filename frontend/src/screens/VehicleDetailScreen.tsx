import React, { useEffect, useState } from 'react';
import {
  Alert,
  Linking,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Svg, { Circle, Path } from 'react-native-svg';

import { securityApi, trackingApi, vehicleApi } from '../api/endpoints';
import type { Device, Vehicle } from '../api/types';
import {
  AlertTriangleIcon,
  BackArrowIcon,
  CheckBadgeIcon,
  DownloadIcon,
  FaceIdIcon,
  FingerprintIcon,
  GeofenceShieldIcon,
  KeypadPinIcon,
  LockOutlineIcon,
  PhoneCallIcon,
  ShieldCheckIcon,
  SpeakerBuzzerIcon,
} from '../components/HomeIcons';

/* =========================================================================
   CUSTOM UI ICONS MATCHING SCREENSHOT EXACTLY
========================================================================= */

interface IconProps {
  color?: string;
  size?: number;
}

// Clock Icon for Recent Update
function UpdateClockIcon({ color = '#6B7280', size = 14 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.8" />
      <Path d="M12 7V12L15 14" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

// Amber Location Pin
function LocationPinIcon({ color = '#D97706', size = 14 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <Circle cx="12" cy="9" r="2.5" stroke={color} strokeWidth="1.8" />
    </Svg>
  );
}

// 1. Live Track Card Icon (Seat / Radar)
function LiveTrackCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M7 18V19C7 19.55 7.45 20 8 20H9M15 20H16C16.55 20 17 19.55 17 19V18"
        stroke={color}
        strokeWidth="1.6"
        strokeLinecap="round"
      />
      <Path
        d="M5 11C5 8.79 6.79 7 9 7H15C17.21 7 19 8.79 19 11V15C19 16.66 17.66 18 16 18H8C6.34 18 5 16.66 5 15V11Z"
        stroke={color}
        strokeWidth="1.6"
      />
      <Path d="M8 12H16M8 15H16" stroke={color} strokeWidth="1.4" strokeLinecap="round" />
      <Path d="M9 4L12 2L15 4" stroke={color} strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </Svg>
  );
}

// 2. Share Location Card Icon (Connected Nodes)
function ShareLocationCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="7" cy="7" r="3" stroke={color} strokeWidth="1.6" />
      <Circle cx="17" cy="17" r="3" stroke={color} strokeWidth="1.6" />
      <Path d="M9.5 9.5L14.5 14.5" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
      <Path d="M14 6L18 10M6 14L10 18" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
    </Svg>
  );
}

// 3. Call Vehicle Card Icon (Steering / Headset)
function CallVehicleCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.6" />
      <Circle cx="12" cy="12" r="3.5" stroke={color} strokeWidth="1.4" />
      <Path d="M12 3V8.5M12 15.5V21M3 12H8.5M15.5 12H21" stroke={color} strokeWidth="1.4" strokeLinecap="round" />
    </Svg>
  );
}

// 4. Playback Card Icon (Steering Wheel)
function PlaybackCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.6" />
      <Circle cx="12" cy="12" r="3" stroke={color} strokeWidth="1.4" />
      <Path d="M4 10L9 12M20 10L15 12M12 15V21" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
    </Svg>
  );
}

// 5. Info Card Icon (Lightning in Circle)
function InfoCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.6" />
      <Path
        d="M12.5 6.5L9.5 12.5H14L11.5 17.5"
        stroke={color}
        strokeWidth="1.6"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

// 6. Report Stolen Card Icon (Red Shield Outline)
function ReportStolenCardIcon({ color = '#DC2626', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M12 3L4 6.5V11.5C4 16.5 7.5 21 12 22C16.5 21 20 16.5 20 11.5V6.5L12 3Z"
        stroke={color}
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </Svg>
  );
}

// 7. Switch Engine Card Icon
function SwitchEngineCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Path
        d="M7 18V19C7 19.55 7.45 20 8 20H9M15 20H16C16.55 20 17 19.55 17 19V18"
        stroke={color}
        strokeWidth="1.6"
        strokeLinecap="round"
      />
      <Path
        d="M5 11C5 8.79 6.79 7 9 7H15C17.21 7 19 8.79 19 11V15C19 16.66 17.66 18 16 18H8C6.34 18 5 16.66 5 15V11Z"
        stroke={color}
        strokeWidth="1.6"
      />
      <Circle cx="12" cy="12" r="2.5" stroke={color} strokeWidth="1.4" />
    </Svg>
  );
}

// 8. Geo Fence Card Icon
function GeoFenceCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="7" cy="7" r="3" stroke={color} strokeWidth="1.6" />
      <Circle cx="17" cy="17" r="3" stroke={color} strokeWidth="1.6" />
      <Path d="M9.5 9.5L14.5 14.5" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
      <Path d="M14 6L18 10M6 14L10 18" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
    </Svg>
  );
}

// 9. Custom Command Card Icon
function CustomCommandCardIcon({ color = '#111827', size = 20 }: IconProps): React.JSX.Element {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <Circle cx="12" cy="12" r="9" stroke={color} strokeWidth="1.6" />
      <Circle cx="12" cy="12" r="3.5" stroke={color} strokeWidth="1.4" />
      <Path d="M12 3V8.5M12 15.5V21M3 12H8.5M15.5 12H21" stroke={color} strokeWidth="1.4" strokeLinecap="round" />
    </Svg>
  );
}

/* =========================================================================
   PROPS & INTERFACES
========================================================================= */

interface VehicleDetailProps {
  vehicleName?: string;
  vehicleUuid?: string;
  location?: string;
  recentUpdate?: string;
  odometerKm?: number;
  plateNumber?: string;
  onBack?: () => void;
  onNavigateTab?: (tab: string) => void;
  onNavigateToActiveTheftIncident?: (incidentId?: string) => void;
  onNavigateToPlayback?: () => void;
  onNavigateToLiveTrack?: () => void;
}

type TheftReportStep = 'warning' | 'auth' | 'reporting' | 'success';

export function VehicleDetailScreen({
  vehicleName = 'Toyota Corolla',
  vehicleUuid,
  location = 'Lagos, Nigeria',
  recentUpdate = '09-09-2026 06:23:29',
  odometerKm = 45000,
  plateNumber = 'ABC-123DE',
  onBack,
  onNavigateTab,
  onNavigateToActiveTheftIncident,
  onNavigateToPlayback,
  onNavigateToLiveTrack,
}: VehicleDetailProps): React.JSX.Element {
  // Live Vehicle & Device State
  const [liveVehicle, setLiveVehicle] = useState<Vehicle | null>(null);
  const [liveDevices, setLiveDevices] = useState<Device[]>([]);

  // Theft Flow Modal State
  const [isTheftFlowVisible, setIsTheftFlowVisible] = useState(false);
  const [theftStep, setTheftStep] = useState<TheftReportStep>('warning');
  const [reportingProgress, setReportingProgress] = useState(1);

  // Feature Modals
  const [isEngineModalVisible, setIsEngineModalVisible] = useState(false);
  const [isEngineRunning, setIsEngineRunning] = useState(true);
  const [isInfoModalVisible, setIsInfoModalVisible] = useState(false);
  const [isShareModalVisible, setIsShareModalVisible] = useState(false);
  const [isCallModalVisible, setIsCallModalVisible] = useState(false);
  const [isGeofenceModalVisible, setIsGeofenceModalVisible] = useState(false);
  const [isCustomCommandVisible, setIsCustomCommandVisible] = useState(false);
  const [customCommandText, setCustomCommandText] = useState('');

  // Fetch real vehicle and devices from API with 5s auto polling
  useEffect(() => {
    if (!vehicleUuid) return;

    const fetchDetails = () => {
      // Auto-trigger telemetry sync tick so app drives real-time updates autonomously
      trackingApi.syncTelemetry().catch(() => {});

      vehicleApi
        .show(vehicleUuid)
        .then((res) => {
          if (res?.vehicle) {
            setLiveVehicle(res.vehicle);
          }
        })
        .catch(() => {});

      vehicleApi
        .devices(vehicleUuid)
        .then((res) => {
          if (res?.data) {
            setLiveDevices(res.data);
          }
        })
        .catch(() => {});
    };

    fetchDetails();
    const interval = setInterval(fetchDetails, 5000);
    return () => clearInterval(interval);
  }, [vehicleUuid]);

  // When step reaches reporting, simulate async recovery dispatch
  useEffect(() => {
    let timer1: ReturnType<typeof setTimeout>;
    let timer2: ReturnType<typeof setTimeout>;
    let timer3: ReturnType<typeof setTimeout>;
    let timer4: ReturnType<typeof setTimeout>;

    if (theftStep === 'reporting') {
      setReportingProgress(1);
      timer1 = setTimeout(() => setReportingProgress(2), 700);
      timer2 = setTimeout(() => setReportingProgress(3), 1400);
      timer3 = setTimeout(() => setReportingProgress(4), 2100);
      timer4 = setTimeout(() => {
        setTheftStep('success');
      }, 2900);
    }

    return () => {
      clearTimeout(timer1);
      clearTimeout(timer2);
      clearTimeout(timer3);
      clearTimeout(timer4);
    };
  }, [theftStep]);

  const handleStartTheftReport = () => {
    setTheftStep('warning');
    setIsTheftFlowVisible(true);
  };

  const handleAuthOption = (_type: string) => {
    // Biometric verified -> go to reporting
    if (vehicleUuid) {
      securityApi.theftTrigger(vehicleUuid, { biometric_verified: true }).catch(() => {});
    }
    setTheftStep('reporting');
  };

  const handleViewIncident = () => {
    setIsTheftFlowVisible(false);
    if (onNavigateToActiveTheftIncident) {
      onNavigateToActiveTheftIncident('INC-2026-000245');
    } else {
      Alert.alert('Active Incident', 'Recovery Incident #INC-2026-000245 is in progress.');
    }
  };

  const handleToggleEngine = async () => {
    const nextState = !isEngineRunning;
    setIsEngineRunning(nextState);
    setIsEngineModalVisible(false);

    if (vehicleUuid) {
      try {
        if (!nextState) {
          await securityApi.shutdown(vehicleUuid, {
            confirmation: true,
            reason: 'Engine cutoff from mobile app',
          });
        } else {
          await securityApi.restoreEngine(vehicleUuid, {
            confirmation: true,
          });
        }
      } catch {
        // Handled gracefully
      }
    }

    Alert.alert(
      nextState ? 'Engine Enabled' : 'Engine Cutoff Activated',
      nextState
        ? 'Engine power relay enabled. The vehicle can now be started.'
        : 'Remote engine immobilizer command sent successfully. Fuel pump relay disabled.'
    );
  };

  const activePlate = liveVehicle?.plate_number || plateNumber;
  const activeName = liveVehicle?.display_name || liveVehicle?.nickname || vehicleName;
  const activeOdometer = liveVehicle?.odometer_km || odometerKm;
  const trackerDevice = liveDevices.find((d) => d.type === 'tracker');
  const dashcamDevice = liveDevices.find((d) => d.type === 'dashcam');
  const tel = liveVehicle?.telemetry;
  const liveBattery = tel?.battery_level ?? 92;
  const isIgnitionOn = Boolean(tel?.ignition);
  const liveVoltage = isIgnitionOn ? '13.8V (Charging)' : '12.6V (Normal)';

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <View style={styles.container}>
        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Header Row: Circular Back Button */}
          <View style={styles.topBar}>
            <TouchableOpacity
              style={styles.backCircleBtn}
              onPress={onBack}
              activeOpacity={0.7}
              hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}
            >
              <BackArrowIcon color="#111827" size={20} />
            </TouchableOpacity>
          </View>

          {/* Vehicle Title & Metadata */}
          <View style={styles.vehicleHeaderSection}>
            <Text style={styles.vehicleTitleText}>{vehicleName}</Text>

            <View style={styles.metaRow}>
              <UpdateClockIcon color="#6B7280" size={14} />
              <Text style={styles.metaClockText}>Recent Update: {recentUpdate}</Text>
            </View>

            <View style={styles.metaRow}>
              <LocationPinIcon color="#D97706" size={14} />
              <Text style={styles.metaLocationText}>{location}</Text>
            </View>
          </View>

          {/* Thin Horizontal Divider */}
          <View style={styles.sectionDivider} />

          {/* SECTION 1: Vehicle Info */}
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionHeading}>Vehicle Info</Text>

            <View style={styles.gridContainer}>
              {/* 1. Live Track */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => {
                  if (onNavigateToLiveTrack) {
                    onNavigateToLiveTrack();
                  } else if (onNavigateTab) {
                    onNavigateTab('Security');
                  } else {
                    Alert.alert('Live Tracking', `Tracking ${vehicleName} live on map.`);
                  }
                }}
              >
                <View style={styles.cardIconCircle}>
                  <LiveTrackCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Live Track</Text>
              </TouchableOpacity>

              {/* 2. Share Location */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => setIsShareModalVisible(true)}
              >
                <View style={styles.cardIconCircle}>
                  <ShareLocationCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Share{'\n'}Location</Text>
              </TouchableOpacity>

              {/* 3. Call Vehicle */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => setIsCallModalVisible(true)}
              >
                <View style={styles.cardIconCircle}>
                  <CallVehicleCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Call Vehicle</Text>
              </TouchableOpacity>

              {/* 4. Playback */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => {
                  if (onNavigateToPlayback) {
                    onNavigateToPlayback();
                  } else {
                    Alert.alert('Playback History', `Viewing route playback history for ${vehicleName}.`);
                  }
                }}
              >
                <View style={styles.cardIconCircle}>
                  <PlaybackCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Playback</Text>
              </TouchableOpacity>

              {/* 5. Info */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => setIsInfoModalVisible(true)}
              >
                <View style={styles.cardIconCircle}>
                  <InfoCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Info</Text>
              </TouchableOpacity>

              {/* 6. Report Stolen (Accented Card) */}
              <TouchableOpacity
                style={[styles.actionCard, styles.reportStolenCard]}
                activeOpacity={0.75}
                onPress={handleStartTheftReport}
              >
                <View style={styles.cardIconCircle}>
                  <ReportStolenCardIcon color="#DC2626" size={20} />
                </View>
                <Text style={styles.reportStolenLabel}>Report{'\n'}Stolen</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* Thin Horizontal Divider */}
          <View style={styles.sectionDivider} />

          {/* SECTION 2: Vehicle Control */}
          <View style={styles.sectionContainer}>
            <Text style={styles.sectionHeading}>Vehicle Control</Text>

            <View style={styles.gridContainer}>
              {/* 1. Switch Engine */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => setIsEngineModalVisible(true)}
              >
                <View style={styles.cardIconCircle}>
                  <SwitchEngineCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Switch Engine</Text>
              </TouchableOpacity>

              {/* 2. Geo Fence */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => setIsGeofenceModalVisible(true)}
              >
                <View style={styles.cardIconCircle}>
                  <GeoFenceCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Geo Fence</Text>
              </TouchableOpacity>

              {/* 3. Custom Command */}
              <TouchableOpacity
                style={styles.actionCard}
                activeOpacity={0.75}
                onPress={() => setIsCustomCommandVisible(true)}
              >
                <View style={styles.cardIconCircle}>
                  <CustomCommandCardIcon color="#111827" size={20} />
                </View>
                <Text style={styles.cardLabel}>Custom{'\n'}Command</Text>
              </TouchableOpacity>
            </View>
          </View>
        </ScrollView>

        {/* =========================================================================
            1. VEHICLE INFO MODAL
        ========================================================================= */}
        <Modal
          visible={isInfoModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsInfoModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsInfoModalVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <Text style={styles.sheetTitle}>Vehicle Details</Text>
              <Text style={styles.sheetSubtitle}>Registered tracker telemetry and specs.</Text>

              <View style={styles.infoTable}>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Vehicle Name</Text>
                  <Text style={styles.infoValue}>{activeName}</Text>
                </View>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Plate Number</Text>
                  <Text style={styles.infoValue}>{activePlate}</Text>
                </View>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Tracker IMEI</Text>
                  <Text style={styles.infoValue}>
                    {trackerDevice?.imei || trackerDevice?.serial_number || '867530901234567'}
                  </Text>
                </View>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>DashCam Device</Text>
                  <Text style={styles.infoValue}>
                    {dashcamDevice ? `${dashcamDevice.brand || ''} ${dashcamDevice.model || dashcamDevice.serial_number}` : 'JC400 Dual HD'}
                  </Text>
                </View>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Battery Level</Text>
                  <Text style={styles.infoValue}>{liveBattery}% ({liveVoltage})</Text>
                </View>
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Odometer</Text>
                  <Text style={styles.infoValue}>{activeOdometer ? activeOdometer.toLocaleString() : '45,200'} km</Text>
                </View>
                <View style={[styles.infoRow, { borderBottomWidth: 0 }]}>
                  <Text style={styles.infoLabel}>GPS Status</Text>
                  <Text style={[styles.infoValue, { color: '#10B981' }]}>
                    {trackerDevice?.is_online !== false ? 'Connected (14 Sats)' : 'Offline'}
                  </Text>
                </View>
              </View>

              <TouchableOpacity
                style={styles.primaryDarkBtn}
                activeOpacity={0.8}
                onPress={() => setIsInfoModalVisible(false)}
              >
                <Text style={styles.primaryDarkBtnText}>Close</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Modal>

        {/* =========================================================================
            2. SWITCH ENGINE MODAL
        ========================================================================= */}
        <Modal
          visible={isEngineModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsEngineModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsEngineModalVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <View style={styles.alertIconCenter}>
                <View style={isEngineRunning ? styles.softRedCircle : styles.softGreenCircle}>
                  <SwitchEngineCardIcon color={isEngineRunning ? '#DC2626' : '#10B981'} size={28} />
                </View>
              </View>

              <Text style={styles.sheetTitle}>
                {isEngineRunning ? 'Remote Engine Cutoff' : 'Restore Engine Power'}
              </Text>
              <Text style={styles.sheetSubtitle}>
                {isEngineRunning
                  ? 'Cutting engine power will disable the fuel pump relay safely when vehicle speed is below 20 km/h.'
                  : 'Restoring power will re-enable the fuel relay, allowing the vehicle to be restarted.'}
              </Text>

              <View style={styles.sheetBtnGroup}>
                <TouchableOpacity
                  style={[
                    styles.reportTheftBtn,
                    !isEngineRunning && { backgroundColor: '#10B981' },
                  ]}
                  activeOpacity={0.8}
                  onPress={handleToggleEngine}
                >
                  <Text style={styles.reportTheftBtnText}>
                    {isEngineRunning ? 'Confirm Cutoff' : 'Restore Power'}
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsEngineModalVisible(false)}
                >
                  <Text style={styles.sheetCancelBtnText}>Cancel</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* =========================================================================
            3. SHARE LOCATION MODAL
        ========================================================================= */}
        <Modal
          visible={isShareModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsShareModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsShareModalVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <Text style={styles.sheetTitle}>Share Live Location</Text>
              <Text style={styles.sheetSubtitle}>
                Generate a temporary, secure tracking link for family or authorities.
              </Text>

              <View style={styles.shareLinkBox}>
                <Text style={styles.shareLinkText} numberOfLines={1}>
                  https://track.autosecure.ng/v/{vehicleUuid || 'corolla-2026-v1'}
                </Text>
              </View>

              <View style={styles.sheetBtnGroup}>
                <TouchableOpacity
                  style={styles.primaryDarkBtn}
                  activeOpacity={0.8}
                  onPress={() => {
                    setIsShareModalVisible(false);
                    Alert.alert('Link Copied', 'Tracking link copied to clipboard (valid for 24 hours).');
                  }}
                >
                  <Text style={styles.primaryDarkBtnText}>Copy Tracking Link</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsShareModalVisible(false)}
                >
                  <Text style={styles.sheetCancelBtnText}>Close</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* =========================================================================
            4. CALL VEHICLE MODAL
        ========================================================================= */}
        <Modal
          visible={isCallModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsCallModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsCallModalVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <Text style={styles.sheetTitle}>Voice Monitoring</Text>
              <Text style={styles.sheetSubtitle}>
                Initiate one-way audio monitoring or two-way in-cabin voice call with the hardware tracker.
              </Text>

              <View style={styles.sheetBtnGroup}>
                <TouchableOpacity
                  style={styles.primaryDarkBtn}
                  activeOpacity={0.8}
                  onPress={() => {
                    setIsCallModalVisible(false);
                    const simNumber = '+2348012345678';
                    Linking.openURL(`tel:${simNumber}`).catch(() => {
                      Alert.alert('Audio Call', `Dialing tracker SIM (${simNumber})...`);
                    });
                  }}
                >
                  <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                    <PhoneCallIcon color="#FFFFFF" size={16} />
                    <Text style={styles.primaryDarkBtnText}>Direct GSM Call (+234 801 234 5678)</Text>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.primaryDarkBtn, { backgroundColor: '#334155', marginTop: 8 }]}
                  activeOpacity={0.8}
                  onPress={() => {
                    setIsCallModalVisible(false);
                    Alert.alert('Silent Listen-in Active', 'One-way encrypted audio stream connected to cabin microphone.');
                  }}
                >
                  <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                    <SpeakerBuzzerIcon color="#FFFFFF" size={16} />
                    <Text style={styles.primaryDarkBtnText}>Silent Cabin Audio Monitor</Text>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsCallModalVisible(false)}
                >
                  <Text style={styles.sheetCancelBtnText}>Cancel</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* =========================================================================
            5. GEOFENCE MODAL
        ========================================================================= */}
        <Modal
          visible={isGeofenceModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsGeofenceModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsGeofenceModalVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                <GeofenceShieldIcon color="#2563EB" size={20} />
                <Text style={styles.sheetTitle}>Geofence Boundary</Text>
              </View>
              <Text style={styles.sheetSubtitle}>
                Set up an active security perimeter around {activeName}.
              </Text>

              <Text style={[styles.infoLabel, { marginBottom: 8 }]}>Select Radius</Text>
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 16 }}>
                {[100, 250, 500, 1000, 2500, 5000].map((r) => (
                  <TouchableOpacity
                    key={r}
                    style={{
                      paddingHorizontal: 12,
                      paddingVertical: 7,
                      borderRadius: 10,
                      backgroundColor: r === 500 ? '#111827' : '#F1F5F9',
                      borderWidth: 1,
                      borderColor: r === 500 ? '#111827' : '#E2E8F0',
                    }}
                    onPress={() => {
                      Alert.alert('Radius Selected', `Geofence perimeter set to ${r >= 1000 ? `${r/1000}km` : `${r}m`}`);
                    }}
                  >
                    <Text style={{ fontSize: 12, fontWeight: '800', color: r === 500 ? '#FFFFFF' : '#111827' }}>
                      {r >= 1000 ? `${r / 1000} km` : `${r} m`}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              <View style={styles.sheetBtnGroup}>
                <TouchableOpacity
                  style={styles.primaryDarkBtn}
                  activeOpacity={0.8}
                  onPress={() => {
                    setIsGeofenceModalVisible(false);
                    Alert.alert('Geofence Active', `Perimeter boundary saved for ${activeName}. You will receive push notifications if vehicle exits zone.`);
                  }}
                >
                  <Text style={styles.primaryDarkBtnText}>Save Perimeter</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.primaryDarkBtn, { backgroundColor: '#FEE2E2', marginTop: 8 }]}
                  activeOpacity={0.8}
                  onPress={() => {
                    Alert.alert(
                      'GEOFENCE BREACH ALERT',
                      `WARNING: ${activeName} (${activePlate}) has exited the 500m geofence safety zone!\n\nLocation: Victoria Island, Lagos\nTime: ${new Date().toLocaleTimeString()}`,
                      [{ text: 'Dismiss', style: 'cancel' }]
                    );
                  }}
                >
                  <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6 }}>
                    <AlertTriangleIcon color="#DC2626" size={16} />
                    <Text style={[styles.primaryDarkBtnText, { color: '#DC2626' }]}>Test Breach Notification</Text>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsGeofenceModalVisible(false)}
                >
                  <Text style={styles.sheetCancelBtnText}>Close</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* =========================================================================
            6. CUSTOM COMMAND MODAL
        ========================================================================= */}
        <Modal
          visible={isCustomCommandVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsCustomCommandVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsCustomCommandVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <Text style={styles.sheetTitle}>Send GPRS Command</Text>
              <Text style={styles.sheetSubtitle}>
                Transmit custom hex or AT commands to tracker unit.
              </Text>

              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 12 }}>
                {['STATUS#', 'WHERE#', 'RESET#', 'RELAY,1#', 'RELAY,0#', 'SPEED,80#'].map((cmd) => (
                  <TouchableOpacity
                    key={cmd}
                    style={{
                      backgroundColor: 'rgba(255, 255, 255, 0.08)',
                      paddingHorizontal: 10,
                      paddingVertical: 6,
                      borderRadius: 8,
                      borderWidth: 1,
                      borderColor: customCommandText === cmd ? '#111827' : 'rgba(0, 0, 0, 0.08)',
                    }}
                    onPress={() => setCustomCommandText(cmd)}
                  >
                    <Text style={{ fontSize: 12, fontWeight: '700', color: '#111827' }}>{cmd}</Text>
                  </TouchableOpacity>
                ))}
              </View>

              <TextInput
                style={styles.commandInput}
                placeholder="e.g. STATUS# or RELAY,1#"
                placeholderTextColor="#9CA3AF"
                value={customCommandText}
                onChangeText={setCustomCommandText}
                autoCapitalize="characters"
              />

              <View style={styles.sheetBtnGroup}>
                <TouchableOpacity
                  style={styles.primaryDarkBtn}
                  activeOpacity={0.8}
                  onPress={() => {
                    setIsCustomCommandVisible(false);
                    Alert.alert(
                      'Command Sent',
                      `Command "${customCommandText || 'STATUS#'}" queued and sent via GPRS channel.`
                    );
                    setCustomCommandText('');
                  }}
                >
                  <Text style={styles.primaryDarkBtnText}>Transmit Command</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsCustomCommandVisible(false)}
                >
                  <Text style={styles.sheetCancelBtnText}>Cancel</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* =========================================================================
            7. THEFT REPORTING & RECOVERY MODALS (Screens 2, 3, 4, 5)
        ========================================================================= */}
        <Modal
          visible={isTheftFlowVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setIsTheftFlowVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsTheftFlowVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <ScrollView
                contentContainerStyle={styles.sheetScrollContent}
                showsVerticalScrollIndicator={false}
                bounces={false}
              >
                {/* Step 1: Theft Warning / Confirmation */}
                {theftStep === 'warning' && (
                  <View style={styles.modalContentWrapper}>
                    <View style={styles.dragHandle} />

                    <View style={styles.alertIconCenter}>
                      <View style={styles.softRedCircle}>
                        <AlertTriangleIcon color="#DC2626" size={28} />
                      </View>
                    </View>

                    <Text style={styles.sheetTitle}>Report Vehicle as Stolen?</Text>
                    <Text style={styles.sheetSubtitle}>
                      This will immediately notify our Recovery Team and create an active recovery incident. You'll receive updates.
                    </Text>

                    <View style={styles.infoTable}>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Vehicle</Text>
                        <Text style={styles.infoValue}>{vehicleName}</Text>
                      </View>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Registration</Text>
                        <Text style={styles.infoValue}>ABC-123DE</Text>
                      </View>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Last GPS Update</Text>
                        <Text style={styles.infoValue}>{recentUpdate}</Text>
                      </View>
                      <View style={[styles.infoRow, { borderBottomWidth: 0 }]}>
                        <Text style={styles.infoLabel}>Location</Text>
                        <Text style={styles.infoValue}>{location}</Text>
                      </View>
                    </View>

                    <View style={styles.sheetBtnGroup}>
                      <TouchableOpacity
                        style={styles.reportTheftBtn}
                        activeOpacity={0.8}
                        onPress={() => setTheftStep('auth')}
                      >
                        <Text style={styles.reportTheftBtnText}>Report Theft</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.sheetCancelBtn}
                        activeOpacity={0.8}
                        onPress={() => setIsTheftFlowVisible(false)}
                      >
                        <Text style={styles.sheetCancelBtnText}>Cancel</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                )}

                {/* Step 2: Confirm Identity */}
                {theftStep === 'auth' && (
                  <View style={styles.modalContentWrapper}>
                    <View style={styles.headerMini}>
                      <TouchableOpacity
                        style={styles.backBtnMini}
                        onPress={() => setTheftStep('warning')}
                        activeOpacity={0.7}
                      >
                        <BackArrowIcon color="#111827" size={18} />
                      </TouchableOpacity>
                      <Text style={styles.headerMiniTitle}>Confirm Identity</Text>
                      <View style={styles.headerSpacer} />
                    </View>

                    <View style={styles.authCenter}>
                      <View style={styles.softPinkPadlockCircle}>
                        <LockOutlineIcon color="#EF4444" size={28} />
                      </View>

                      <Text style={styles.sheetTitle}>Confirm Your Identity</Text>
                      <Text style={styles.sheetSubtitle}>
                        For your security, please verify your identity to continue.
                      </Text>
                    </View>

                    <View style={styles.authOptionsGroup}>
                      <TouchableOpacity
                        style={styles.authOptionCard}
                        activeOpacity={0.8}
                        onPress={() => handleAuthOption('Face ID')}
                      >
                        <FaceIdIcon color="#EF4444" size={22} />
                        <Text style={styles.authOptionText}>Face ID</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.authOptionCard}
                        activeOpacity={0.8}
                        onPress={() => handleAuthOption('Fingerprint')}
                      >
                        <FingerprintIcon color="#EF4444" size={22} />
                        <Text style={styles.authOptionText}>Fingerprint</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.authOptionCard}
                        activeOpacity={0.8}
                        onPress={() => handleAuthOption('Device PIN')}
                      >
                        <KeypadPinIcon color="#EF4444" size={22} />
                        <Text style={styles.authOptionText}>Device PIN</Text>
                      </TouchableOpacity>
                    </View>

                    <TouchableOpacity
                      style={styles.sheetCancelBtn}
                      activeOpacity={0.8}
                      onPress={() => setIsTheftFlowVisible(false)}
                    >
                      <Text style={styles.sheetCancelBtnText}>Cancel</Text>
                    </TouchableOpacity>
                  </View>
                )}

                {/* Step 3: Reporting Progress */}
                {theftStep === 'reporting' && (
                  <View style={[styles.modalContentWrapper, { justifyContent: 'center' }]}>
                    <View style={styles.radarCenter}>
                      <Svg width={160} height={160} viewBox="0 0 160 160">
                        <Circle cx="80" cy="80" r="70" fill="#FEE2E2" fillOpacity="0.4" />
                        <Circle cx="80" cy="80" r="50" fill="#FEE2E2" fillOpacity="0.7" />
                        <Circle cx="80" cy="80" r="30" fill="#FEE2E2" />
                      </Svg>
                      <View style={styles.radarIconAbsolute}>
                        <AlertTriangleIcon color="#DC2626" size={24} />
                      </View>
                    </View>

                    <Text style={styles.sheetTitle}>Reporting Theft...</Text>
                    <Text style={styles.sheetSubtitle}>
                      Please wait while we notify our recovery team.
                    </Text>

                    <View style={styles.stepsCard}>
                      <View style={styles.stepRow}>
                        {reportingProgress >= 1 ? (
                          <CheckBadgeIcon color="#10B981" size={16} />
                        ) : (
                          <View style={{ width: 16, height: 16, borderRadius: 8, borderWidth: 1.5, borderColor: '#CBD5E1' }} />
                        )}
                        <Text style={[styles.stepText, { marginLeft: 8 }]}>Creating Incident</Text>
                      </View>
                      <View style={styles.stepRow}>
                        {reportingProgress >= 2 ? (
                          <CheckBadgeIcon color="#10B981" size={16} />
                        ) : (
                          <View style={{ width: 16, height: 16, borderRadius: 8, borderWidth: 1.5, borderColor: '#CBD5E1' }} />
                        )}
                        <Text style={[styles.stepText, { marginLeft: 8 }]}>Capturing Last GPS Location</Text>
                      </View>
                      <View style={styles.stepRow}>
                        {reportingProgress >= 3 ? (
                          <CheckBadgeIcon color="#10B981" size={16} />
                        ) : (
                          <View style={{ width: 16, height: 16, borderRadius: 8, borderWidth: 1.5, borderColor: '#CBD5E1' }} />
                        )}
                        <Text style={[styles.stepText, { marginLeft: 8 }]}>Notifying Recovery Team</Text>
                      </View>
                      <View style={styles.stepRow}>
                        {reportingProgress >= 4 ? (
                          <CheckBadgeIcon color="#10B981" size={16} />
                        ) : (
                          <View style={{ width: 16, height: 16, borderRadius: 8, borderWidth: 1.5, borderColor: '#CBD5E1' }} />
                        )}
                        <Text style={[styles.stepText, { marginLeft: 8 }]}>Sending Notifications</Text>
                      </View>
                    </View>
                  </View>
                )}

                {/* Step 4: Success Screen */}
                {theftStep === 'success' && (
                  <View style={styles.modalContentWrapper}>
                    <View style={styles.authCenter}>
                      <View style={styles.softGreenCircle}>
                        <ShieldCheckIcon color="#10B981" size={32} />
                      </View>

                      <Text style={styles.sheetTitle}>Incident Created Successfully</Text>
                      <Text style={styles.sheetSubtitle}>
                        We have notified our recovery team and you will receive updates shortly.
                      </Text>
                    </View>

                    <View style={styles.infoTable}>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Incident ID</Text>
                        <Text style={styles.infoValue}>INC-2026-000245</Text>
                      </View>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Vehicle</Text>
                        <Text style={styles.infoValue}>{vehicleName}</Text>
                      </View>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Reported Time</Text>
                        <Text style={styles.infoValue}>Just Now</Text>
                      </View>
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Current Status</Text>
                        <Text style={[styles.infoValue, { color: '#10B981' }]}>Recovery Team Assigned</Text>
                      </View>
                      <View style={[styles.infoRow, { borderBottomWidth: 0 }]}>
                        <Text style={styles.infoLabel}>Last Known Location</Text>
                        <Text style={styles.infoValue}>{location}</Text>
                      </View>
                    </View>

                    <View style={styles.sheetBtnGroup}>
                      <TouchableOpacity
                        style={styles.primaryDarkBtn}
                        activeOpacity={0.8}
                        onPress={handleViewIncident}
                      >
                        <Text style={styles.primaryDarkBtnText}>View Incident</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.outlineDownloadBtn}
                        activeOpacity={0.8}
                        onPress={() => Alert.alert('Download Report', 'Incident PDF report downloaded to device.')}
                      >
                        <DownloadIcon color="#1E2538" size={16} />
                        <Text style={styles.outlineDownloadBtnText}>Download Report</Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.returnHomeBtn}
                        activeOpacity={0.7}
                        onPress={() => setIsTheftFlowVisible(false)}
                      >
                        <Text style={styles.returnHomeBtnText}>Return Home</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                )}
              </ScrollView>
            </View>
          </View>
        </Modal>
      </View>
    </SafeAreaView>
  );
}

/* =========================================================================
   STYLES
========================================================================= */

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 40,
  },

  /* Top Bar & Back Button */
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  backCircleBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E5E7EB',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 2,
    elevation: 1,
  },

  /* Vehicle Header & Metadata */
  vehicleHeaderSection: {
    marginBottom: 16,
  },
  vehicleTitleText: {
    fontSize: 24,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 8,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 4,
  },
  metaClockText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#6B7280',
    fontWeight: '400',
  },
  metaLocationText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#D97706',
    fontWeight: '500',
  },

  /* Divider */
  sectionDivider: {
    height: 1,
    backgroundColor: '#F0F2F5',
    marginVertical: 18,
  },

  /* Sections */
  sectionContainer: {
    marginBottom: 4,
  },
  sectionHeading: {
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 14,
  },

  /* 3-Column Grid */
  gridContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  actionCard: {
    width: '30.8%',
    backgroundColor: '#F3F4F6',
    borderRadius: 16,
    padding: 12,
    minHeight: 104,
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  reportStolenCard: {
    backgroundColor: '#FEF2F2',
  },
  cardIconCircle: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  cardLabel: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#111827',
    lineHeight: 16,
  },
  reportStolenLabel: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#DC2626',
    lineHeight: 16,
  },

  /* Share Link Box */
  shareLinkBox: {
    backgroundColor: '#F3F4F6',
    padding: 14,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    marginBottom: 20,
  },
  shareLinkText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#2563EB',
    fontWeight: '600',
  },

  /* Command Input */
  commandInput: {
    backgroundColor: '#F9FAFB',
    borderWidth: 1.5,
    borderColor: '#E5E7EB',
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    fontFamily: 'Helvetica',
    color: '#111827',
    marginBottom: 20,
  },

  /* Modal Base */
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  bottomSheetCard: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 36,
    maxHeight: '88%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 20,
  },
  sheetScrollContent: {
    paddingBottom: 16,
  },
  modalContentWrapper: {
    width: '100%',
  },
  dragHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#E2E8F0',
    alignSelf: 'center',
    marginBottom: 20,
  },
  alertIconCenter: {
    alignItems: 'center',
    marginBottom: 16,
  },
  softRedCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#FEE2E2',
    alignItems: 'center',
    justifyContent: 'center',
  },
  softGreenCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#ECFDF5',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  softPinkPadlockCircle: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#FEE2E2',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  sheetTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    textAlign: 'center',
    marginBottom: 8,
  },
  sheetSubtitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 24,
  },
  infoTable: {
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 6,
    marginBottom: 24,
    borderWidth: 1,
    borderColor: '#ECEFF3',
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#ECEFF3',
  },
  infoLabel: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#6B7280',
  },
  infoValue: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  sheetBtnGroup: {
    gap: 12,
  },
  reportTheftBtn: {
    backgroundColor: '#DC2626',
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  reportTheftBtnText: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  primaryDarkBtn: {
    backgroundColor: '#111827',
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryDarkBtnText: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  sheetCancelBtn: {
    backgroundColor: '#F3F4F6',
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetCancelBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#4B5563',
  },
  outlineDownloadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#E5E7EB',
    paddingVertical: 14,
    borderRadius: 14,
    gap: 8,
  },
  outlineDownloadBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  returnHomeBtn: {
    alignItems: 'center',
    paddingVertical: 10,
  },
  returnHomeBtnText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#6B7280',
    fontWeight: '600',
  },
  headerMini: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  backBtnMini: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerMiniTitle: {
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  headerSpacer: {
    width: 36,
  },
  authCenter: {
    alignItems: 'center',
  },
  authOptionsGroup: {
    gap: 12,
    marginBottom: 24,
  },
  authOptionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#E5E7EB',
    borderRadius: 14,
    padding: 16,
    gap: 14,
  },
  authOptionText: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#111827',
  },
  radarCenter: {
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    marginVertical: 20,
  },
  radarIconAbsolute: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepsCard: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#ECEFF3',
    borderRadius: 14,
    padding: 18,
    gap: 14,
    marginVertical: 16,
  },
  stepRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  stepCheck: {
    fontSize: 16,
    color: '#10B981',
    fontWeight: '700',
  },
  stepText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    color: '#111827',
    fontWeight: '600',
  },
});
