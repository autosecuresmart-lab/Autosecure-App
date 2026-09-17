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
import Svg, { Circle, G } from 'react-native-svg';

import { careApi, vehicleApi } from '../api/endpoints';
import type { Vehicle, VehicleCareDashboard } from '../api/types';
import {
  BackArrowIcon,
  ChevronRightIcon,
  MaintenanceIcon,
  MoreHorizontalIcon,
} from '../components/HomeIcons';

interface AnalyticsScreenProps {
  onBack?: () => void;
  onNavigateToMaintenance?: () => void;
  onNavigateToVehicles?: () => void;
}

export function AnalyticsScreen({
  onBack,
  onNavigateToMaintenance,
  onNavigateToVehicles,
}: AnalyticsScreenProps): React.JSX.Element {
  const [loading, setLoading] = useState(true);
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [careDashboard, setCareDashboard] = useState<VehicleCareDashboard | null>(null);

  const loadData = useCallback(async () => {
    try {
      setLoading(true);
      const res = await vehicleApi.list();
      const list = res.data ?? [];
      setVehicles(list);

      if (list.length > 0) {
        const primary = list.find((v) => v.is_primary) || list[0];
        if (primary) {
          try {
            const careRes = await careApi.dashboard(primary.uuid);
            setCareDashboard(careRes);
          } catch {
            // If care dashboard is pending or empty, continue gracefully
          }
        }
      }
    } catch {
      // Offline or network error fallback
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const totalVehiclesCount = vehicles.length > 0 ? vehicles.length : 3;
  const activeCount = vehicles.filter((v) => v.status === 'active').length || totalVehiclesCount;
  const maintenanceCount = careDashboard?.metrics.total_services_count ?? 0;
  const remindersCount = careDashboard?.reminders_summary.total_outstanding ?? 0;

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

          <Text style={styles.headerTitle}>Analytics</Text>

          <TouchableOpacity
            style={styles.headerCircleBtn}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            onPress={() => Alert.alert('Analytics Options', 'Export analytics data or change time period.')}
          >
            <MoreHorizontalIcon color="#1E2538" size={18} />
          </TouchableOpacity>
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {loading && (
            <ActivityIndicator size="small" color="#2563EB" style={{ marginVertical: 8 }} />
          )}

          {/* Card 1: Total Devices */}
          <TouchableOpacity
            style={styles.metricCard}
            activeOpacity={0.8}
            onPress={onNavigateToVehicles}
          >
            <Text style={styles.metricLabel}>Total Devices</Text>
            <View style={styles.metricValueRow}>
              <Text style={styles.metricValue}>{totalVehiclesCount}</Text>
              <View style={styles.greenBadge}>
                <Text style={styles.greenBadgeText}>↑ 2%</Text>
              </View>
            </View>
          </TouchableOpacity>

          {/* Card 2: Online */}
          <TouchableOpacity
            style={styles.metricCard}
            activeOpacity={0.8}
            onPress={onNavigateToVehicles}
          >
            <Text style={styles.metricLabel}>Online</Text>
            <View style={styles.metricValueRow}>
              <Text style={styles.metricValue}>{activeCount}</Text>
              <View style={styles.greenBadge}>
                <Text style={styles.greenBadgeText}>↑ 9%</Text>
              </View>
            </View>
          </TouchableOpacity>

          {/* Card 3: Stopped */}
          <TouchableOpacity
            style={styles.metricCard}
            activeOpacity={0.8}
            onPress={onNavigateToVehicles}
          >
            <Text style={styles.metricLabel}>Stopped</Text>
            <View style={styles.metricValueRow}>
              <Text style={styles.metricValue}>0</Text>
              <View style={styles.redBadge}>
                <Text style={styles.redBadgeText}>0%</Text>
              </View>
            </View>
          </TouchableOpacity>

          {/* Card 4: Moving */}
          <TouchableOpacity
            style={styles.metricCard}
            activeOpacity={0.8}
            onPress={onNavigateToVehicles}
          >
            <Text style={styles.metricLabel}>Moving</Text>
            <View style={styles.metricValueRow}>
              <Text style={styles.metricValue}>1</Text>
              <View style={styles.greenBadge}>
                <Text style={styles.greenBadgeText}>Active</Text>
              </View>
            </View>
          </TouchableOpacity>


          {/* Card 5: Device Status Overview (Donut Chart) */}
          <View style={styles.overviewCard}>
            <Text style={styles.overviewCardTitle}>Device Status Overview</Text>

            {/* Donut Chart Container */}
            <View style={styles.donutWrapper}>
              <Svg width={180} height={180} viewBox="0 0 180 180">
                <G rotation="-90" origin="90, 90">
                  {/* Segment 1: Gray (Offline - 45% of circle) */}
                  <Circle
                    cx="90"
                    cy="90"
                    r="62"
                    stroke="#6B7280"
                    strokeWidth="16"
                    strokeDasharray="389.5"
                    strokeDashoffset="210"
                    fill="none"
                    strokeLinecap="round"
                  />

                  {/* Segment 2: Orange (Stopped - 15% of circle) */}
                  <Circle
                    cx="90"
                    cy="90"
                    r="62"
                    stroke="#F97316"
                    strokeWidth="16"
                    strokeDasharray="389.5"
                    strokeDashoffset="330"
                    fill="none"
                    strokeLinecap="round"
                  />

                  {/* Segment 3: Green (Online - 30% of circle) */}
                  <Circle
                    cx="90"
                    cy="90"
                    r="62"
                    stroke="#34D399"
                    strokeWidth="16"
                    strokeDasharray="389.5"
                    strokeDashoffset="270"
                    fill="none"
                    strokeLinecap="round"
                  />

                  {/* Segment 4: Blue (Moving - 10% of circle) */}
                  <Circle
                    cx="90"
                    cy="90"
                    r="62"
                    stroke="#2563EB"
                    strokeWidth="16"
                    strokeDasharray="389.5"
                    strokeDashoffset="350"
                    fill="none"
                    strokeLinecap="round"
                  />
                </G>
              </Svg>

              {/* Center Donut Label */}
              <View style={styles.donutCenterTextContainer}>
                <Text style={styles.donutPercent}>20%</Text>
                <Text style={styles.donutSubtitle}>Overview</Text>
              </View>
            </View>

            {/* 2x2 Legend */}
            <View style={styles.legendGrid}>
              <View style={styles.legendCol}>
                <View style={styles.legendItem}>
                  <View style={[styles.legendDot, { backgroundColor: '#2563EB' }]} />
                  <Text style={styles.legendLabel}>Moving</Text>
                  <Text style={styles.legendValue}>0%</Text>
                </View>

                <View style={styles.legendItem}>
                  <View style={[styles.legendDot, { backgroundColor: '#34D399' }]} />
                  <Text style={styles.legendLabel}>Online</Text>
                  <Text style={styles.legendValue}>9%</Text>
                </View>
              </View>

              <View style={styles.legendCol}>
                <View style={styles.legendItem}>
                  <View style={[styles.legendDot, { backgroundColor: '#F97316' }]} />
                  <Text style={styles.legendLabel}>Stopped</Text>
                  <Text style={styles.legendValue}>8%</Text>
                </View>

                <View style={styles.legendItem}>
                  <View style={[styles.legendDot, { backgroundColor: '#6B7280' }]} />
                  <Text style={styles.legendLabel}>Offline</Text>
                  <Text style={styles.legendValue}>2%</Text>
                </View>
              </View>
            </View>
          </View>

          {/* Card 6: Maintenance */}
          <TouchableOpacity
            style={styles.maintenanceCard}
            activeOpacity={0.8}
            onPress={() => {
              if (onNavigateToMaintenance) {
                onNavigateToMaintenance();
              } else {
                Alert.alert(
                  'Vehicle Care',
                  `Services Logged: ${maintenanceCount}\nActive Reminders: ${remindersCount}\nOdometer: ${careDashboard?.vehicle.odometer_km ? `${careDashboard.vehicle.odometer_km.toLocaleString()} km` : '45,000 km'}`,
                );
              }
            }}
          >
            <View style={styles.maintenanceIconBadge}>
              <MaintenanceIcon color="#FFFFFF" size={22} />
            </View>

            <View style={styles.maintenanceTextColumn}>
              <Text style={styles.maintenanceTitle}>
                {maintenanceCount > 0 ? `Maintenance (${maintenanceCount})` : 'Maintenance'}
              </Text>
              <Text style={styles.maintenanceSubtitle}>
                {remindersCount > 0 ? `${remindersCount} active service reminders` : 'Manage vehicle maintenance schedules'}
              </Text>
            </View>

            <ChevronRightIcon color="#94A3B8" size={18} />
          </TouchableOpacity>
        </ScrollView>
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
    backgroundColor: '#F5F7FA',
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 100, // Room for floating tab bar
    gap: 12,
  },
  metricCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    paddingHorizontal: 18,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#ECEFF3',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
  },
  metricLabel: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
    marginBottom: 4,
  },
  metricValueRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  metricValue: {
    fontSize: 26,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  greenBadge: {
    backgroundColor: '#ECFDF5',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  greenBadgeText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#10B981',
  },
  redBadge: {
    backgroundColor: '#FEF2F2',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  redBadgeText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EF4444',
  },
  /* Device Status Overview */
  overviewCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: '#ECEFF3',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
  },
  overviewCardTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 8,
  },
  donutWrapper: {
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    marginVertical: 10,
  },
  donutCenterTextContainer: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  donutPercent: {
    fontSize: 22,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  donutSubtitle: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#6B7280',
    marginTop: 2,
  },
  legendGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 16,
    paddingHorizontal: 10,
  },
  legendCol: {
    gap: 12,
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  legendDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  legendLabel: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#4B5563',
    minWidth: 50,
  },
  legendValue: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  /* Maintenance Card */
  maintenanceCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: '#ECEFF3',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
  },
  maintenanceIconBadge: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#10B981',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  maintenanceTextColumn: {
    flex: 1,
  },
  maintenanceTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  maintenanceSubtitle: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
});
