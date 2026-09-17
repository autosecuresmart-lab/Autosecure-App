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
import Svg, { Circle, Path, Rect } from 'react-native-svg';

import {
  BackArrowIcon,
  BatteryIcon,
  CheckVerifiedIcon,
  ClockIcon,
  NetworkIcon,
  PinIcon,
  PlaybackIcon,
  ShareIcon,
  SpeedArrowIcon,
} from '../components/HomeIcons';

interface ViewEventReportScreenProps {
  eventId?: string;
  title?: string;
  timestamp?: string;
  vehicleName?: string;
  location?: string;
  onBack?: () => void;
  onNavigateToPlayback?: () => void;
  onNavigateToLiveTrack?: () => void;
}

export function ViewEventReportScreen({
  title = 'Event Report',
  timestamp = '11th February 2026, 02:30:15 PM',
  vehicleName = 'Toyota Corolla',
  location = 'Third Mainland Bridge, Lagos, Nigeria',
  onBack,
  onNavigateToPlayback,
  onNavigateToLiveTrack,
}: ViewEventReportScreenProps): React.JSX.Element {
  const handleShare = () => {
    Alert.alert('Share Event', `Sharing event report for ${vehicleName}`);
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
            <BackArrowIcon color="#1E2538" size={20} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Event Details</Text>

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
          {/* Main Event Headline Card */}
          <View style={styles.headlineCard}>
            <View style={styles.headlineTopRow}>
              <View style={styles.badgeContainer}>
                <CheckVerifiedIcon color="#1E2538" size={26} />
              </View>
              <View style={styles.statusPill}>
                <View style={styles.statusDot} />
                <Text style={styles.statusText}>Verified Event</Text>
              </View>
            </View>

            <Text style={styles.eventTitle}>{title}</Text>

            <View style={styles.metaRow}>
              <ClockIcon color="#94A3B8" size={13} />
              <Text style={styles.metaText}>{timestamp}</Text>
            </View>

            <View style={styles.locationRow}>
              <PinIcon color="#DE8635" size={14} />
              <Text style={styles.locationText}>{location}</Text>
            </View>
          </View>

          {/* Mini Event Location Map Canvas */}
          <View style={styles.mapCard}>
            <View style={styles.mapCanvas}>
              <Svg width="100%" height="150" viewBox="0 0 350 150">
                <Rect width="350" height="150" fill="#E8EDF2" />
                <Path d="M-20 75 C100 80, 200 60, 370 70" stroke="#CBD5E1" strokeWidth="26" fill="none" />
                <Path d="M140 -10 C160 80, 150 120, 180 160" stroke="#CBD5E1" strokeWidth="20" fill="none" />
                <Path d="M-20 75 C100 80, 200 60, 370 70" stroke="#FEF08A" strokeWidth="6" fill="none" />

                {/* Event Marker */}
                <Circle cx="175" cy="72" r="28" fill="rgba(239, 68, 68, 0.18)" />
                <Circle cx="175" cy="72" r="14" fill="rgba(239, 68, 68, 0.4)" />
                <Circle cx="175" cy="72" r="6" fill="#EF4444" stroke="#FFFFFF" strokeWidth="2" />
              </Svg>

              <View style={styles.mapBadge}>
                <Text style={styles.mapBadgeText}>GPS: 6.5244° N, 3.3792° E</Text>
              </View>
            </View>
          </View>

          {/* Telemetry & Metrics Grid */}
          <Text style={styles.sectionHeader}>Telemetry at Event</Text>
          <View style={styles.metricsGrid}>
            {/* Speed Card */}
            <View style={styles.metricCard}>
              <View style={styles.metricHeader}>
                <SpeedArrowIcon color="#10B981" size={14} />
                <Text style={styles.metricLabel}>Speed</Text>
              </View>
              <Text style={styles.metricValue}>84 km/hr</Text>
            </View>

            {/* Battery Card */}
            <View style={styles.metricCard}>
              <View style={styles.metricHeader}>
                <BatteryIcon color="#94A3B8" size={14} />
                <Text style={styles.metricLabel}>Battery</Text>
              </View>
              <Text style={styles.metricValue}>65.0%</Text>
            </View>

            {/* Signal Card */}
            <View style={styles.metricCard}>
              <View style={styles.metricHeader}>
                <NetworkIcon color="#94A3B8" size={14} />
                <Text style={styles.metricLabel}>Network</Text>
              </View>
              <Text style={styles.metricValue}>4G LTE</Text>
            </View>

            {/* Device Card */}
            <View style={styles.metricCard}>
              <View style={styles.metricHeader}>
                <Text style={styles.metricLabel}>Vehicle</Text>
              </View>
              <Text style={styles.metricValue}>{vehicleName}</Text>
            </View>
          </View>

          {/* Event Narrative & Summary */}
          <Text style={styles.sectionHeader}>Event Summary</Text>
          <View style={styles.summaryCard}>
            <Text style={styles.summaryText}>
              A concise summary of the event, highlighting key activities, outcomes, and notable moments.
              The vehicle sensors triggered an active threshold notification along {location}. All safety telemetry systems remained responsive.
            </Text>
          </View>

          {/* Action Buttons Row */}
          <View style={styles.actionsContainer}>
            <TouchableOpacity
              style={styles.playbackBtn}
              onPress={() => {
                if (onNavigateToPlayback) {
                  onNavigateToPlayback();
                } else {
                  Alert.alert('Playback', 'Replaying event segment.');
                }
              }}
              activeOpacity={0.85}
            >
              <PlaybackIcon color="#FFFFFF" size={18} />
              <Text style={styles.playbackBtnText}>Playback Event</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.liveTrackBtn}
              onPress={() => {
                if (onNavigateToLiveTrack) {
                  onNavigateToLiveTrack();
                } else {
                  Alert.alert('Live Track', 'Opening live tracker.');
                }
              }}
              activeOpacity={0.85}
            >
              <Text style={styles.liveTrackBtnText}>Live Track</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    ...StyleSheet.absoluteFill,
    backgroundColor: '#FFFFFF',
    zIndex: 999,
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
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  headerCircleBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 2,
  },
  headerTitle: {
    fontFamily: 'Helvetica',
    fontSize: 18,
    fontWeight: '700',
    color: '#1E2538',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 40,
  },
  headlineCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 18,
    borderWidth: 1,
    borderColor: '#E8EDF5',
    marginBottom: 16,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  headlineTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  badgeContainer: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ECFDF5',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    gap: 6,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#10B981',
  },
  statusText: {
    fontFamily: 'Aeonik',
    fontSize: 11,
    fontWeight: '600',
    color: '#065F46',
  },
  eventTitle: {
    fontFamily: 'Helvetica',
    fontSize: 20,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 8,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 6,
  },
  metaText: {
    fontFamily: 'Aeonik',
    fontSize: 12,
    color: '#94A3B8',
  },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  locationText: {
    fontFamily: 'Aeonik',
    fontSize: 12.5,
    color: '#DE8635',
    fontWeight: '500',
  },
  mapCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#E8EDF5',
    marginBottom: 20,
  },
  mapCanvas: {
    height: 150,
    position: 'relative',
  },
  mapBadge: {
    position: 'absolute',
    bottom: 10,
    left: 10,
    backgroundColor: 'rgba(255, 255, 255, 0.92)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  mapBadgeText: {
    fontFamily: 'Aeonik',
    fontSize: 10,
    fontWeight: '600',
    color: '#1E2538',
  },
  sectionHeader: {
    fontFamily: 'Helvetica',
    fontSize: 14.5,
    fontWeight: '700',
    color: '#1E2538',
    marginBottom: 10,
  },
  metricsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 20,
  },
  metricCard: {
    width: '48.5%',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E8EDF5',
  },
  metricHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 6,
  },
  metricLabel: {
    fontFamily: 'Aeonik',
    fontSize: 11,
    color: '#94A3B8',
    fontWeight: '500',
  },
  metricValue: {
    fontFamily: 'Helvetica',
    fontSize: 15,
    fontWeight: '700',
    color: '#1E2538',
  },
  summaryCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E8EDF5',
    marginBottom: 24,
  },
  summaryText: {
    fontFamily: 'Aeonik',
    fontSize: 12.5,
    lineHeight: 18,
    color: '#475569',
  },
  actionsContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  playbackBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#1E2538',
    paddingVertical: 14,
    borderRadius: 12,
    gap: 8,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  playbackBtnText: {
    fontFamily: 'Aeonik',
    fontSize: 13,
    fontWeight: '600',
    color: '#FFFFFF',
  },
  liveTrackBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#CBD5E1',
    paddingVertical: 14,
    borderRadius: 12,
  },
  liveTrackBtnText: {
    fontFamily: 'Aeonik',
    fontSize: 13,
    fontWeight: '600',
    color: '#1E2538',
  },
});
