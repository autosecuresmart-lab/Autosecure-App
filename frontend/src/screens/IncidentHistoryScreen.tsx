import React, { useState } from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { BackArrowIcon, CarDeliveryIcon } from '../components/HomeIcons';

interface IncidentHistoryScreenProps {
  onBack?: () => void;
  onSelectIncident?: (incidentId: string) => void;
}

type HistoryFilter = 'All' | 'Active' | 'Closed';

interface IncidentHistoryRecord {
  id: string;
  vehicleName: string;
  date: string;
  status: 'Recovered' | 'Closed' | 'Cancelled' | 'Active';
}

const HISTORY_RECORDS: IncidentHistoryRecord[] = [
  { id: 'INC-2026-000245', vehicleName: 'Toyota Corolla', date: 'June 15, 2026', status: 'Recovered' },
  { id: 'INC-2026-000198', vehicleName: 'Toyota Camry', date: 'March 02, 2026', status: 'Closed' },
  { id: 'INC-2026-000156', vehicleName: 'Honda Accord', date: 'Jan 10, 2026', status: 'Cancelled' },
  { id: 'INC-2026-000112', vehicleName: 'Lexus RX350', date: 'Dec 20, 2025', status: 'Recovered' },
];

export function IncidentHistoryScreen({
  onBack,
  onSelectIncident,
}: IncidentHistoryScreenProps): React.JSX.Element {
  const [filter, setFilter] = useState<HistoryFilter>('Closed');

  const filteredRecords = HISTORY_RECORDS.filter((rec) => {
    if (filter === 'Active') return rec.status === 'Active';
    if (filter === 'Closed') return rec.status !== 'Active';
    return true;
  });

  const getStatusBadge = (status: IncidentHistoryRecord['status']) => {
    switch (status) {
      case 'Recovered':
        return (
          <View style={[styles.badge, { backgroundColor: '#ECFDF5' }]}>
            <Text style={[styles.badgeText, { color: '#10B981' }]}>Recovered</Text>
          </View>
        );
      case 'Closed':
        return (
          <View style={[styles.badge, { backgroundColor: '#F1F5F9' }]}>
            <Text style={[styles.badgeText, { color: '#64748B' }]}>Closed</Text>
          </View>
        );
      case 'Cancelled':
        return (
          <View style={[styles.badge, { backgroundColor: '#FEF2F2' }]}>
            <Text style={[styles.badgeText, { color: '#EF4444' }]}>Cancelled</Text>
          </View>
        );
      case 'Active':
        return (
          <View style={[styles.badge, { backgroundColor: '#FEE2E2' }]}>
            <Text style={[styles.badgeText, { color: '#DC2626' }]}>Active</Text>
          </View>
        );
    }
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right', 'bottom']}>
      <View style={styles.container}>
        {/* Header */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.backBtn}
            onPress={onBack}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <BackArrowIcon color="#111827" size={20} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Incident History</Text>

          <View style={styles.headerSpacer} />
        </View>

        {/* Segmented Filter Control */}
        <View style={styles.filterBar}>
          <View style={styles.segmentedBox}>
            {(['All', 'Active', 'Closed'] as HistoryFilter[]).map((tab) => {
              const isActive = filter === tab;
              return (
                <TouchableOpacity
                  key={tab}
                  style={[styles.segmentTab, isActive && styles.segmentTabActive]}
                  onPress={() => setFilter(tab)}
                  activeOpacity={0.8}
                >
                  <Text style={[styles.segmentText, isActive && styles.segmentTextActive]}>
                    {tab}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </View>
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.recordsList}>
            {filteredRecords.map((item) => (
              <TouchableOpacity
                key={item.id}
                style={styles.recordCard}
                activeOpacity={0.8}
                onPress={() => onSelectIncident && onSelectIncident(item.id)}
              >
                <View style={styles.carIconBox}>
                  <CarDeliveryIcon color="#64748B" size={20} />
                </View>

                <View style={styles.recordInfo}>
                  <Text style={styles.vehicleName}>{item.vehicleName}</Text>
                  <Text style={styles.recordMeta}>{item.id} • {item.date}</Text>
                </View>

                {getStatusBadge(item.status)}
              </TouchableOpacity>
            ))}
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
  },
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 10,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F0F2F5',
  },
  backBtn: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  headerSpacer: {
    width: 36,
  },
  filterBar: {
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  segmentedBox: {
    flexDirection: 'row',
    backgroundColor: '#F1F5F9',
    borderRadius: 12,
    padding: 3,
  },
  segmentTab: {
    flex: 1,
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 10,
  },
  segmentTabActive: {
    backgroundColor: '#FFFFFF',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 2,
    elevation: 2,
  },
  segmentText: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
  segmentTextActive: {
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  recordsList: {
    gap: 12,
  },
  recordCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#ECEFF3',
    borderRadius: 14,
    padding: 16,
    gap: 12,
  },
  carIconBox: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  recordInfo: {
    flex: 1,
  },
  vehicleName: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 3,
  },
  recordMeta: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 10,
  },
  badgeText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
});
