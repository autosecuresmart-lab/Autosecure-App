import React from 'react';
import {
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  BackArrowIcon,
  BatteryIcon,
  CheckVerifiedIcon,
  ClockIcon,
  DeviceOfflineIcon,
  NetworkIcon,
  PinIcon,
  ShareIcon,
} from '../components/HomeIcons';

interface ViewNotificationScreenProps {
  id?: string;
  title?: string;
  subtitle?: string;
  time?: string;
  type?: 'online' | 'offline' | 'alert' | 'event';
  read?: boolean;
  vehicleName?: string;
  location?: string;
  onBack?: () => void;
  onNavigateToMap?: () => void;
  onNavigateToVehicle?: () => void;
}

export function ViewNotificationScreen({
  title = 'Active Demo is online',
  subtitle = 'The live demo is now active and available for use',
  time = 'Today at 10:00 am',
  type = 'online',
  vehicleName = 'Active Demo (Toyota Corolla)',
  location = 'Victoria Island, Lagos, Nigeria',
  onBack,
  onNavigateToMap,
  onNavigateToVehicle,
}: ViewNotificationScreenProps): React.JSX.Element {
  const isOnline = type === 'online';

  const handleShare = () => {
    Alert.alert('Share Notification', `Sharing: ${title}\n${subtitle}`);
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right', 'bottom']}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={onBack}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <BackArrowIcon color="#1E2538" size={18} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Notification</Text>

          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={handleShare}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <ShareIcon color="#1E2538" size={18} />
          </TouchableOpacity>
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Main Notification Headline Card */}
          <View style={styles.card}>
            <View style={styles.cardHeaderRow}>
              <View style={[styles.iconCircle, isOnline ? styles.iconCircleOnline : styles.iconCircleOffline]}>
                {type === 'offline' ? (
                  <DeviceOfflineIcon color="#64748B" size={24} />
                ) : (
                  <CheckVerifiedIcon color="#10B981" size={24} />
                )}
              </View>

              <View style={[styles.statusPill, isOnline ? styles.pillOnline : styles.pillOffline]}>
                <View style={[styles.statusDot, isOnline ? styles.dotOnline : styles.dotOffline]} />
                <Text style={[styles.statusPillText, isOnline ? styles.textOnline : styles.textOffline]}>
                  {isOnline ? 'System Online' : 'Device Offline'}
                </Text>
              </View>
            </View>

            <Text style={styles.headlineTitle}>{title}</Text>
            <Text style={styles.headlineSubtitle}>{subtitle}</Text>

            <View style={styles.metaRow}>
              <ClockIcon color="#94A3B8" size={13} />
              <Text style={styles.metaText}>{time}</Text>
            </View>

            <View style={styles.locationRow}>
              <PinIcon color="#DE8635" size={14} />
              <Text style={styles.locationText}>{location}</Text>
            </View>
          </View>

          {/* Diagnostic & Telemetry Card */}
          <View style={styles.card}>
            <Text style={styles.sectionHeading}>DEVICE TELEMETRY & STATUS</Text>

            <View style={styles.telemetryGrid}>
              <View style={styles.telemetryItem}>
                <Text style={styles.telemetryLabel}>Vehicle</Text>
                <Text style={styles.telemetryValue} numberOfLines={1}>{vehicleName}</Text>
              </View>

              <View style={styles.telemetryItem}>
                <Text style={styles.telemetryLabel}>Signal / Network</Text>
                <View style={styles.telemetryInline}>
                  <NetworkIcon color={isOnline ? '#10B981' : '#64748B'} size={14} />
                  <Text style={styles.telemetryValue}>{isOnline ? '4G LTE • 98%' : 'No Carrier'}</Text>
                </View>
              </View>

              <View style={styles.telemetryItem}>
                <Text style={styles.telemetryLabel}>Battery / Power</Text>
                <View style={styles.telemetryInline}>
                  <BatteryIcon color="#10B981" size={14} />
                  <Text style={styles.telemetryValue}>13.4V (Active)</Text>
                </View>
              </View>

              <View style={styles.telemetryItem}>
                <Text style={styles.telemetryLabel}>Tracker Protocol</Text>
                <Text style={styles.telemetryValue}>OBD-II Telematics</Text>
              </View>
            </View>
          </View>

          {/* Detailed Summary Narrative */}
          <View style={styles.card}>
            <Text style={styles.sectionHeading}>EVENT DESCRIPTION</Text>
            <Text style={styles.narrativeBody}>
              {isOnline
                ? 'The live vehicle tracker has successfully synchronized with AUTOSECURE cloud servers. GPS telemetry, speed sensors, and perimeter geofencing are actively reporting. Real-time live tracking and remote commands are operational.'
                : 'The vehicle tracker has lost connection to the primary cellular network or ignition has been shut off. Last recorded telemetry has been cached locally and will sync once connection is restored.'}
            </Text>
          </View>
        </ScrollView>

        {/* Bottom Action Buttons (side-by-side with auto full width) */}
        <View style={styles.bottomBar}>
          <TouchableOpacity
            style={styles.dismissBtn}
            activeOpacity={0.8}
            onPress={onBack}
          >
            <Text style={styles.dismissBtnText}>Dismiss</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.actionBtn}
            activeOpacity={0.8}
            onPress={() => {
              if (onNavigateToMap) {
                onNavigateToMap();
              } else if (onNavigateToVehicle) {
                onNavigateToVehicle();
              } else {
                Alert.alert('Live Tracking', `Tracking ${vehicleName} on map`);
              }
            }}
          >
            <Text style={styles.actionBtnText}>Live Track</Text>
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    ...StyleSheet.absoluteFill,
    backgroundColor: '#F5F7FA',
  },
  container: {
    flex: 1,
    backgroundColor: '#F5F7FA',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 14,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#F0F2F5',
  },
  headerCircleBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    gap: 12,
  },
  card: {
    backgroundColor: '#FFFFFF',
    padding: 18,
    borderWidth: 1,
    borderColor: '#ECEFF3',
  },
  cardHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  iconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconCircleOnline: {
    backgroundColor: '#ECFDF5',
  },
  iconCircleOffline: {
    backgroundColor: '#F1F5F9',
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 12,
    gap: 6,
  },
  pillOnline: {
    backgroundColor: '#ECFDF5',
  },
  pillOffline: {
    backgroundColor: '#F1F5F9',
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  dotOnline: {
    backgroundColor: '#10B981',
  },
  dotOffline: {
    backgroundColor: '#64748B',
  },
  statusPillText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '600',
  },
  textOnline: {
    color: '#047857',
  },
  textOffline: {
    color: '#475569',
  },
  headlineTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  headlineSubtitle: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#4B5563',
    lineHeight: 18,
    marginBottom: 12,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 6,
  },
  metaText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  locationText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
    flex: 1,
  },
  sectionHeading: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#94A3B8',
    letterSpacing: 0.8,
    marginBottom: 12,
  },
  telemetryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  telemetryItem: {
    width: '47%',
    backgroundColor: '#F8FAFC',
    padding: 12,
    borderRadius: 8,
  },
  telemetryLabel: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    color: '#64748B',
    marginBottom: 4,
    textTransform: 'uppercase',
  },
  telemetryValue: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#1E2538',
  },
  telemetryInline: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  narrativeBody: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#4B5563',
    lineHeight: 20,
  },
  bottomBar: {
    flexDirection: 'row',
    gap: 12,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 24,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#F0F2F5',
  },
  dismissBtn: {
    flex: 1,
    backgroundColor: '#F3F4F6',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dismissBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  actionBtn: {
    flex: 1,
    backgroundColor: '#1E2538',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
