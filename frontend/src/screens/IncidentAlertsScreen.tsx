import React, { useState } from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  AlertTriangleIcon,
  BackArrowIcon,
  BellIcon,
  ChatBubbleIcon,
  CheckVerifiedIcon,
  LockOutlineIcon,
  PinIcon,
} from '../components/HomeIcons';

interface IncidentAlertsScreenProps {
  onBack?: () => void;
  onSelectAlert?: (alertId: string) => void;
}

type AlertFilter = 'All' | 'Unread' | 'Incident';

interface IncidentAlertItem {
  id: string;
  type: 'created' | 'assigned' | 'gps' | 'comment' | 'recovered' | 'closed';
  title: string;
  subtitle: string;
  time: string;
  unread: boolean;
}

const ALERTS_DATA: IncidentAlertItem[] = [
  {
    id: 'a1',
    type: 'created',
    title: 'Incident Created',
    subtitle: 'Theft report submitted successfully.',
    time: '10:42 AM',
    unread: true,
  },
  {
    id: 'a2',
    type: 'assigned',
    title: 'Recovery Team Assigned',
    subtitle: 'A recovery officer has been assigned.',
    time: '10:43 AM',
    unread: true,
  },
  {
    id: 'a3',
    type: 'gps',
    title: 'GPS Location Updated',
    subtitle: 'Last known location has been updated.',
    time: '10:48 AM',
    unread: true,
  },
  {
    id: 'a4',
    type: 'comment',
    title: 'Officer Comment',
    subtitle: 'Officer Michael left a comment on your incident.',
    time: '11:20 AM',
    unread: false,
  },
  {
    id: 'a5',
    type: 'recovered',
    title: 'Vehicle Recovered',
    subtitle: 'Great news! Your vehicle has been recovered.',
    time: 'Yesterday, 2:15 PM',
    unread: false,
  },
  {
    id: 'a6',
    type: 'closed',
    title: 'Incident Closed',
    subtitle: 'Your incident has been closed successfully.',
    time: 'Yesterday, 3:30 PM',
    unread: false,
  },
];

export function IncidentAlertsScreen({
  onBack,
  onSelectAlert,
}: IncidentAlertsScreenProps): React.JSX.Element {
  const [filter, setFilter] = useState<AlertFilter>('Unread');

  const filteredAlerts = ALERTS_DATA.filter((item) => {
    if (filter === 'Unread') return item.unread;
    if (filter === 'Incident') return item.type !== 'comment';
    return true;
  });

  const getAlertIcon = (type: IncidentAlertItem['type']) => {
    switch (type) {
      case 'created':
        return (
          <View style={[styles.iconCircle, { backgroundColor: '#FEE2E2' }]}>
            <BellIcon color="#EF4444" size={18} />
          </View>
        );
      case 'assigned':
        return (
          <View style={[styles.iconCircle, { backgroundColor: '#FEE2E2' }]}>
            <AlertTriangleIcon color="#DC2626" size={18} />
          </View>
        );
      case 'gps':
        return (
          <View style={[styles.iconCircle, { backgroundColor: '#F1F5F9' }]}>
            <PinIcon color="#64748B" size={18} />
          </View>
        );
      case 'comment':
        return (
          <View style={[styles.iconCircle, { backgroundColor: '#F1F5F9' }]}>
            <ChatBubbleIcon color="#64748B" size={18} />
          </View>
        );
      case 'recovered':
        return (
          <View style={[styles.iconCircle, { backgroundColor: '#ECFDF5' }]}>
            <CheckVerifiedIcon color="#10B981" size={18} />
          </View>
        );
      case 'closed':
        return (
          <View style={[styles.iconCircle, { backgroundColor: '#ECFDF5' }]}>
            <LockOutlineIcon color="#10B981" size={18} />
          </View>
        );
    }
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right', 'bottom']}>
      <View style={styles.container}>
        {/* Optional Header if onBack is provided */}
        {onBack && (
          <View style={styles.topBackRow}>
            <TouchableOpacity
              style={styles.backBtn}
              onPress={onBack}
              activeOpacity={0.7}
              hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            >
              <BackArrowIcon color="#111827" size={20} />
            </TouchableOpacity>
          </View>
        )}

        {/* Filter Segmented Bar */}
        <View style={styles.filterBar}>
          <TouchableOpacity
            style={[styles.filterTab, filter === 'All' && styles.filterTabActive]}
            onPress={() => setFilter('All')}
            activeOpacity={0.8}
          >
            <Text style={[styles.filterTabText, filter === 'All' && styles.filterTabTextActive]}>
              All
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.filterTab, filter === 'Unread' && styles.filterTabActive]}
            onPress={() => setFilter('Unread')}
            activeOpacity={0.8}
          >
            <View style={styles.unreadPillRow}>
              <Text style={[styles.filterTabText, filter === 'Unread' && styles.filterTabTextActive]}>
                Unread
              </Text>
              <View style={styles.unreadCountBadge}>
                <Text style={styles.unreadCountText}>3</Text>
              </View>
            </View>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.filterTab, filter === 'Incident' && styles.filterTabActive]}
            onPress={() => setFilter('Incident')}
            activeOpacity={0.8}
          >
            <Text style={[styles.filterTabText, filter === 'Incident' && styles.filterTabTextActive]}>
              Incident
            </Text>
          </TouchableOpacity>
        </View>

        {/* Alert Cards List */}
        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.alertsList}>
            {filteredAlerts.map((alert) => (
              <TouchableOpacity
                key={alert.id}
                style={styles.alertCard}
                activeOpacity={0.8}
                onPress={() => onSelectAlert && onSelectAlert(alert.id)}
              >
                {getAlertIcon(alert.type)}

                <View style={styles.alertContent}>
                  <View style={styles.alertTopRow}>
                    <Text style={styles.alertTitle}>{alert.title}</Text>
                    <Text style={styles.alertTime}>{alert.time}</Text>
                  </View>
                  <Text style={styles.alertSubtitle}>{alert.subtitle}</Text>
                </View>
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
    backgroundColor: '#F6F8FA',
  },
  container: {
    flex: 1,
    backgroundColor: '#F6F8FA',
  },
  topBackRow: {
    paddingHorizontal: 16,
    paddingTop: 6,
    paddingBottom: 2,
  },
  backBtn: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  filterBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  filterTab: {
    flex: 1,
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 20,
    marginHorizontal: 4,
  },
  filterTabActive: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  filterTabText: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#64748B',
    fontWeight: '500',
  },
  filterTabTextActive: {
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  unreadPillRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  unreadCountBadge: {
    backgroundColor: '#EF4444',
    paddingHorizontal: 6,
    paddingVertical: 1,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
  },
  unreadCountText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 36,
  },
  alertsList: {
    gap: 12,
  },
  alertCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderWidth: 1,
    borderColor: '#ECEFF3',
    gap: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 3,
    elevation: 1,
  },
  iconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  alertContent: {
    flex: 1,
  },
  alertTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  alertTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  alertTime: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  alertSubtitle: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
    lineHeight: 16,
  },
});
