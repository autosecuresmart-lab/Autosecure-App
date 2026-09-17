import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Svg, { Circle, Line, Path, Rect } from 'react-native-svg';

import { securityApi } from '../api/endpoints';
import type { TheftEvent } from '../api/types';
import {
  AlertTriangleIcon,
  BackArrowIcon,
  BatteryIcon,
  CarDeliveryIcon,
  CarLicensePlateIcon,
  DocumentTextIcon,
  PhoneCallIcon,
  PinIcon,
  ShareIcon,
  ShieldCheckIcon,
  SupportAgentIcon,
} from '../components/HomeIcons';

interface ActiveTheftIncidentScreenProps {
  route?: {
    params?: {
      incidentId?: string;
      vehicleName?: string;
      location?: string;
    };
  };
  incidentId?: string;
  vehicleName?: string;
  reportedTime?: string;
  location?: string;
  onBack?: () => void;
  onCallSupport?: () => void;
  onNavigateToShare?: () => void;
  onNavigateToAlerts?: () => void;
  onNavigateToHistory?: () => void;
}

type IncidentTab = 'Timeline' | 'Details' | 'Updates';

interface TimelineEvent {
  id: string;
  time: string;
  title: string;
  subtitle: string;
}

export function ActiveTheftIncidentScreen({
  route,
  incidentId: propIncidentId,
  vehicleName: propVehicleName,
  location: propLocation,
  onBack,
  onCallSupport,
  onNavigateToShare,
  onNavigateToAlerts,
  onNavigateToHistory,
}: ActiveTheftIncidentScreenProps): React.JSX.Element {
  const incidentId = route?.params?.incidentId ?? propIncidentId ?? 'INC-2026-000245';
  const initialVehicleName = route?.params?.vehicleName ?? propVehicleName ?? 'Toyota Corolla';
  const initialLocation = route?.params?.location ?? propLocation ?? 'Victoria Island, Lagos';

  const [activeTab, setActiveTab] = useState<IncidentTab>('Timeline');
  const [theftEvent, setTheftEvent] = useState<TheftEvent | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const [isResolveModalVisible, setIsResolveModalVisible] = useState(false);
  const [resolutionNote, setResolutionNote] = useState('Vehicle recovered safely by recovery dispatch team.');
  const [isResolving, setIsResolving] = useState(false);
  const [isResolvedSuccess, setIsResolvedSuccess] = useState(false);

  useEffect(() => {
    loadIncident();
  }, [incidentId]);

  const loadIncident = async () => {
    setIsLoading(true);
    try {
      const res = await securityApi.theftEvent(incidentId);
      if (res.theft_event) {
        setTheftEvent(res.theft_event);
        if (!res.theft_event.is_open) {
          setIsResolvedSuccess(true);
        }
      }
    } catch (err) {
      console.warn('Unable to load theft event details', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleShare = () => {
    if (onNavigateToShare) {
      onNavigateToShare();
    } else {
      Alert.alert('Share Incident', `Incident #${incidentId} tracking broadcast link copied.`);
    }
  };

  const handleCall = () => {
    if (onCallSupport) {
      onCallSupport();
    } else {
      Alert.alert('Support Dispatch', 'Calling AUTOSECURE Emergency Control: +234 803 123 4567');
    }
  };

  const handleConfirmResolve = async () => {
    if (!resolutionNote.trim()) {
      Alert.alert('Validation Error', 'Please enter a resolution note.');
      return;
    }
    setIsResolving(true);
    try {
      await securityApi.resolveTheftEvent(incidentId, {
        resolution_note: resolutionNote,
        status: 'resolved',
      });
      setIsResolveModalVisible(false);
      setIsResolvedSuccess(true);
      loadIncident();
    } catch (err: any) {
      Alert.alert('Resolution Error', err?.message || 'Could not resolve incident.');
    } finally {
      setIsResolving(false);
    }
  };

  const displayVehicle = (theftEvent as any)?.vehicle?.display_name ?? initialVehicleName;
  const displayPlate = (theftEvent as any)?.vehicle?.plate_number ?? 'ABC-123DE';
  const liveSpeed = (theftEvent as any)?.live_telemetry?.speed_kph ? `${Math.round((theftEvent as any).live_telemetry.speed_kph)} Km/hr` : '0 Km/hr';
  const liveLat = (theftEvent as any)?.live_telemetry?.latitude ?? (theftEvent?.last_known_location?.latitude ?? 6.4382);
  const liveLng = (theftEvent as any)?.live_telemetry?.longitude ?? (theftEvent?.last_known_location?.longitude ?? 3.4721);

  const timelineEvents: TimelineEvent[] = [
    {
      id: 'e1',
      time: 'Just Now',
      title: theftEvent?.is_open ? 'Rapid GPS Tracking Active' : 'Incident Resolved',
      subtitle: theftEvent?.is_open ? `Fix at ${liveLat.toFixed(4)}, ${liveLng.toFixed(4)} (${liveSpeed})` : (theftEvent?.resolution_note ?? 'Vehicle recovered safely.'),
    },
    {
      id: 'e2',
      time: '10:45 AM',
      title: 'Recovery Team Dispatched',
      subtitle: 'Armed recovery patrol unit responding.',
    },
    {
      id: 'e3',
      time: '10:43 AM',
      title: 'Remote Immobiliser Command Sent',
      subtitle: 'Engine cutoff signal acknowledged by device.',
    },
    {
      id: 'e4',
      time: theftEvent?.triggered_at ? new Date(theftEvent.triggered_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '10:42 AM',
      title: 'Emergency Theft Mode Triggered',
      subtitle: `Owner activated alert for ${displayVehicle}.`,
    },
  ];

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right', 'bottom']}>
      <View style={styles.container}>
        {/* Top Emergency Banner */}
        <View style={[styles.emergencyBanner, !theftEvent?.is_open && { backgroundColor: '#DCFCE7', borderColor: '#86EFAC' }]}>
          {theftEvent?.is_open ? (
            <>
              <AlertTriangleIcon color="#DC2626" size={18} />
              <Text style={styles.emergencyBannerText}>Active Theft Incident</Text>
            </>
          ) : (
            <>
              <ShieldCheckIcon color="#16A34A" size={18} />
              <Text style={[styles.emergencyBannerText, { color: '#16A34A' }]}>Incident Resolved & Recovered</Text>
            </>
          )}
        </View>

        {/* Header Bar */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={onBack}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <BackArrowIcon color="#1E2538" size={18} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Incident #{incidentId.substring(0, 14)}</Text>

          <TouchableOpacity
            style={styles.headerCircleBtn}
            activeOpacity={0.7}
            onPress={handleShare}
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
          {isLoading && (
            <ActivityIndicator size="small" color="#DC2626" style={{ marginVertical: 12 }} />
          )}

          {/* Recovery Team Assigned Card */}
          <View style={styles.card}>
            <View style={styles.cardTopRow}>
              <Text style={styles.recoveryTitle}>
                {theftEvent?.is_open && !isResolvedSuccess ? 'Recovery Team Assigned' : 'Vehicle Recovered'}
              </Text>
              <View style={[styles.criticalBadge, (!theftEvent?.is_open || isResolvedSuccess) && { backgroundColor: '#DCFCE7' }]}>
                <Text style={[styles.criticalBadgeText, (!theftEvent?.is_open || isResolvedSuccess) && { color: '#16A34A' }]}>
                  {theftEvent?.is_open && !isResolvedSuccess ? 'Critical' : 'Resolved'}
                </Text>
              </View>
            </View>

            <View style={styles.metaRow}>
              <View style={styles.metaCol}>
                <Text style={styles.metaLabel}>Incident ID</Text>
                <Text style={styles.metaValue}>{incidentId.substring(0, 16)}</Text>
              </View>
              <View style={[styles.metaCol, { alignItems: 'flex-end' }]}>
                <Text style={styles.metaLabel}>Vehicle</Text>
                <Text style={styles.metaValue}>{displayVehicle}</Text>
              </View>
            </View>
          </View>

          {/* Segmented Tab Control */}
          <View style={styles.segmentedControl}>
            {(['Timeline', 'Details', 'Updates'] as IncidentTab[]).map((tab) => {
              const isActive = activeTab === tab;
              return (
                <TouchableOpacity
                  key={tab}
                  style={[styles.segmentBtn, isActive && styles.segmentBtnActive]}
                  onPress={() => setActiveTab(tab)}
                  activeOpacity={0.8}
                >
                  <Text style={[styles.segmentText, isActive && styles.segmentTextActive]}>
                    {tab}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>

          {/* 1. Tab Content: Timeline */}
          {activeTab === 'Timeline' && (
            <View style={styles.timelineContainer}>
              <Text style={styles.timelineSectionTitle}>Incident Timeline</Text>

              <View style={styles.timelineWrapper}>
                <View style={styles.verticalRail} />

                <View style={styles.cardsStack}>
                  {timelineEvents.map((evt) => (
                    <View key={evt.id} style={styles.timelineItemRow}>
                      <View style={styles.railDot} />

                      <View style={styles.timelineEventCard}>
                        <Text style={styles.eventTime}>{evt.time}</Text>
                        <Text style={styles.eventTitle}>{evt.title}</Text>
                        <Text style={styles.eventSubtitle}>{evt.subtitle}</Text>
                      </View>
                    </View>
                  ))}
                </View>
              </View>
            </View>
          )}

          {/* 2. Tab Content: Details */}
          {activeTab === 'Details' && (
            <View style={styles.detailsContainer}>
              <Text style={styles.timelineSectionTitle}>Incident Details</Text>

              <View style={styles.detailsCard}>
                <View style={styles.detailRow}>
                  <View style={styles.detailIconBox}>
                    <CarDeliveryIcon color="#1E2538" size={18} />
                  </View>
                  <View style={styles.detailTextBox}>
                    <Text style={styles.detailLabel}>Vehicle</Text>
                    <Text style={styles.detailValue}>{displayVehicle}</Text>
                  </View>
                </View>

                <View style={styles.detailRow}>
                  <View style={styles.detailIconBox}>
                    <DocumentTextIcon color="#1E2538" size={18} />
                  </View>
                  <View style={styles.detailTextBox}>
                    <Text style={styles.detailLabel}>Incident UUID</Text>
                    <Text style={styles.detailValue}>{incidentId.substring(0, 18)}...</Text>
                  </View>
                </View>

                <View style={styles.detailRow}>
                  <View style={styles.detailIconBox}>
                    <CarLicensePlateIcon color="#1E2538" size={16} />
                  </View>
                  <View style={styles.detailTextBox}>
                    <Text style={styles.detailLabel}>Plate Number</Text>
                    <Text style={styles.detailValue}>{displayPlate}</Text>
                  </View>
                </View>

                <View style={styles.detailRow}>
                  <View style={styles.detailIconBox}>
                    <PinIcon color="#10B981" size={18} />
                  </View>
                  <View style={styles.detailTextBox}>
                    <Text style={styles.detailLabel}>GPS Tracking Status</Text>
                    <Text style={[styles.detailValue, { color: '#10B981' }]}>1-Second Live Stream Active</Text>
                  </View>
                </View>

                <View style={[styles.detailRow, { borderBottomWidth: 0 }]}>
                  <View style={styles.detailIconBox}>
                    <BatteryIcon color="#10B981" size={18} />
                  </View>
                  <View style={styles.detailTextBox}>
                    <Text style={styles.detailLabel}>Current Speed</Text>
                    <Text style={[styles.detailValue, { color: '#1E293B' }]}>{liveSpeed}</Text>
                  </View>
                </View>
              </View>

              {/* Support Contact */}
              <View style={styles.supportContactCard}>
                <View style={styles.supportAvatar}>
                  <SupportAgentIcon color="#10B981" size={18} />
                </View>
                <View style={styles.supportInfo}>
                  <Text style={styles.supportLabel}>Recovery Dispatch Line</Text>
                  <Text style={styles.supportPhone}>+234 803 123 4567</Text>
                </View>
                <TouchableOpacity
                  style={styles.callCircleBtn}
                  activeOpacity={0.8}
                  onPress={handleCall}
                >
                  <PhoneCallIcon color="#1E2538" size={16} />
                </TouchableOpacity>
              </View>
            </View>
          )}

          {/* 3. Tab Content: Updates */}
          {activeTab === 'Updates' && (
            <View style={styles.card}>
              <View style={styles.cardTopRow}>
                <Text style={styles.sectionHeader}>LIVE RECOVERY UPDATES</Text>
                <TouchableOpacity onPress={onNavigateToAlerts}>
                  <Text style={styles.viewAllLink}>View All Alerts</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.bodyText}>
                • Real-time triangulation: {liveLat.toFixed(4)}, {liveLng.toFixed(4)}{'\n'}
                • Remote ignition cutoff relay: ACTIVE{'\n'}
                • Recovery response unit: EN ROUTE{'\n'}
                • Status: {theftEvent?.is_open ? 'OPEN INCIDENT' : 'RESOLVED'}
              </Text>

              {theftEvent?.is_open && (
                <TouchableOpacity
                  style={styles.markRecoveredBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsResolveModalVisible(true)}
                >
                  <ShieldCheckIcon color="#10B981" size={18} />
                  <Text style={styles.markRecoveredText}>Resolve & Mark Vehicle Recovered</Text>
                </TouchableOpacity>
              )}
            </View>
          )}

          {/* Live Vector Map Section */}
          <View style={styles.card}>
            <Text style={styles.sectionHeader}>Live GPS Coordinates</Text>

            <View style={styles.mapCanvas}>
              <Svg width="100%" height={150} viewBox="0 0 320 150">
                <Rect x="0" y="0" width="320" height="150" fill="#E2E8F0" rx="12" />

                <Line x1="0" y1="40" x2="320" y2="40" stroke="#CBD5E1" strokeWidth="3" />
                <Line x1="0" y1="90" x2="320" y2="90" stroke="#CBD5E1" strokeWidth="4" />
                <Line x1="0" y1="120" x2="320" y2="120" stroke="#CBD5E1" strokeWidth="2" />
                <Line x1="60" y1="0" x2="60" y2="150" stroke="#CBD5E1" strokeWidth="3" />
                <Line x1="160" y1="0" x2="160" y2="150" stroke="#CBD5E1" strokeWidth="5" />
                <Line x1="250" y1="0" x2="250" y2="150" stroke="#CBD5E1" strokeWidth="3" />

                <Path d="M0 140 L320 20" stroke="#FFFFFF" strokeWidth="8" />
                <Path d="M0 140 L320 20" stroke="#94A3B8" strokeWidth="5" />

                <Circle cx="160" cy="75" r="28" fill="rgba(239, 68, 68, 0.15)" />
                <Circle cx="160" cy="75" r="16" fill="rgba(239, 68, 68, 0.3)" />
                <Circle cx="160" cy="75" r="7" fill="#EF4444" />
                <Circle cx="160" cy="75" r="3" fill="#FFFFFF" />
              </Svg>

              <View style={styles.mapLabelCard}>
                <PinIcon color="#EF4444" size={14} />
                <Text style={styles.mapLabelText} numberOfLines={1}>
                  {liveLat.toFixed(4)}, {liveLng.toFixed(4)} ({initialLocation})
                </Text>
              </View>
            </View>
          </View>
        </ScrollView>

        {/* Bottom Bar */}
        <View style={styles.bottomBar}>
          <TouchableOpacity
            style={styles.callSupportBtn}
            activeOpacity={0.8}
            onPress={handleCall}
          >
            <PhoneCallIcon color="#1E2538" size={18} />
            <Text style={styles.callSupportText}>Call Support</Text>
          </TouchableOpacity>

          {theftEvent?.is_open ? (
            <TouchableOpacity
              style={styles.resolvePrimaryBtn}
              activeOpacity={0.8}
              onPress={() => setIsResolveModalVisible(true)}
            >
              <ShieldCheckIcon color="#FFFFFF" size={18} />
              <Text style={styles.resolvePrimaryBtnText}>Resolve Incident</Text>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity
              style={styles.shareBtn}
              activeOpacity={0.8}
              onPress={handleShare}
            >
              <ShareIcon color="#1E2538" size={18} />
              <Text style={styles.shareText}>Share Report</Text>
            </TouchableOpacity>
          )}
        </View>

        {/* Resolution Bottom Sheet Modal */}
        <Modal
          visible={isResolveModalVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setIsResolveModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsResolveModalVisible(false)}
            />
            <View style={styles.bottomSheetCard}>
              <View style={styles.dragHandle} />
              <Text style={styles.modalTitle}>Resolve Theft Incident</Text>
              <Text style={styles.modalSubtitle}>
                Add a resolution note to close this theft event and record safe recovery.
              </Text>

              <Text style={styles.inputLabel}>Resolution Note</Text>
              <TextInput
                style={styles.modalTextArea}
                multiline
                numberOfLines={3}
                value={resolutionNote}
                onChangeText={setResolutionNote}
                placeholder="Describe how the vehicle was recovered..."
              />

              <TouchableOpacity
                style={[styles.primaryConfirmBtn, isResolving && { opacity: 0.6 }]}
                onPress={handleConfirmResolve}
                disabled={isResolving}
              >
                {isResolving ? (
                  <ActivityIndicator color="#FFFFFF" size="small" />
                ) : (
                  <Text style={styles.primaryConfirmBtnText}>Confirm Resolution</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </Modal>
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
  emergencyBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FEE2E2',
    borderBottomWidth: 1,
    borderColor: '#FECACA',
    paddingVertical: 10,
    gap: 8,
  },
  emergencyBannerText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#DC2626',
    letterSpacing: 0.5,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderColor: '#F1F5F9',
  },
  headerCircleBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#1E2538',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    gap: 16,
    paddingBottom: 40,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 2,
  },
  cardTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  recoveryTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#1E2538',
  },
  criticalBadge: {
    backgroundColor: '#FEE2E2',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  criticalBadgeText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#DC2626',
  },
  metaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  metaCol: {
    gap: 2,
  },
  metaLabel: {
    fontSize: 11,
    fontWeight: '600',
    color: '#94A3B8',
  },
  metaValue: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E2538',
  },
  segmentedControl: {
    flexDirection: 'row',
    backgroundColor: '#F1F5F9',
    borderRadius: 12,
    padding: 4,
  },
  segmentBtn: {
    flex: 1,
    paddingVertical: 10,
    alignItems: 'center',
    borderRadius: 10,
  },
  segmentBtnActive: {
    backgroundColor: '#FFFFFF',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 2,
    elevation: 2,
  },
  segmentText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#64748B',
  },
  segmentTextActive: {
    fontWeight: '800',
    color: '#1E2538',
  },
  timelineContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  timelineSectionTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#1E2538',
    marginBottom: 14,
  },
  timelineWrapper: {
    position: 'relative',
    paddingLeft: 10,
  },
  verticalRail: {
    position: 'absolute',
    left: 17,
    top: 10,
    bottom: 10,
    width: 2,
    backgroundColor: '#E2E8F0',
  },
  cardsStack: {
    gap: 14,
  },
  timelineItemRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  railDot: {
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#2563EB',
    borderWidth: 3,
    borderColor: '#DBEAFE',
    marginRight: 12,
    marginTop: 4,
  },
  timelineEventCard: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  eventTime: {
    fontSize: 10,
    fontWeight: '700',
    color: '#64748B',
    marginBottom: 2,
  },
  eventTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1E2538',
    marginBottom: 2,
  },
  eventSubtitle: {
    fontSize: 12,
    color: '#475569',
  },
  detailsContainer: {
    gap: 14,
  },
  detailsCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderColor: '#F1F5F9',
  },
  detailIconBox: {
    width: 32,
    height: 32,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  detailTextBox: {
    flex: 1,
  },
  detailLabel: {
    fontSize: 11,
    color: '#94A3B8',
    fontWeight: '600',
  },
  detailValue: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E2538',
  },
  supportContactCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  supportAvatar: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#EFF6FF',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  supportInfo: {
    flex: 1,
  },
  supportLabel: {
    fontSize: 11,
    color: '#94A3B8',
    fontWeight: '600',
  },
  supportPhone: {
    fontSize: 14,
    fontWeight: '800',
    color: '#1E2538',
  },
  callCircleBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionHeader: {
    fontSize: 14,
    fontWeight: '800',
    color: '#1E2538',
    marginBottom: 10,
  },
  bodyText: {
    fontSize: 12,
    color: '#475569',
    lineHeight: 20,
    marginBottom: 14,
  },
  viewAllLink: {
    fontSize: 12,
    fontWeight: '700',
    color: '#2563EB',
  },
  markRecoveredBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#ECFDF5',
    borderWidth: 1,
    borderColor: '#A7F3D0',
    paddingVertical: 12,
    borderRadius: 12,
  },
  markRecoveredText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#065F46',
  },
  mapCanvas: {
    borderRadius: 12,
    overflow: 'hidden',
    position: 'relative',
  },
  mapLabelCard: {
    position: 'absolute',
    bottom: 8,
    left: 8,
    right: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.94)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  mapLabelText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#1E2538',
    flex: 1,
  },
  bottomBar: {
    flexDirection: 'row',
    padding: 16,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderColor: '#F1F5F9',
    gap: 12,
  },
  callSupportBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: '#F1F5F9',
  },
  callSupportText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1E2538',
  },
  shareBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: '#F1F5F9',
  },
  shareText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1E2538',
  },
  resolvePrimaryBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: '#16A34A',
  },
  resolvePrimaryBtnText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  modalBackdrop: {
    flex: 1,
    justifyContent: 'flex-end',
    backgroundColor: 'rgba(0, 0, 0, 0.4)',
  },
  modalDismissArea: {
    flex: 1,
  },
  bottomSheetCard: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
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
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1E293B',
    marginBottom: 6,
  },
  modalSubtitle: {
    fontSize: 13,
    color: '#64748B',
    lineHeight: 18,
    marginBottom: 16,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#475569',
    marginBottom: 6,
  },
  modalTextArea: {
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 14,
    color: '#1E293B',
    minHeight: 80,
    textAlignVertical: 'top',
    marginBottom: 18,
  },
  primaryConfirmBtn: {
    backgroundColor: '#16A34A',
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: 'center',
  },
  primaryConfirmBtnText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
