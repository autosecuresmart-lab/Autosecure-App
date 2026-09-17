import React from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { BackArrowIcon, CheckVerifiedIcon } from '../components/HomeIcons';

interface EventReportScreenProps {
  onBack?: () => void;
  onSelectEvent?: (eventId: string) => void;
}

export function EventReportScreen({
  onBack,
  onSelectEvent,
}: EventReportScreenProps): React.JSX.Element {
  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right', 'bottom']}>
      <View style={styles.container}>
        {/* Top Header */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={onBack}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <BackArrowIcon color="#1E2538" size={20} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Event Report</Text>

          <View style={styles.headerSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Section Header */}
          <Text style={styles.sectionHeader}>Today (1)</Text>

          {/* Full-width Flat List Item (No rounded card box) */}
          <TouchableOpacity
            style={styles.reportRow}
            activeOpacity={0.8}
            onPress={() => {
              if (onSelectEvent) {
                onSelectEvent('evt_1');
              }
            }}
          >
            <View style={styles.badgeWrapper}>
              <CheckVerifiedIcon color="#1E2538" size={24} />
            </View>

            <View style={styles.cardContent}>
              <View style={styles.cardTopRow}>
                <Text style={styles.cardTitle}>Event Report</Text>
                <View style={styles.timeWrapper}>
                  <Text style={styles.cardTime}>2:30 pm</Text>
                  <View style={styles.unreadDot} />
                </View>
              </View>

              <Text style={styles.cardDescription}>
                A concise summary of the event, highlighting key activities, outcomes, and notable moments.
              </Text>
            </View>
          </TouchableOpacity>
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
  headerSpacer: {
    width: 40,
    height: 40,
  },
  scrollContainer: {
    flex: 1,
    backgroundColor: '#F5F7FA',
  },
  scrollContent: {
    paddingTop: 16,
    paddingBottom: 40,
  },
  sectionHeader: {
    fontFamily: 'Helvetica',
    fontSize: 14.5,
    fontWeight: '700',
    color: '#1E2538',
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  reportRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderTopWidth: 1,
    borderTopColor: '#E8EDF5',
    borderBottomWidth: 1,
    borderBottomColor: '#E8EDF5',
    gap: 14,
  },
  badgeWrapper: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  cardContent: {
    flex: 1,
  },
  cardTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  cardTitle: {
    fontFamily: 'Helvetica',
    fontSize: 14,
    fontWeight: '700',
    color: '#1E2538',
  },
  timeWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  cardTime: {
    fontFamily: 'Aeonik',
    fontSize: 12,
    color: '#94A3B8',
  },
  unreadDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#2563EB',
  },
  cardDescription: {
    fontFamily: 'Aeonik',
    fontSize: 12,
    lineHeight: 17,
    color: '#64748B',
  },
});
