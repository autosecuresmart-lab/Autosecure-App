import React, { useCallback, useEffect, useState } from 'react';
import {
  Modal,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { notificationApi } from '../api/endpoints';
import {
  AlertTriangleIcon,
  BackArrowIcon,
  CheckVerifiedIcon,
  CloseIcon,
  DeviceOfflineIcon,
  MoreHorizontalIcon,
  SleepingBellIllustration,
} from '../components/HomeIcons';

export interface NotificationItem {
  id: string;
  title: string;
  subtitle: string;
  time: string;
  section: 'Today' | 'Previous';
  type: 'online' | 'offline' | 'alert' | 'event';
  read: boolean;
  vehicleName?: string;
  location?: string;
}

const FALLBACK_NOTIFICATIONS: NotificationItem[] = [
  {
    id: 'notif_1',
    title: 'Geofence Exit Alert',
    subtitle: 'Toyota Corolla (ABC-123DE) exited the designated Lekki Phase 1 perimeter.',
    time: '2 hours ago',
    section: 'Today',
    type: 'alert',
    read: false,
    vehicleName: 'Toyota Corolla',
    location: 'Lekki Phase 1, Lagos',
  },
  {
    id: 'notif_2',
    title: 'Vehicle Online',
    subtitle: 'Toyota Camry (KJA-892XY) tracker connected to 4G LTE network.',
    time: '5 hours ago',
    section: 'Today',
    type: 'online',
    read: false,
    vehicleName: 'Toyota Camry',
    location: 'Victoria Island, Lagos',
  },
  {
    id: 'notif_3',
    title: 'Maintenance Due Soon',
    subtitle: 'Engine oil & filter change scheduled for Toyota Corolla in 14 days.',
    time: 'Yesterday',
    section: 'Previous',
    type: 'event',
    read: true,
    vehicleName: 'Toyota Corolla',
    location: 'Lagos, Nigeria',
  },
];

interface NotificationsScreenProps {
  onBack?: () => void;
  onSelectNotification?: (notification: NotificationItem) => void;
}

export function NotificationsScreen({
  onBack,
  onSelectNotification,
}: NotificationsScreenProps): React.JSX.Element {
  const [notifications, setNotifications] = useState<NotificationItem[]>(FALLBACK_NOTIFICATIONS);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);

  // Load real notifications from backend API
  const loadNotifications = useCallback(async () => {
    try {
      const response = await notificationApi.list();
      if (response && Array.isArray(response.data) && response.data.length > 0) {
        const mapped: NotificationItem[] = response.data.map((n, idx) => {
          const createdAt = n.created_at ? new Date(n.created_at) : new Date();
          const isToday = Date.now() - createdAt.getTime() < 24 * 60 * 60 * 1000;
          const timeStr = createdAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

          return {
            id: n.uuid || `notif_${idx}`,
            title: n.title,
            subtitle: n.body ?? '',
            time: timeStr,
            section: isToday ? 'Today' : 'Previous',
            type: (n.type === 'alert' || n.type === 'online' || n.type === 'offline' ? n.type : 'event'),
            read: !!n.read_at,
            vehicleName: (n.data as any)?.vehicle_name || 'Toyota Corolla',
            location: 'Lagos, Nigeria',
          };
        });
        setNotifications(mapped);
      }
    } catch {
      // Fallback
    }
  }, []);

  useEffect(() => {
    loadNotifications();
  }, [loadNotifications]);

  const onRefresh = async () => {
    setIsRefreshing(true);
    await loadNotifications();
    setIsRefreshing(false);
  };

  const todayNotifications = notifications.filter((n) => n.section === 'Today');
  const previousNotifications = notifications.filter((n) => n.section === 'Previous');
  const unreadTodayCount = todayNotifications.filter((n) => !n.read).length;

  const handleDeleteAll = async () => {
    try {
      await notificationApi.markAllAsRead();
    } catch {}
    setNotifications([]);
    setIsDeleteModalVisible(false);
  };

  const handleRestoreNotifications = () => {
    loadNotifications();
  };

  const handleItemPress = (item: NotificationItem) => {
    // Mark as read locally and on API
    if (item.id && !item.id.startsWith('notif_')) {
      notificationApi.markAsRead(item.id).catch(() => {});
    }

    setNotifications((prev) =>
      prev.map((n) => (n.id === item.id ? { ...n, read: true } : n))
    );
    if (onSelectNotification) {
      onSelectNotification(item);
    }
  };

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
            <BackArrowIcon color="#1E2538" size={18} />
          </TouchableOpacity>

          <Text style={styles.headerTitle}>Notifications</Text>

          <TouchableOpacity
            style={styles.headerCircleBtn}
            onPress={() => setIsDeleteModalVisible(true)}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          >
            <MoreHorizontalIcon color="#1E2538" size={18} />
          </TouchableOpacity>
        </View>

        {notifications.length === 0 ? (
          /* Empty State Screen */
          <View style={styles.emptyContainer}>
            <View style={styles.emptyContent}>
              <SleepingBellIllustration size={130} />
              <Text style={styles.emptyTitle}>NO NOTIFICATIONS</Text>
              <Text style={styles.emptySubtitle}>
                Clutter cleared! We'll notify you when there's an update.
              </Text>

              <TouchableOpacity
                style={styles.restoreBtn}
                activeOpacity={0.8}
                onPress={handleRestoreNotifications}
              >
                <Text style={styles.restoreBtnText}>Check for Notifications</Text>
              </TouchableOpacity>
            </View>
          </View>
        ) : (
          /* Active Notification List */
          <ScrollView
            style={styles.scrollContainer}
            contentContainerStyle={styles.scrollContent}
            showsVerticalScrollIndicator={false}
            refreshControl={
              <RefreshControl
                refreshing={isRefreshing}
                onRefresh={() => void onRefresh()}
                tintColor="#DE8635"
              />
            }
          >
            {/* Today Section */}
            {todayNotifications.length > 0 && (
              <View style={styles.sectionBlock}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Today</Text>
                  <Text style={styles.unreadCountText}>
                    {unreadTodayCount} Unread
                  </Text>
                </View>

                <View style={styles.listCard}>
                  {todayNotifications.map((item, index) => {
                    const isLast = index === todayNotifications.length - 1;
                    return (
                      <TouchableOpacity
                        key={item.id}
                        style={[styles.notificationRow, isLast && styles.rowNoBorder]}
                        activeOpacity={0.7}
                        onPress={() => handleItemPress(item)}
                      >
                        {/* Icon Badge */}
                        <View style={styles.iconContainer}>
                          {item.type === 'alert' ? (
                            <View style={[styles.iconBadge, styles.iconBadgeAlert]}>
                              <AlertTriangleIcon color="#EF4444" size={18} />
                            </View>
                          ) : item.type === 'offline' ? (
                            <View style={[styles.iconBadge, styles.iconBadgeOffline]}>
                              <DeviceOfflineIcon color="#EF4444" size={18} />
                            </View>
                          ) : (
                            <View style={[styles.iconBadge, styles.iconBadgeOnline]}>
                              <CheckVerifiedIcon color="#10B981" size={18} />
                            </View>
                          )}
                        </View>

                        {/* Content */}
                        <View style={styles.textContent}>
                          <View style={styles.titleRow}>
                            <Text style={[styles.itemTitle, !item.read && styles.itemTitleUnread]} numberOfLines={1}>
                              {item.title}
                            </Text>
                            <Text style={styles.timeText}>{item.time}</Text>
                          </View>
                          <Text style={styles.itemSubtitle} numberOfLines={2}>
                            {item.subtitle}
                          </Text>
                        </View>

                        {/* Unread dot */}
                        {!item.read && <View style={styles.unreadDot} />}
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>
            )}

            {/* Previous Section */}
            {previousNotifications.length > 0 && (
              <View style={styles.sectionBlock}>
                <View style={styles.sectionHeaderRow}>
                  <Text style={styles.sectionTitle}>Previous</Text>
                </View>

                <View style={styles.listCard}>
                  {previousNotifications.map((item, index) => {
                    const isLast = index === previousNotifications.length - 1;
                    return (
                      <TouchableOpacity
                        key={item.id}
                        style={[styles.notificationRow, isLast && styles.rowNoBorder]}
                        activeOpacity={0.7}
                        onPress={() => handleItemPress(item)}
                      >
                        <View style={styles.iconContainer}>
                          {item.type === 'alert' ? (
                            <View style={[styles.iconBadge, styles.iconBadgeAlert]}>
                              <AlertTriangleIcon color="#EF4444" size={18} />
                            </View>
                          ) : item.type === 'offline' ? (
                            <View style={[styles.iconBadge, styles.iconBadgeOffline]}>
                              <DeviceOfflineIcon color="#EF4444" size={18} />
                            </View>
                          ) : (
                            <View style={[styles.iconBadge, styles.iconBadgeOnline]}>
                              <CheckVerifiedIcon color="#10B981" size={18} />
                            </View>
                          )}
                        </View>

                        <View style={styles.textContent}>
                          <View style={styles.titleRow}>
                            <Text style={[styles.itemTitle, !item.read && styles.itemTitleUnread]} numberOfLines={1}>
                              {item.title}
                            </Text>
                            <Text style={styles.timeText}>{item.time}</Text>
                          </View>
                          <Text style={styles.itemSubtitle} numberOfLines={2}>
                            {item.subtitle}
                          </Text>
                        </View>

                        {!item.read && <View style={styles.unreadDot} />}
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </View>
            )}
          </ScrollView>
        )}

        {/* Delete All Modal */}
        <Modal
          visible={isDeleteModalVisible}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setIsDeleteModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsDeleteModalVisible(false)}
            />
            <View style={styles.modalCard}>
              <View style={styles.modalHeaderRow}>
                <Text style={styles.modalTitle}>Notification Options</Text>
                <TouchableOpacity onPress={() => setIsDeleteModalVisible(false)}>
                  <CloseIcon color="#64748B" size={20} />
                </TouchableOpacity>
              </View>

              <Text style={styles.modalBodyText}>
                Would you like to clear all notifications or mark everything as read?
              </Text>

              <View style={styles.modalActions}>
                <TouchableOpacity
                  style={styles.modalClearBtn}
                  activeOpacity={0.8}
                  onPress={handleDeleteAll}
                >
                  <Text style={styles.modalClearBtnText}>Clear All</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.modalCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsDeleteModalVisible(false)}
                >
                  <Text style={styles.modalCancelBtnText}>Cancel</Text>
                </TouchableOpacity>
              </View>
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
    backgroundColor: '#F8FAFC',
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
  },
  headerCircleBtn: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  headerTitle: {
    fontFamily: 'Helvetica',
    fontSize: 17,
    fontWeight: '700',
    color: '#1E2538',
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 40,
    gap: 20,
  },
  sectionBlock: {
    gap: 8,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 4,
  },
  sectionTitle: {
    fontFamily: 'Helvetica',
    fontSize: 15,
    fontWeight: '700',
    color: '#1E2538',
  },
  unreadCountText: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    color: '#94A3B8',
    fontWeight: '500',
  },
  listCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
  },
  notificationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F8FAFC',
    gap: 12,
  },
  rowNoBorder: {
    borderBottomWidth: 0,
  },
  iconContainer: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconBadge: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconBadgeOnline: {
    backgroundColor: '#ECFDF5',
  },
  iconBadgeOffline: {
    backgroundColor: '#FEF2F2',
  },
  iconBadgeAlert: {
    backgroundColor: '#FEF2F2',
  },
  textContent: {
    flex: 1,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 3,
  },
  itemTitle: {
    fontFamily: 'Helvetica',
    fontSize: 14,
    color: '#64748B',
    fontWeight: '600',
    flex: 1,
    marginRight: 8,
  },
  itemTitleUnread: {
    color: '#1E2538',
    fontWeight: '700',
  },
  timeText: {
    fontFamily: 'Helvetica',
    fontSize: 11,
    color: '#94A3B8',
  },
  itemSubtitle: {
    fontFamily: 'Helvetica',
    fontSize: 12,
    color: '#64748B',
    lineHeight: 16,
  },
  unreadDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#DE8635',
  },
  /* Empty state */
  emptyContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  emptyContent: {
    alignItems: 'center',
  },
  emptyTitle: {
    fontFamily: 'Helvetica',
    fontSize: 18,
    fontWeight: '800',
    color: '#1E2538',
    marginTop: 20,
    letterSpacing: 1,
  },
  emptySubtitle: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    color: '#64748B',
    textAlign: 'center',
    marginTop: 8,
    lineHeight: 18,
    maxWidth: 260,
  },
  restoreBtn: {
    marginTop: 24,
    backgroundColor: '#1E2538',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 12,
  },
  restoreBtnText: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  /* Modal */
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 24,
  },
  modalDismissArea: {
    ...StyleSheet.absoluteFill,
  },
  modalCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 24,
    width: '100%',
    maxWidth: 360,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
    elevation: 10,
  },
  modalHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  modalTitle: {
    fontFamily: 'Helvetica',
    fontSize: 17,
    fontWeight: '700',
    color: '#1E2538',
  },
  modalBodyText: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    color: '#64748B',
    lineHeight: 18,
    marginBottom: 20,
  },
  modalActions: {
    gap: 10,
  },
  modalClearBtn: {
    backgroundColor: '#EF4444',
    paddingVertical: 13,
    borderRadius: 12,
    alignItems: 'center',
  },
  modalClearBtnText: {
    fontFamily: 'Helvetica',
    fontSize: 14,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  modalCancelBtn: {
    backgroundColor: '#F1F5F9',
    paddingVertical: 12,
    borderRadius: 12,
    alignItems: 'center',
  },
  modalCancelBtnText: {
    fontFamily: 'Helvetica',
    fontSize: 13,
    fontWeight: '600',
    color: '#64748B',
  },
});
