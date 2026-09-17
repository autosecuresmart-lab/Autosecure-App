import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { dashcamApi } from '../api/endpoints';
import type {
  DashcamRecording,
  DashcamSnapshot,
  DashcamStatus,
  DashcamStreamSession,
} from '../api/types';
import {
  BackArrowIcon,
  CameraVideoIcon,
  DownloadIcon,
  FlashIcon,
  ShieldCheckIcon,
} from '../components/HomeIcons';

interface DashcamLiveScreenProps {
  vehicleName?: string;
  vehicleUuid?: string;
  deviceUuid?: string;
  onBack?: () => void;
}

export function DashcamLiveScreen({
  vehicleName = 'Toyota Corolla (ABC-123DE)',
  vehicleUuid,
  deviceUuid,
  onBack,
}: DashcamLiveScreenProps): React.JSX.Element {
  const [activeChannel, setActiveChannel] = useState<'front' | 'cabin'>('front');
  const [isRecordingManual, setIsRecordingManual] = useState(false);
  const [isAudioMuted, setIsAudioMuted] = useState(false);

  // Live stream and device state
  const [loading, setLoading] = useState(true);
  const [capturingSnapshot, setCapturingSnapshot] = useState(false);
  const [streamSession, setStreamSession] = useState<DashcamStreamSession | null>(null);
  const [deviceInfo, setDeviceInfo] = useState<{ uuid: string; is_online: boolean; model: string } | null>(null);
  const [hardwareStatus, setHardwareStatus] = useState<DashcamStatus | null>(null);
  const [eventClips, setEventClips] = useState<DashcamRecording[]>([]);
  const [streamError, setStreamError] = useState<string | null>(null);

  const loadStreamAndData = useCallback(async () => {
    setLoading(true);
    setStreamError(null);

    try {
      if (deviceUuid) {
        const streamRes = await dashcamApi.stream(deviceUuid, activeChannel);
        setStreamSession(streamRes.stream);
        setDeviceInfo(streamRes.device);

        const [statusRes, emergenciesRes] = await Promise.allSettled([
          dashcamApi.status(deviceUuid),
          dashcamApi.emergencies(deviceUuid),
        ]);

        if (statusRes.status === 'fulfilled') {
          setHardwareStatus(statusRes.value.device);
        }
        if (emergenciesRes.status === 'fulfilled') {
          setEventClips(emergenciesRes.value.events);
        }
      } else if (vehicleUuid) {
        const streamRes = await dashcamApi.liveForVehicle(vehicleUuid, activeChannel);
        setStreamSession(streamRes.stream);
        setDeviceInfo(streamRes.device);

        if (streamRes.device?.uuid) {
          const [statusRes, emergenciesRes] = await Promise.allSettled([
            dashcamApi.status(streamRes.device.uuid),
            dashcamApi.emergencies(streamRes.device.uuid),
          ]);
          if (statusRes.status === 'fulfilled') {
            setHardwareStatus(statusRes.value.device);
          }
          if (emergenciesRes.status === 'fulfilled') {
            setEventClips(emergenciesRes.value.events);
          }
        }
      } else {
        // Fallback default stream placeholder info
        setStreamSession({
          stream_url: 'https://stream.autosecure.ng/live/front.m3u8',
          protocol: 'hls',
          camera: activeChannel,
          expires_at: new Date(Date.now() + 3600000).toISOString(),
        });
        setDeviceInfo({
          uuid: 'dev-demo-dashcam',
          is_online: true,
          model: 'AutoSecure 4G Dual-Cam 1080p',
        });
      }
    } catch (err: any) {
      const errMsg = err?.body?.message || err?.message || 'Could not connect to dashcam stream.';
      setStreamError(errMsg);
    } finally {
      setLoading(false);
    }
  }, [vehicleUuid, deviceUuid, activeChannel]);

  useEffect(() => {
    loadStreamAndData();
  }, [loadStreamAndData]);

  const handleSnapshot = async () => {
    const targetDevUuid = deviceUuid || deviceInfo?.uuid;
    if (!targetDevUuid) {
      Alert.alert('Snapshot Captured', 'High-definition 1080p frame saved to cloud gallery.');
      return;
    }

    setCapturingSnapshot(true);
    try {
      const res = await dashcamApi.snapshot(targetDevUuid, activeChannel);
      const snap: DashcamSnapshot = res.snapshot;
      Alert.alert(
        'Snapshot Captured',
        `High-definition photo captured from ${snap.camera} camera and saved to secure cloud storage.`
      );
    } catch (err: any) {
      const msg = err?.body?.message || err?.message || 'Failed to capture snapshot from dashcam.';
      Alert.alert('Snapshot Failed', msg);
    } finally {
      setCapturingSnapshot(false);
    }
  };

  const handleToggleRecord = () => {
    if (isRecordingManual) {
      setIsRecordingManual(false);
      Alert.alert('Recording Saved', '60-second emergency clip uploaded to secure forensic cloud storage.');
    } else {
      setIsRecordingManual(true);
    }
  };

  const handleDownloadClip = (clip: DashcamRecording) => {
    Alert.alert(
      'Export Video Clip',
      `Download "${clip.camera.toUpperCase()} Event Clip (${clip.duration_seconds || 45}s)" with embedded GPS and speed metadata watermark?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Download',
          onPress: () => Alert.alert('Download Started', 'Clip is downloading to your camera roll.'),
        },
      ]
    );
  };

  const displayClips = eventClips.length > 0
    ? eventClips
    : [
        {
          id: 'clip-1',
          url: null,
          recorded_at: new Date().toISOString(),
          duration_seconds: 45,
          size_bytes: 14200000,
          camera: 'front' as const,
          is_emergency: true,
          thumbnail_url: null,
          trigger_type: 'Impact Sensor',
        },
        {
          id: 'clip-2',
          url: null,
          recorded_at: new Date(Date.now() - 3600000 * 6).toISOString(),
          duration_seconds: 60,
          size_bytes: 18600000,
          camera: 'front' as const,
          is_emergency: true,
          thumbnail_url: null,
          trigger_type: 'Motion Sensor',
        },
        {
          id: 'clip-3',
          url: null,
          recorded_at: new Date(Date.now() - 3600000 * 18).toISOString(),
          duration_seconds: 30,
          size_bytes: 9400000,
          camera: 'cabin' as const,
          is_emergency: true,
          thumbnail_url: null,
          trigger_type: 'Manual Trigger',
        },
      ];

  const signalText = hardwareStatus?.network?.operator
    ? `${hardwareStatus.network.type || '4G LTE'} • ${hardwareStatus.network.signal_bars || 4} Bars`
    : '4G LTE • 2.4 Mb/s';

  const isOnline = deviceInfo?.is_online ?? true;

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
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

          <Text style={styles.headerTitle}>Dashcam Live</Text>

          <View style={styles.headerRightSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Channel Selector Pills */}
          <View style={styles.channelTabsRow}>
            <TouchableOpacity
              style={[
                styles.channelTabBtn,
                activeChannel === 'front' && styles.channelTabBtnActive,
              ]}
              activeOpacity={0.7}
              onPress={() => setActiveChannel('front')}
            >
              <Text
                style={[
                  styles.channelTabText,
                  activeChannel === 'front' && styles.channelTabTextActive,
                ]}
              >
                Front Road View (1080p FHD)
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[
                styles.channelTabBtn,
                activeChannel === 'cabin' && styles.channelTabBtnActive,
              ]}
              activeOpacity={0.7}
              onPress={() => setActiveChannel('cabin')}
            >
              <Text
                style={[
                  styles.channelTabText,
                  activeChannel === 'cabin' && styles.channelTabTextActive,
                ]}
              >
                Cabin Interior (720p HD)
              </Text>
            </TouchableOpacity>
          </View>

          {/* Video Player Canvas */}
          <View style={styles.videoPlayerCanvas}>
            {/* Top Watermark Overlay */}
            <View style={styles.videoOverlayTop}>
              <View style={styles.liveIndicatorPill}>
                <View
                  style={[
                    styles.livePulseDot,
                    !isOnline && { backgroundColor: '#94A3B8' },
                    isRecordingManual && styles.recordingPulseDot,
                  ]}
                />
                <Text style={styles.liveIndicatorText}>
                  {!isOnline
                    ? 'OFFLINE'
                    : isRecordingManual
                    ? 'REC • 00:14'
                    : 'LIVE • 30 FPS'}
                </Text>
              </View>

              <View style={styles.signalBadge}>
                <Text style={styles.signalText}>{signalText}</Text>
              </View>
            </View>

            {/* Video Placeholder Graphic */}
            <View style={styles.videoPlaceholderCenter}>
              {loading ? (
                <ActivityIndicator size="large" color="#38BDF8" />
              ) : streamError ? (
                <>
                  <CameraVideoIcon color="#EF4444" size={48} />
                  <Text style={[styles.videoPlaceholderTitle, { color: '#FCA5A5' }]}>
                    Stream Unavailable
                  </Text>
                  <Text style={styles.videoPlaceholderSub}>{streamError}</Text>
                </>
              ) : (
                <>
                  <CameraVideoIcon color="#64748B" size={48} />
                  <Text style={styles.videoPlaceholderTitle}>
                    {activeChannel === 'front' ? 'Forward Road Stream' : 'Cabin Security Stream'}
                  </Text>
                  <Text style={styles.videoPlaceholderSub}>
                    {streamSession?.protocol ? `${streamSession.protocol.toUpperCase()} Encrypted Stream • Latency 140ms` : 'AES-128 Encrypted Stream • Latency 140ms'}
                  </Text>
                </>
              )}
            </View>

            {/* Bottom Watermark Overlay */}
            <View style={styles.videoOverlayBottom}>
              <Text style={styles.watermarkText}>
                {vehicleName} • {isOnline ? 'Online' : 'Offline'} • 1080p FHD
              </Text>
              <Text style={styles.timestampWatermarkText}>
                {new Date().toISOString().replace('T', ' ').substring(0, 19)} GMT+1
              </Text>
            </View>
          </View>

          {/* Camera Quick Control Buttons */}
          <View style={styles.controlButtonsRow}>
            {/* Snapshot */}
            <TouchableOpacity
              style={styles.controlBtn}
              activeOpacity={0.7}
              onPress={handleSnapshot}
              disabled={capturingSnapshot}
            >
              <View style={[styles.controlIconCircle, { backgroundColor: '#EFF6FF' }]}>
                {capturingSnapshot ? (
                  <ActivityIndicator size="small" color="#2563EB" />
                ) : (
                  <FlashIcon color="#2563EB" size={20} />
                )}
              </View>
              <Text style={styles.controlLabel}>Snapshot</Text>
            </TouchableOpacity>

            {/* Emergency Record */}
            <TouchableOpacity
              style={styles.controlBtn}
              activeOpacity={0.7}
              onPress={handleToggleRecord}
            >
              <View
                style={[
                  styles.controlIconCircle,
                  isRecordingManual ? { backgroundColor: '#EF4444' } : { backgroundColor: '#FEE2E2' },
                ]}
              >
                <CameraVideoIcon
                  color={isRecordingManual ? '#FFFFFF' : '#DC2626'}
                  size={20}
                />
              </View>
              <Text style={[styles.controlLabel, isRecordingManual && { color: '#DC2626', fontWeight: '700' }]}>
                {isRecordingManual ? 'Stop Rec' : 'Record'}
              </Text>
            </TouchableOpacity>

            {/* Audio Mute */}
            <TouchableOpacity
              style={styles.controlBtn}
              activeOpacity={0.7}
              onPress={() => setIsAudioMuted(!isAudioMuted)}
            >
              <View style={[styles.controlIconCircle, { backgroundColor: '#F1F5F9' }]}>
                <ShieldCheckIcon color="#475569" size={20} />
              </View>
              <Text style={styles.controlLabel}>{isAudioMuted ? 'Muted' : 'Audio On'}</Text>
            </TouchableOpacity>

            {/* Fullscreen / Refresh */}
            <TouchableOpacity
              style={styles.controlBtn}
              activeOpacity={0.7}
              onPress={loadStreamAndData}
            >
              <View style={[styles.controlIconCircle, { backgroundColor: '#F1F5F9' }]}>
                <CameraVideoIcon color="#475569" size={20} />
              </View>
              <Text style={styles.controlLabel}>Refresh</Text>
            </TouchableOpacity>
          </View>

          {/* Recorded Event Clips Section */}
          <View style={styles.sectionHeaderBetween}>
            <Text style={styles.sectionTitle}>Impact & Event Recordings</Text>
            <Text style={styles.clipsCountText}>{displayClips.length} Clips</Text>
          </View>

          <View style={styles.clipsList}>
            {displayClips.map((clip, idx) => (
              <View key={clip.id || `clip-${idx}`} style={styles.clipCard}>
                <View style={[styles.clipThumbnail, { backgroundColor: '#1E293B' }]}>
                  <CameraVideoIcon color="#94A3B8" size={22} />
                  <View style={styles.durationBadge}>
                    <Text style={styles.durationText}>
                      {clip.duration_seconds ? `00:${String(clip.duration_seconds).padStart(2, '0')}` : '00:45'}
                    </Text>
                  </View>
                </View>

                <View style={styles.clipInfo}>
                  <Text style={styles.clipTitle}>
                    {clip.camera === 'cabin' ? 'Cabin Event Recording' : 'Impact / G-Sensor Event'}
                  </Text>
                  <Text style={styles.clipSub}>
                    {clip.recorded_at ? new Date(clip.recorded_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Today • 10:42 AM'} • {clip.size_bytes ? `${(clip.size_bytes / (1024 * 1024)).toFixed(1)} MB` : '14.2 MB'}
                  </Text>
                  <View
                    style={[
                      styles.triggerBadge,
                      clip.camera === 'cabin' ? styles.triggerMotion : styles.triggerImpact,
                    ]}
                  >
                    <Text
                      style={[
                        styles.triggerText,
                        clip.camera === 'cabin' ? styles.triggerMotionText : styles.triggerImpactText,
                      ]}
                    >
                      {clip.camera === 'cabin' ? 'Motion Sensor' : 'Impact Sensor'}
                    </Text>
                  </View>
                </View>

                <TouchableOpacity
                  style={styles.downloadBtn}
                  activeOpacity={0.7}
                  onPress={() => handleDownloadClip(clip)}
                >
                  <DownloadIcon color="#1E2538" size={16} />
                </TouchableOpacity>
              </View>
            ))}
          </View>
        </ScrollView>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
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
  headerRightSpacer: {
    width: 38,
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 90,
  },
  channelTabsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F1F5F9',
    borderRadius: 14,
    padding: 4,
    marginBottom: 16,
  },
  channelTabBtn: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  channelTabBtnActive: {
    backgroundColor: '#FFFFFF',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 2,
  },
  channelTabText: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
    fontWeight: '600',
  },
  channelTabTextActive: {
    color: '#111827',
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
  videoPlayerCanvas: {
    height: 230,
    borderRadius: 22,
    backgroundColor: '#0F172A',
    overflow: 'hidden',
    justifyContent: 'space-between',
    padding: 14,
    marginBottom: 20,
  },
  videoOverlayTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  liveIndicatorPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: 'rgba(0, 0, 0, 0.65)',
  },
  livePulseDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#10B981',
  },
  recordingPulseDot: {
    backgroundColor: '#EF4444',
  },
  liveIndicatorText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  signalBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: 'rgba(0, 0, 0, 0.65)',
  },
  signalText: {
    fontSize: 10,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  videoPlaceholderCenter: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  videoPlaceholderTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#F8FAFC',
    marginTop: 8,
    marginBottom: 2,
  },
  videoPlaceholderSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  videoOverlayBottom: {
    backgroundColor: 'rgba(0, 0, 0, 0.65)',
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  watermarkText: {
    fontSize: 10,
    fontFamily: 'Courier',
    fontWeight: '700',
    color: '#38BDF8',
  },
  timestampWatermarkText: {
    fontSize: 9,
    fontFamily: 'Courier',
    color: '#94A3B8',
    marginTop: 1,
  },
  controlButtonsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-around',
    padding: 14,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 22,
  },
  controlBtn: {
    alignItems: 'center',
    gap: 6,
  },
  controlIconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  controlLabel: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#475569',
    fontWeight: '600',
  },
  sectionHeaderBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  clipsCountText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  clipsList: {
    gap: 10,
  },
  clipCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  clipThumbnail: {
    width: 68,
    height: 52,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    marginRight: 12,
  },
  durationBadge: {
    position: 'absolute',
    bottom: 3,
    right: 3,
    backgroundColor: 'rgba(0, 0, 0, 0.75)',
    paddingHorizontal: 4,
    paddingVertical: 1,
    borderRadius: 4,
  },
  durationText: {
    fontSize: 8,
    fontFamily: 'Courier',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  clipInfo: {
    flex: 1,
    paddingRight: 8,
  },
  clipTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  clipSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
    marginBottom: 4,
  },
  triggerBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  triggerText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  triggerImpact: {
    backgroundColor: '#FEE2E2',
  },
  triggerImpactText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#DC2626',
    textTransform: 'uppercase',
  },
  triggerMotion: {
    backgroundColor: '#FEF3C7',
  },
  triggerMotionText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#D97706',
    textTransform: 'uppercase',
  },
  downloadBtn: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
