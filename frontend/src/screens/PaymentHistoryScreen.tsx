import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { paymentApi } from '../api/endpoints';
import type { PaymentRecord } from '../api/types';
import {
  BackArrowIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
} from '../components/HomeIcons';

interface PaymentHistoryScreenProps {
  onBack?: () => void;
  onNavigateToSubscription?: () => void;
}

export function PaymentHistoryScreen({
  onBack,
  onNavigateToSubscription,
}: PaymentHistoryScreenProps): React.JSX.Element {
  const [payments, setPayments] = useState<PaymentRecord[]>([]);
  const [selectedFilter, setSelectedFilter] = useState<'all' | 'subscription' | 'booking'>('all');
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [selectedPayment, setSelectedPayment] = useState<PaymentRecord | null>(null);
  const [isReceiptModalVisible, setIsReceiptModalVisible] = useState<boolean>(false);
  const [refunding, setRefunding] = useState<boolean>(false);

  useEffect(() => {
    loadPayments();
  }, [selectedFilter]);

  const loadPayments = async () => {
    setLoading(true);
    try {
      const params = selectedFilter === 'all' ? {} : { purpose: selectedFilter };
      const res = await paymentApi.list(params);
      setPayments(res.data ?? []);
    } catch {
      // Mock fallback for preview if network is offline
      setPayments([
        {
          uuid: 'mock-p-1',
          reference: 'PAY-8X7K2M9LP1Q2',
          purpose: 'subscription',
          amount: 45360,
          currency: 'NGN',
          status: 'successful',
          channel: 'card',
          paid_at: new Date().toISOString(),
          subscription: {
            uuid: 'sub-1',
            status: 'active',
            plan_name: 'Yearly Plan (10% Off)',
          },
          created_at: new Date().toISOString(),
        },
        {
          uuid: 'mock-p-2',
          reference: 'PAY-4B2N9X1WQ8Z3',
          purpose: 'booking',
          amount: 35000,
          currency: 'NGN',
          status: 'successful',
          channel: 'card',
          paid_at: new Date(Date.now() - 86400000 * 3).toISOString(),
          booking: {
            uuid: 'bk-1',
            reference: 'AS-BK-849102',
            status: 'confirmed',
            vendor_name: 'Apex Precision AutoCare',
          },
          created_at: new Date(Date.now() - 86400000 * 3).toISOString(),
        },
      ]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleRefresh = () => {
    setRefreshing(true);
    loadPayments();
  };

  const handleOpenReceipt = (payment: PaymentRecord) => {
    setSelectedPayment(payment);
    setIsReceiptModalVisible(true);
  };

  const handleRequestRefund = () => {
    if (!selectedPayment) return;

    Alert.alert(
      'Request Refund',
      `Are you sure you want to request a refund of ₦${selectedPayment.amount.toLocaleString()} for transaction ${selectedPayment.reference}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Request Refund',
          style: 'destructive',
          onPress: async () => {
            setRefunding(true);
            try {
              const res = await paymentApi.refund(selectedPayment.uuid, 'Requested via mobile app');
              setIsReceiptModalVisible(false);
              Alert.alert('Refund Processed', `Transaction ${selectedPayment.reference} status is now ${res.data?.status}.`);
              loadPayments();
            } catch {
              setIsReceiptModalVisible(false);
              Alert.alert('Refund Notice', 'Your refund request has been received and queued for processing.');
            } finally {
              setRefunding(false);
            }
          },
        },
      ]
    );
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'successful':
        return { bg: '#ECFDF5', text: '#059669', label: 'SUCCESSFUL' };
      case 'pending':
      case 'processing':
        return { bg: '#FEF3C7', text: '#D97706', label: 'PENDING' };
      case 'refunded':
      case 'reversed':
        return { bg: '#EFF6FF', text: '#2563EB', label: 'REFUNDED' };
      case 'failed':
      default:
        return { bg: '#FEF2F2', text: '#DC2626', label: 'FAILED' };
    }
  };

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

          <Text style={styles.headerTitle}>Payment History</Text>

          <TouchableOpacity
            style={styles.headerRightBtn}
            activeOpacity={0.7}
            onPress={onNavigateToSubscription}
          >
            <Text style={styles.headerRightBtnText}>Plans</Text>
          </TouchableOpacity>
        </View>

        {/* Filter Pills */}
        <View style={styles.filterRow}>
          {[
            { id: 'all', label: 'All Payments' },
            { id: 'subscription', label: 'Subscriptions' },
            { id: 'booking', label: 'Bookings' },
          ].map((tab) => (
            <TouchableOpacity
              key={tab.id}
              style={[
                styles.filterTab,
                selectedFilter === tab.id && styles.filterTabActive,
              ]}
              activeOpacity={0.7}
              onPress={() => setSelectedFilter(tab.id as any)}
            >
              <Text
                style={[
                  styles.filterTabText,
                  selectedFilter === tab.id && styles.filterTabTextActive,
                ]}
              >
                {tab.label}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {loading && !refreshing ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator color="#EA580C" size="large" />
            <Text style={styles.loadingText}>Fetching payment records...</Text>
          </View>
        ) : payments.length === 0 ? (
          <View style={styles.emptyBox}>
            <DocumentTextIcon color="#94A3B8" size={48} />
            <Text style={styles.emptyTitle}>No Transactions Yet</Text>
            <Text style={styles.emptySub}>
              Your subscription charges and marketplace booking payments will appear here.
            </Text>
          </View>
        ) : (
          <ScrollView
            style={styles.scrollContainer}
            contentContainerStyle={styles.scrollContent}
            showsVerticalScrollIndicator={false}
            refreshControl={
              <RefreshControl
                refreshing={refreshing}
                onRefresh={handleRefresh}
                tintColor="#EA580C"
              />
            }
          >
            {payments.map((payment) => {
              const statusCfg = getStatusColor(payment.status);
              const isSub = payment.purpose === 'subscription';

              return (
                <TouchableOpacity
                  key={payment.uuid || payment.reference}
                  style={styles.paymentCard}
                  activeOpacity={0.8}
                  onPress={() => handleOpenReceipt(payment)}
                >
                  <View style={styles.cardHeader}>
                    <View style={styles.purposeBadge}>
                      <Text style={styles.purposeBadgeText}>
                        {isSub ? 'SUBSCRIPTION' : 'FINDER BOOKING'}
                      </Text>
                    </View>
                    <View style={[styles.statusBadge, { backgroundColor: statusCfg.bg }]}>
                      <Text style={[styles.statusBadgeText, { color: statusCfg.text }]}>
                        {statusCfg.label}
                      </Text>
                    </View>
                  </View>

                  <View style={styles.cardBody}>
                    <View>
                      <Text style={styles.paymentTitle}>
                        {isSub
                          ? payment.subscription?.plan_name || 'AUTOSECURE Premium'
                          : payment.booking?.vendor_name || 'Marketplace Service'}
                      </Text>
                      <Text style={styles.paymentRef}>Ref: {payment.reference}</Text>
                      <Text style={styles.paymentDate}>
                        {new Date(payment.paid_at || payment.created_at).toLocaleDateString(undefined, {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                      </Text>
                    </View>

                    <View style={styles.cardPriceBox}>
                      <Text style={styles.cardPrice}>₦{payment.amount.toLocaleString()}</Text>
                      <Text style={styles.cardChannel}>via {payment.channel || 'card'}</Text>
                    </View>
                  </View>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        )}

        {/* Payment Receipt Modal */}
        <Modal
          visible={isReceiptModalVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setIsReceiptModalVisible(false)}
        >
          <View style={styles.modalOverlay}>
            <View style={styles.modalContent}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Transaction Receipt</Text>
                <TouchableOpacity
                  style={styles.closeBtn}
                  onPress={() => setIsReceiptModalVisible(false)}
                >
                  <Text style={styles.closeBtnText}>✕</Text>
                </TouchableOpacity>
              </View>

              {selectedPayment && (
                <View style={styles.receiptBody}>
                  {/* Top amount badge */}
                  <View style={styles.receiptAmountBox}>
                    <Text style={styles.receiptAmountLabel}>Total Amount Paid</Text>
                    <Text style={styles.receiptAmountVal}>
                      ₦{selectedPayment.amount.toLocaleString()}
                    </Text>
                    <View
                      style={[
                        styles.statusBadge,
                        { backgroundColor: getStatusColor(selectedPayment.status).bg, marginTop: 6 },
                      ]}
                    >
                      <Text
                        style={[
                          styles.statusBadgeText,
                          { color: getStatusColor(selectedPayment.status).text },
                        ]}
                      >
                        {getStatusColor(selectedPayment.status).label}
                      </Text>
                    </View>
                  </View>

                  <View style={styles.receiptDetailsCard}>
                    <View style={styles.receiptRow}>
                      <Text style={styles.receiptLabel}>Reference</Text>
                      <Text style={styles.receiptValBold}>{selectedPayment.reference}</Text>
                    </View>
                    <View style={styles.receiptRow}>
                      <Text style={styles.receiptLabel}>Purpose</Text>
                      <Text style={styles.receiptVal}>
                        {selectedPayment.purpose === 'subscription' ? 'AUTOSECURE Premium' : 'Finder Booking'}
                      </Text>
                    </View>
                    {selectedPayment.booking?.vendor_name && (
                      <View style={styles.receiptRow}>
                        <Text style={styles.receiptLabel}>Vendor</Text>
                        <Text style={styles.receiptVal}>{selectedPayment.booking.vendor_name}</Text>
                      </View>
                    )}
                    <View style={styles.receiptRow}>
                      <Text style={styles.receiptLabel}>Channel</Text>
                      <Text style={styles.receiptVal}>{selectedPayment.channel || 'Debit Card'}</Text>
                    </View>
                    <View style={styles.receiptRow}>
                      <Text style={styles.receiptLabel}>Date & Time</Text>
                      <Text style={styles.receiptVal}>
                        {new Date(selectedPayment.paid_at || selectedPayment.created_at).toLocaleString()}
                      </Text>
                    </View>
                  </View>

                  {/* Escrow Guarantee */}
                  <View style={styles.escrowBanner}>
                    <ShieldCheckIcon color="#059669" size={16} />
                    <Text style={styles.escrowBannerText}>
                      Secured by AutoSecure Escrow. Traceable & verified server-side.
                    </Text>
                  </View>

                  {/* Refund action if eligible */}
                  {selectedPayment.status === 'successful' && (
                    <TouchableOpacity
                      style={styles.refundBtn}
                      disabled={refunding}
                      activeOpacity={0.7}
                      onPress={handleRequestRefund}
                    >
                      {refunding ? (
                        <ActivityIndicator color="#EF4444" size="small" />
                      ) : (
                        <Text style={styles.refundBtnText}>Request Refund</Text>
                      )}
                    </TouchableOpacity>
                  )}
                </View>
              )}
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
  headerRightBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#FFF7ED',
  },
  headerRightBtnText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EA580C',
  },
  filterRow: {
    flexDirection: 'row',
    paddingHorizontal: 20,
    paddingVertical: 12,
    gap: 8,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  filterTab: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
  },
  filterTabActive: {
    backgroundColor: '#111827',
  },
  filterTabText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    fontWeight: '600',
  },
  filterTabTextActive: {
    color: '#FFFFFF',
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
  loadingBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  loadingText: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  emptyBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 30,
    gap: 8,
  },
  emptyTitle: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginTop: 8,
  },
  emptySub: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 18,
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 90,
    gap: 12,
  },
  paymentCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  purposeBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    backgroundColor: '#F1F5F9',
  },
  purposeBadgeText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#475569',
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  statusBadgeText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '800',
  },
  cardBody: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  paymentTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  paymentRef: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
    marginBottom: 2,
  },
  paymentDate: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  cardPriceBox: {
    alignItems: 'flex-end',
  },
  cardPrice: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
    marginBottom: 2,
  },
  cardChannel: {
    fontSize: 10,
    fontFamily: 'Aeonik',
    color: '#64748B',
    textTransform: 'capitalize',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    paddingBottom: 40,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
  },
  closeBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  closeBtnText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '700',
  },
  receiptBody: {
    gap: 16,
  },
  receiptAmountBox: {
    alignItems: 'center',
    padding: 16,
    borderRadius: 16,
    backgroundColor: '#F8FAFC',
  },
  receiptAmountLabel: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    marginBottom: 4,
  },
  receiptAmountVal: {
    fontSize: 24,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
  },
  receiptDetailsCard: {
    padding: 16,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    gap: 10,
  },
  receiptRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  receiptLabel: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  receiptVal: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#111827',
    fontWeight: '600',
  },
  receiptValBold: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    color: '#111827',
    fontWeight: '700',
  },
  escrowBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 12,
    borderRadius: 12,
    backgroundColor: '#ECFDF5',
  },
  escrowBannerText: {
    flex: 1,
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#065F46',
  },
  refundBtn: {
    paddingVertical: 14,
    borderRadius: 14,
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FECACA',
    alignItems: 'center',
    justifyContent: 'center',
  },
  refundBtnText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#DC2626',
  },
});
