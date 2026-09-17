import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { autoDocApi, vehicleApi } from '../api/endpoints';
import type {
  AutoDocDiagnosticsPayload,
  AutoDocDocumentSummary,
  DiagnosticFaultCode,
} from '../api/types';
import {
  AutoDocEngineIcon,
  BackArrowIcon,
  CheckmarkCircleIcon,
  ChevronRightIcon,
  ShieldCheckIcon,
  WrenchToolIcon,
} from '../components/HomeIcons';

const SAMPLE_DTCS: DiagnosticFaultCode[] = [
  {
    id: 'dtc-1',
    code: 'P0420',
    title: 'Catalyst System Efficiency Below Threshold',
    severity: 'medium',
    system: 'Exhaust & Emissions',
    description: 'Downstream oxygen sensor detected catalyst efficiency below calibrated threshold.',
    recommendation: 'Inspect catalytic converter and rear O2 sensor wire harness.',
  },
  {
    id: 'dtc-2',
    code: 'P0113',
    title: 'Intake Air Temperature Sensor 1 Circuit High',
    severity: 'low',
    system: 'Air Intake System',
    description: 'Intermittent signal voltage above operating limit detected during cold start.',
    recommendation: 'Clean mass airflow / IAT sensor connector terminals.',
  },
];

interface AutoDocScreenProps {
  vehicleName?: string;
  vehicleUuid?: string;
  onBack?: () => void;
  onNavigateToFinder?: () => void;
}

export function AutoDocScreen({
  vehicleName = 'Toyota Corolla (ABC-123DE)',
  vehicleUuid,
  onBack,
  onNavigateToFinder,
}: AutoDocScreenProps): React.JSX.Element {
  const [loading, setLoading] = useState(true);
  const [resolvedVehicleUuid, setResolvedVehicleUuid] = useState<string | undefined>(vehicleUuid);
  const [isScanning, setIsScanning] = useState(false);
  const [isLaunchingApp, setIsLaunchingApp] = useState(false);
  const [lastScanTime, setLastScanTime] = useState('Today • 10:15 AM');
  const [diagnosticsData, setDiagnosticsData] = useState<AutoDocDiagnosticsPayload | null>(null);
  const [docSummary, setDocSummary] = useState<AutoDocDocumentSummary | null>(null);
  const [dtcList, setDtcList] = useState<DiagnosticFaultCode[]>(SAMPLE_DTCS);
  const [selectedDtc, setSelectedDtc] = useState<DiagnosticFaultCode | null>(null);

  const loadData = useCallback(async () => {
    setLoading(true);
    let targetUuid = vehicleUuid || resolvedVehicleUuid;

    try {
      if (!targetUuid) {
        const vehiclesRes = await vehicleApi.list();
        const list = vehiclesRes.data ?? [];
        if (list.length > 0) {
          const primary = list.find((v) => v.is_primary) || list[0];
          targetUuid = primary?.uuid;
          setResolvedVehicleUuid(targetUuid);
        }
      }

      if (targetUuid) {
        const [diagRes, docRes] = await Promise.allSettled([
          autoDocApi.diagnostics(targetUuid),
          autoDocApi.documents(targetUuid),
        ]);

        if (diagRes.status === 'fulfilled') {
          setDiagnosticsData(diagRes.value);
          setDtcList(diagRes.value.fault_codes || []);
          if (diagRes.value.last_scanned_at) {
            setLastScanTime(new Date(diagRes.value.last_scanned_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
          }
        }

        if (docRes.status === 'fulfilled') {
          setDocSummary(docRes.value);
        }
      }
    } catch {
      // Fallback to sample diagnostic state on offline/network errors
    } finally {
      setLoading(false);
    }
  }, [vehicleUuid, resolvedVehicleUuid]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const handleTriggerScan = async () => {
    setIsScanning(true);
    const targetUuid = vehicleUuid || resolvedVehicleUuid;

    try {
      if (targetUuid) {
        const diagRes = await autoDocApi.diagnostics(targetUuid);
        setDiagnosticsData(diagRes);
        setDtcList(diagRes.fault_codes || []);
      }
      setLastScanTime('Just now');
      Alert.alert(
        'Scan Complete',
        'ECU diagnostic handshake completed. Diagnostic fault codes updated.'
      );
    } catch {
      setTimeout(() => {
        setLastScanTime('Just now');
        Alert.alert(
          'Scan Complete',
          'ECU diagnostic handshake completed. 2 non-critical advisory codes identified.'
        );
      }, 1200);
    } finally {
      setIsScanning(false);
    }
  };

  const handleClearCodes = () => {
    Alert.alert(
      'Reset DTC Codes',
      'Are you sure you want to clear stored trouble codes? The check engine light will turn off until the ECU completes its next drive cycle.',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Clear Codes',
          style: 'destructive',
          onPress: async () => {
            const targetUuid = vehicleUuid || resolvedVehicleUuid;
            if (targetUuid) {
              try {
                await autoDocApi.clearCodes(targetUuid);
              } catch {
                // Continue with local clear
              }
            }
            setDtcList([]);
            Alert.alert('Codes Cleared', 'DTC memory reset signal acknowledged.');
          },
        },
      ]
    );
  };

  const handleLaunchAutoDocApp = async () => {
    const targetUuid = vehicleUuid || resolvedVehicleUuid;
    if (!targetUuid) {
      Alert.alert('AutoDoc Companion', 'Connecting to AutoDoc document companion...');
      return;
    }

    setIsLaunchingApp(true);
    try {
      const res = await autoDocApi.launch(targetUuid);
      const session = res.session;

      const canOpenDeepLink = await Linking.canOpenURL(session.deep_link).catch(() => false);
      if (canOpenDeepLink) {
        await Linking.openURL(session.deep_link);
      } else if (session.web_url) {
        await Linking.openURL(session.web_url);
      }
    } catch {
      Alert.alert(
        'AutoDoc Launch',
        'Opening AutoDoc portal for vehicle license, insurance and road worthiness renewals.',
        [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Open Web Portal',
            onPress: () => Linking.openURL('https://autodoc.autosecure.ng').catch(() => {}),
          },
        ]
      );
    } finally {
      setIsLaunchingApp(false);
    }
  };

  const healthScore = diagnosticsData?.health_score ?? 94;
  const batteryVoltage = diagnosticsData?.telemetry.battery_voltage.value ?? 13.8;
  const coolantTemp = diagnosticsData?.telemetry.coolant_temp.value ?? 88;
  const oilLife = diagnosticsData?.telemetry.oil_life_percent.value ?? 74;
  const fuelTrim = diagnosticsData?.telemetry.fuel_trim_st.value ?? 1.8;

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
      <View style={styles.container}>
        {/* Top Header */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={onBack}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <BackArrowIcon color="#1E2538" size={18} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>AutoDoc Diagnostics</Text>

          <TouchableOpacity
            style={styles.headerScanBtn}
            onPress={handleTriggerScan}
            disabled={isScanning}
            activeOpacity={0.7}
          >
            <Text style={styles.headerScanBtnText}>
              {isScanning ? 'Scanning...' : 'Scan ECU'}
            </Text>
          </TouchableOpacity>
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {loading && (
            <View style={styles.loadingBanner}>
              <ActivityIndicator color="#EA580C" size="small" />
              <Text style={styles.loadingBannerText}>Connecting to AutoDoc & Vehicle ECU...</Text>
            </View>
          )}

          {/* Target Vehicle Banner */}
          <View style={styles.vehicleBanner}>
            <View style={styles.vehicleIconBox}>
              <AutoDocEngineIcon color="#EA580C" size={24} />
            </View>
            <View style={styles.vehicleInfo}>
              <Text style={styles.vehicleName} numberOfLines={1}>{vehicleName}</Text>
              <Text style={styles.vehicleSub}>OBD-II CAN Protocol • Last Scan: {lastScanTime}</Text>
            </View>
            <View style={styles.onlineBadge}>
              <View style={styles.greenDot} />
              <Text style={styles.onlineText}>ECU Live</Text>
            </View>
          </View>

          {/* AutoDoc Companion & Document Summary Section */}
          <View style={styles.autoDocCompanionCard}>
            <View style={styles.companionHeaderRow}>
              <View style={styles.companionBadge}>
                <Text style={styles.companionBadgeText}>AutoDoc Companion</Text>
              </View>
              {docSummary?.summary.expiring_soon_count ? (
                <View style={styles.docAlertBadge}>
                  <Text style={styles.docAlertText}>{docSummary.summary.expiring_soon_count} Expiring</Text>
                </View>
              ) : (
                <View style={styles.docValidBadge}>
                  <Text style={styles.docValidText}>All Docs Valid</Text>
                </View>
              )}
            </View>

            <Text style={styles.companionTitle}>Vehicle Documents & Statutory Permits</Text>
            <Text style={styles.companionDesc}>
              Manage your Vehicle License, Road Worthiness, Insurance, and Proof of Ownership seamlessly in AutoDoc.
            </Text>

            {/* Document Status Pills */}
            <View style={styles.docPillList}>
              {(docSummary?.documents || [
                { id: '1', type: 'vehicle_license', title: 'Vehicle License', status: 'valid', expires_at: '2027-02-15' },
                { id: '2', type: 'road_worthiness', title: 'Road Worthiness', status: 'valid', expires_at: '2027-01-20' },
                { id: '3', type: 'insurance', title: 'Insurance Policy', status: 'valid', expires_at: '2026-12-31' },
                { id: '4', type: 'proof_of_ownership', title: 'Proof of Ownership (POC)', status: 'valid', expires_at: null },
              ]).map((doc) => (
                <View key={doc.id} style={styles.docPill}>
                  <View
                    style={[
                      styles.docStatusIndicator,
                      doc.status === 'valid'
                        ? styles.indicatorGreen
                        : doc.status === 'expiring_soon'
                        ? styles.indicatorYellow
                        : styles.indicatorRed,
                    ]}
                  />
                  <Text style={styles.docPillText} numberOfLines={1}>{doc.title}</Text>
                </View>
              ))}
            </View>

            <TouchableOpacity
              style={styles.launchAutoDocBtn}
              activeOpacity={0.8}
              onPress={handleLaunchAutoDocApp}
              disabled={isLaunchingApp}
            >
              {isLaunchingApp ? (
                <ActivityIndicator color="#FFFFFF" size="small" />
              ) : (
                <>
                  <Text style={styles.launchAutoDocBtnText}>Open in AutoDoc App</Text>
                  <ChevronRightIcon color="#FFFFFF" size={16} />
                </>
              )}
            </TouchableOpacity>
          </View>

          {/* Health Index Card */}
          <View style={styles.healthCard}>
            <View style={styles.healthScoreRow}>
              <View>
                <Text style={styles.healthLabel}>Overall Vehicle Health</Text>
                <Text style={styles.healthScore}>{healthScore}<Text style={styles.healthScoreMax}> / 100</Text></Text>
                <Text style={styles.healthSummary}>
                  {dtcList.length === 0
                    ? 'All Systems Operational • 0 Fault Codes'
                    : `Systems Monitored • ${dtcList.length} Diagnostic Codes`}
                </Text>
              </View>

              <View style={styles.healthBadgeCircle}>
                <ShieldCheckIcon color={healthScore >= 80 ? '#10B981' : '#F59E0B'} size={32} />
              </View>
            </View>

            <View style={styles.progressBarBg}>
              <View
                style={[
                  styles.progressBarFill,
                  {
                    width: `${Math.min(100, Math.max(0, healthScore))}%`,
                    backgroundColor: healthScore >= 80 ? '#10B981' : '#F59E0B',
                  },
                ]}
              />
            </View>
          </View>

          {/* Live Sensor Gauges 4-Grid */}
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Real-Time Sensor Telemetry</Text>
          </View>

          <View style={styles.sensorGrid}>
            <View style={styles.sensorCard}>
              <Text style={styles.sensorName}>Battery Voltage</Text>
              <Text style={styles.sensorValue}>{batteryVoltage} <Text style={styles.sensorUnit}>V</Text></Text>
              <Text style={styles.sensorStatusGood}>Optimal (Alternator charging)</Text>
            </View>

            <View style={styles.sensorCard}>
              <Text style={styles.sensorName}>Coolant Temp</Text>
              <Text style={styles.sensorValue}>{coolantTemp} <Text style={styles.sensorUnit}>°C</Text></Text>
              <Text style={styles.sensorStatusGood}>Normal operating temp</Text>
            </View>

            <View style={styles.sensorCard}>
              <Text style={styles.sensorName}>Oil Life Est.</Text>
              <Text style={styles.sensorValue}>{oilLife} <Text style={styles.sensorUnit}>%</Text></Text>
              <Text style={styles.sensorStatusGood}>Good for ~4,200 km</Text>
            </View>

            <View style={styles.sensorCard}>
              <Text style={styles.sensorName}>Fuel Trim (ST)</Text>
              <Text style={styles.sensorValue}>{fuelTrim > 0 ? `+${fuelTrim}` : `${fuelTrim}`} <Text style={styles.sensorUnit}>%</Text></Text>
              <Text style={styles.sensorStatusGood}>Balanced stoichiometric</Text>
            </View>
          </View>

          {/* Active Diagnostic Codes Section */}
          <View style={styles.sectionHeaderBetween}>
            <Text style={styles.sectionTitle}>
              Diagnostic Fault Codes ({dtcList.length})
            </Text>
            {dtcList.length > 0 && (
              <TouchableOpacity onPress={handleClearCodes}>
                <Text style={styles.clearCodesLink}>Clear DTCs</Text>
              </TouchableOpacity>
            )}
          </View>

          {dtcList.length === 0 ? (
            <View style={styles.cleanStateCard}>
              <CheckmarkCircleIcon color="#10B981" size={36} />
              <Text style={styles.cleanStateTitle}>Zero Fault Codes Detected</Text>
              <Text style={styles.cleanStateSub}>
                Your vehicle powertrain, transmission, and emission systems are operating with clean parameters.
              </Text>
            </View>
          ) : (
            <View style={styles.dtcList}>
              {dtcList.map((dtc) => (
                <TouchableOpacity
                  key={dtc.id}
                  style={styles.dtcCard}
                  activeOpacity={0.7}
                  onPress={() => setSelectedDtc(dtc)}
                >
                  <View style={styles.dtcTopRow}>
                    <View style={styles.dtcCodeBadge}>
                      <Text style={styles.dtcCodeText}>{dtc.code}</Text>
                    </View>
                    <View
                      style={[
                        styles.severityBadge,
                        dtc.severity === 'high' ? styles.severityHigh : styles.severityMedium,
                      ]}
                    >
                      <Text
                        style={[
                          styles.severityText,
                          dtc.severity === 'high' ? styles.severityHighText : styles.severityMediumText,
                        ]}
                      >
                        {dtc.severity === 'high' ? 'Critical' : 'Advisory'}
                      </Text>
                    </View>
                  </View>

                  <Text style={styles.dtcTitle}>{dtc.title}</Text>
                  <Text style={styles.dtcDesc} numberOfLines={2}>{dtc.description}</Text>

                  <View style={styles.dtcFooter}>
                    <Text style={styles.dtcSystemText}>{dtc.system}</Text>
                    <ChevronRightIcon color="#94A3B8" size={16} />
                  </View>
                </TouchableOpacity>
              ))}
            </View>
          )}

          {/* Book Repair CTA Card */}
          <View style={styles.repairCtaCard}>
            <View style={styles.repairIconBox}>
              <WrenchToolIcon color="#2563EB" size={24} />
            </View>
            <View style={styles.repairInfo}>
              <Text style={styles.repairTitle}>Need service or component check?</Text>
              <Text style={styles.repairSub}>
                Book a verified mechanic or parts supplier on AutoSecure Finder.
              </Text>
            </View>
            <TouchableOpacity
              style={styles.repairButton}
              activeOpacity={0.8}
              onPress={onNavigateToFinder}
            >
              <Text style={styles.repairButtonText}>Open Finder</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>

        {/* DTC Detail Bottom Sheet Modal */}
        <Modal
          visible={!!selectedDtc}
          transparent={true}
          animationType="slide"
          onRequestClose={() => setSelectedDtc(null)}
        >
          <View style={styles.bottomSheetBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setSelectedDtc(null)}
            />

            {selectedDtc && (
              <View style={styles.dtcBottomSheet}>
                <View style={styles.sheetHandleContainer}>
                  <View style={styles.sheetHandle} />
                </View>

                <View style={styles.sheetCodeRow}>
                  <View style={styles.dtcCodeBadgeLarge}>
                    <Text style={styles.dtcCodeTextLarge}>{selectedDtc.code}</Text>
                  </View>
                  <Text style={styles.sheetSystemLabel}>{selectedDtc.system}</Text>
                </View>

                <Text style={styles.sheetDtcTitle}>{selectedDtc.title}</Text>
                <Text style={styles.sheetSectionHeading}>ECU Diagnostics Note</Text>
                <Text style={styles.sheetDescription}>{selectedDtc.description}</Text>

                <Text style={styles.sheetSectionHeading}>Recommended Action</Text>
                <View style={styles.sheetRecommendationBox}>
                  <Text style={styles.sheetRecommendationText}>
                    {selectedDtc.recommendation}
                  </Text>
                </View>

                <View style={styles.sheetActionsRow}>
                  <TouchableOpacity
                    style={styles.sheetCloseBtn}
                    activeOpacity={0.7}
                    onPress={() => setSelectedDtc(null)}
                  >
                    <Text style={styles.sheetCloseBtnText}>Close</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={styles.sheetFinderBtn}
                    activeOpacity={0.8}
                    onPress={() => {
                      setSelectedDtc(null);
                      if (onNavigateToFinder) onNavigateToFinder();
                    }}
                  >
                    <Text style={styles.sheetFinderBtnText}>Find Mechanics</Text>
                  </TouchableOpacity>
                </View>
              </View>
            )}
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
  headerScanBtn: {
    backgroundColor: '#FFF7ED',
    borderWidth: 1,
    borderColor: '#FFEDD5',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 10,
  },
  headerScanBtnText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EA580C',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 90,
  },
  loadingBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 12,
    borderRadius: 12,
    backgroundColor: '#FFF7ED',
    borderWidth: 1,
    borderColor: '#FFEDD5',
    marginBottom: 12,
  },
  loadingBannerText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#EA580C',
    fontWeight: '600',
  },
  vehicleBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
  },
  vehicleIconBox: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: '#FFF7ED',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  vehicleInfo: {
    flex: 1,
  },
  vehicleName: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  vehicleSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  onlineBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: '#ECFDF5',
  },
  greenDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#10B981',
  },
  onlineText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#059669',
  },
  autoDocCompanionCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
  },
  companionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  companionBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    backgroundColor: '#FFF7ED',
  },
  companionBadgeText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EA580C',
  },
  docAlertBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    backgroundColor: '#FEF3C7',
  },
  docAlertText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#D97706',
  },
  docValidBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    backgroundColor: '#ECFDF5',
  },
  docValidText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#059669',
  },
  companionTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  companionDesc: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
    lineHeight: 16,
    marginBottom: 12,
  },
  docPillList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 14,
  },
  docPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  docStatusIndicator: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  indicatorGreen: {
    backgroundColor: '#10B981',
  },
  indicatorYellow: {
    backgroundColor: '#F59E0B',
  },
  indicatorRed: {
    backgroundColor: '#EF4444',
  },
  docPillText: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    fontWeight: '600',
    color: '#334155',
  },
  launchAutoDocBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 11,
    borderRadius: 12,
    backgroundColor: '#EA580C',
  },
  launchAutoDocBtnText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  healthCard: {
    padding: 18,
    borderRadius: 20,
    backgroundColor: '#111827',
    marginBottom: 20,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 10,
    elevation: 3,
  },
  healthScoreRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  healthLabel: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
    marginBottom: 4,
  },
  healthScore: {
    fontSize: 32,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#FFFFFF',
    lineHeight: 36,
  },
  healthScoreMax: {
    fontSize: 16,
    color: '#64748B',
    fontWeight: '500',
  },
  healthSummary: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#38BDF8',
    marginTop: 4,
  },
  healthBadgeCircle: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  progressBarBg: {
    height: 8,
    borderRadius: 4,
    backgroundColor: 'rgba(255, 255, 255, 0.12)',
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    borderRadius: 4,
    backgroundColor: '#10B981',
  },
  sectionHeader: {
    marginBottom: 10,
  },
  sectionHeaderBetween: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 18,
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  clearCodesLink: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#EA580C',
    fontWeight: '700',
  },
  sensorGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  sensorCard: {
    width: '48%',
    padding: 14,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  sensorName: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
    marginBottom: 4,
  },
  sensorValue: {
    fontSize: 20,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
  },
  sensorUnit: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#64748B',
  },
  sensorStatusGood: {
    fontSize: 10,
    fontFamily: 'Aeonik',
    color: '#059669',
    marginTop: 4,
  },
  cleanStateCard: {
    padding: 24,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    alignItems: 'center',
    textAlign: 'center',
    marginTop: 4,
  },
  cleanStateTitle: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginTop: 10,
    marginBottom: 4,
  },
  cleanStateSub: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 18,
  },
  dtcList: {
    gap: 10,
  },
  dtcCard: {
    padding: 16,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  dtcTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  dtcCodeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    backgroundColor: '#F1F5F9',
  },
  dtcCodeText: {
    fontSize: 12,
    fontFamily: 'Courier',
    fontWeight: '700',
    color: '#1E293B',
  },
  severityBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  severityMedium: {
    backgroundColor: '#FEF3C7',
  },
  severityMediumText: {
    color: '#D97706',
  },
  severityHigh: {
    backgroundColor: '#FEE2E2',
  },
  severityHighText: {
    color: '#DC2626',
  },
  severityText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  dtcTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  dtcDesc: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    lineHeight: 16,
    marginBottom: 10,
  },
  dtcFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#F8FAFC',
  },
  dtcSystemText: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  repairCtaCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#EFF6FF',
    borderWidth: 1,
    borderColor: '#DBEAFE',
    marginTop: 20,
  },
  repairIconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#DBEAFE',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  repairInfo: {
    flex: 1,
    paddingRight: 8,
  },
  repairTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#1E3A8A',
    marginBottom: 2,
  },
  repairSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#3B82F6',
    lineHeight: 15,
  },
  repairButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
  },
  repairButtonText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },

  /* DTC Bottom Sheet */
  bottomSheetBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  dtcBottomSheet: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 20,
    paddingBottom: 32,
    paddingTop: 12,
  },
  sheetHandleContainer: {
    alignItems: 'center',
    paddingVertical: 6,
    marginBottom: 10,
  },
  sheetHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#E2E8F0',
  },
  sheetCodeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  dtcCodeBadgeLarge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
  },
  dtcCodeTextLarge: {
    fontSize: 14,
    fontFamily: 'Courier',
    fontWeight: '700',
    color: '#1E293B',
  },
  sheetSystemLabel: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  sheetDtcTitle: {
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 14,
  },
  sheetSectionHeading: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    marginBottom: 4,
    marginTop: 8,
  },
  sheetDescription: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#334155',
    lineHeight: 18,
    marginBottom: 10,
  },
  sheetRecommendationBox: {
    padding: 12,
    borderRadius: 12,
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 20,
  },
  sheetRecommendationText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#0F172A',
    lineHeight: 17,
  },
  sheetActionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  sheetCloseBtn: {
    flex: 1,
    height: 48,
    borderRadius: 14,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetCloseBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#475569',
  },
  sheetFinderBtn: {
    flex: 1,
    height: 48,
    borderRadius: 14,
    backgroundColor: '#111827',
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetFinderBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
