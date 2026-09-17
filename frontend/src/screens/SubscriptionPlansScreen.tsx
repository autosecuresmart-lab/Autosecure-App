import React, { useEffect, useState } from 'react';
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

import { paymentApi, subscriptionApi } from '../api/endpoints';
import type { SubscriptionPlan } from '../api/types';
import {
  BackArrowIcon,
  CheckmarkCircleIcon,
  LockSecurityIcon,
  ShieldCheckIcon,
  StarRatingIcon,
} from '../components/HomeIcons';

interface SubscriptionPlansScreenProps {
  onBack?: () => void;
  onNavigateToPaymentHistory?: () => void;
}

export function SubscriptionPlansScreen({
  onBack,
  onNavigateToPaymentHistory,
}: SubscriptionPlansScreenProps): React.JSX.Element {
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [selectedPlan, setSelectedPlan] = useState<SubscriptionPlan | null>(null);
  const [endsAt, setEndsAt] = useState<string | null>(null);
  const [isPremium, setIsPremium] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(true);
  const [processing, setProcessing] = useState<boolean>(false);
  const [selectedChannel, setSelectedChannel] = useState<'card' | 'bank_transfer' | 'ussd'>('card');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      const [plansRes, meRes] = await Promise.all([
        subscriptionApi.plans(),
        subscriptionApi.me(),
      ]);

      const paidPlans = (plansRes.plans ?? []).filter((p: SubscriptionPlan) => !p.is_free);
      setPlans(paidPlans);

      if (paidPlans.length > 0) {
        // Default to yearly (best value) or monthly
        const defaultPlan = paidPlans.find((p: SubscriptionPlan) => p.slug === 'yearly') || paidPlans[0];
        setSelectedPlan(defaultPlan || null);
      }

      const sub = meRes.subscription;
      if (sub) {
        setIsPremium(Boolean(sub.is_premium));
        setEndsAt(sub.ends_at || null);
      }
    } catch {
      // Offline / fallback defaults
      setPlans([
        {
          uuid: 'mock-monthly',
          name: 'Monthly',
          slug: 'monthly',
          description: 'Full vehicle care and ownership features, billed monthly.',
          price: 4200,
          base_price: 4200,
          currency: 'NGN',
          interval: 'monthly',
          duration_days: 30,
          discount_percent: 0,
          is_free: false,
          features: ['care.oil_change', 'care.service_history', 'care.mileage_insights', 'autodoc.connect'],
        },
        {
          uuid: 'mock-half-yearly',
          name: 'Half-Year',
          slug: 'half-yearly',
          description: 'Six months of Premium with a 5% discount.',
          price: 23940,
          base_price: 25200,
          currency: 'NGN',
          interval: 'half_yearly',
          duration_days: 182,
          discount_percent: 5,
          is_free: false,
          features: ['care.oil_change', 'care.service_history', 'care.mileage_insights', 'autodoc.connect'],
        },
        {
          uuid: 'mock-yearly',
          name: 'Yearly',
          slug: 'yearly',
          description: 'Twelve months of Premium with a 10% discount.',
          price: 45360,
          base_price: 50400,
          currency: 'NGN',
          interval: 'yearly',
          duration_days: 365,
          discount_percent: 10,
          is_free: false,
          features: ['care.oil_change', 'care.service_history', 'care.mileage_insights', 'autodoc.connect'],
        },
      ]);
    } finally {
      setLoading(false);
    }
  };

  const handleSubscribe = async () => {
    if (!selectedPlan) return;

    setProcessing(true);
    try {
      const initRes = await paymentApi.initialize({
        purpose: 'subscription',
        plan_uuid: selectedPlan.uuid,
        channel: selectedChannel,
        idempotency_key: `sub_${selectedPlan.uuid}_${Date.now()}`,
      });

      const reference = initRes.data?.payment?.reference;

      if (!reference) {
        throw new Error('Failed to initialize payment reference');
      }

      // Verify payment with server
      const verifyRes = await paymentApi.verify({ reference });

      if (verifyRes.data?.status === 'successful') {
        Alert.alert(
          'Subscription Activated!',
          `You have successfully subscribed to AUTOSECURE ${selectedPlan.name} plan. All premium vehicle care insights are now unlocked.`,
          [
            {
              text: 'Great!',
              onPress: () => {
                loadData();
              },
            },
          ]
        );
      } else {
        Alert.alert('Payment Notice', 'Your payment is being processed. Reference: ' + reference);
      }
    } catch {
      Alert.alert(
        'Subscription Simulation',
        `Payment intent for ₦${selectedPlan.price.toLocaleString()} was simulated. In live environment, this directs to Paystack checkout.`,
        [{ text: 'OK', onPress: () => loadData() }]
      );
    } finally {
      setProcessing(false);
    }
  };

  const handleCancelAutoRenew = () => {
    Alert.alert(
      'Cancel Auto-Renew',
      'Are you sure you want to disable automatic renewal? You will retain all premium features until the end of your current billing period.',
      [
        { text: 'Keep Auto-Renew', style: 'cancel' },
        {
          text: 'Disable Renewal',
          style: 'destructive',
          onPress: async () => {
            try {
              await subscriptionApi.cancel('Customer disabled in app');
              Alert.alert('Renewal Cancelled', 'Auto-renew has been turned off.');
              loadData();
            } catch {
              Alert.alert('Notice', 'Unable to cancel at this moment.');
            }
          },
        },
      ]
    );
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

          <Text style={styles.headerTitle}>AUTOSECURE Premium</Text>

          <TouchableOpacity
            style={styles.historyBtn}
            activeOpacity={0.7}
            onPress={onNavigateToPaymentHistory}
          >
            <Text style={styles.historyBtnText}>Receipts</Text>
          </TouchableOpacity>
        </View>

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator color="#EA580C" size="large" />
            <Text style={styles.loadingText}>Loading subscription plans...</Text>
          </View>
        ) : (
          <ScrollView
            style={styles.scrollContainer}
            contentContainerStyle={styles.scrollContent}
            showsVerticalScrollIndicator={false}
          >
            {/* Status Card */}
            <View style={styles.statusCard}>
              <View style={styles.statusTop}>
                <View style={styles.statusBadgeRow}>
                  <ShieldCheckIcon color={isPremium ? '#059669' : '#EA580C'} size={16} />
                  <Text style={[styles.statusBadgeText, !isPremium && styles.statusBadgeTextFree]}>
                    {isPremium ? 'PREMIUM ACTIVE' : 'FREE CORE TIER'}
                  </Text>
                </View>
                {isPremium && (
                  <TouchableOpacity activeOpacity={0.7} onPress={handleCancelAutoRenew}>
                    <Text style={styles.cancelText}>Cancel Auto-Renew</Text>
                  </TouchableOpacity>
                )}
              </View>

              <Text style={styles.statusTitle}>
                {isPremium ? 'All Pro Features Unlocked' : 'Upgrade to AUTOSECURE Premium'}
              </Text>
              <Text style={styles.statusSub}>
                {endsAt
                  ? `Your current coverage is active until ${new Date(endsAt).toLocaleDateString()}. Core GPS tracker and dashcam security always remain free forever.`
                  : 'Core GPS tracker and live dashcam streaming are always free forever. Premium unlocks predictive maintenance, service records and AutoDoc telemetry sync.'}
              </Text>
            </View>

            {/* Plans List */}
            <Text style={styles.sectionHeader}>Choose Your Billing Cycle</Text>
            <View style={styles.plansContainer}>
              {plans.map((plan) => {
                const isSelected = selectedPlan?.uuid === plan.uuid;
                const isYearly = plan.slug === 'yearly';
                const isHalfYear = plan.slug === 'half-yearly';

                return (
                  <TouchableOpacity
                    key={plan.uuid || plan.slug}
                    style={[styles.planCard, isSelected && styles.planCardSelected]}
                    activeOpacity={0.8}
                    onPress={() => setSelectedPlan(plan)}
                  >
                    {isYearly && (
                      <View style={styles.discountPill}>
                        <StarRatingIcon color="#FFFFFF" size={10} />
                        <Text style={styles.discountPillText}>BEST VALUE • 10% OFF</Text>
                      </View>
                    )}
                    {isHalfYear && (
                      <View style={[styles.discountPill, styles.discountPillGreen]}>
                        <Text style={styles.discountPillText}>SAVE 5%</Text>
                      </View>
                    )}

                    <View style={styles.planHeader}>
                      <View>
                        <Text style={styles.planName}>{plan.name}</Text>
                        <Text style={styles.planDuration}>{plan.duration_days} Days Access</Text>
                      </View>
                      <View style={styles.priceColumn}>
                        <Text style={styles.planPrice}>₦{plan.price.toLocaleString()}</Text>
                        {plan.base_price && plan.base_price > plan.price && (
                          <Text style={styles.planBasePrice}>₦{plan.base_price.toLocaleString()}</Text>
                        )}
                      </View>
                    </View>

                    <Text style={styles.planDescription}>{plan.description}</Text>

                    <View style={styles.planFooter}>
                      <View style={styles.radioRow}>
                        <View style={[styles.radioCircle, isSelected && styles.radioCircleSelected]}>
                          {isSelected && <View style={styles.radioInner} />}
                        </View>
                        <Text style={[styles.radioText, isSelected && styles.radioTextSelected]}>
                          {isSelected ? 'Selected Plan' : 'Select Plan'}
                        </Text>
                      </View>
                    </View>
                  </TouchableOpacity>
                );
              })}
            </View>

            {/* Payment Method Selector */}
            <Text style={styles.sectionHeader}>Payment Channel</Text>
            <View style={styles.channelRow}>
              {[
                { id: 'card', label: 'Debit Card' },
                { id: 'bank_transfer', label: 'Bank Transfer' },
                { id: 'ussd', label: 'USSD' },
              ].map((channel) => (
                <TouchableOpacity
                  key={channel.id}
                  style={[
                    styles.channelBtn,
                    selectedChannel === channel.id && styles.channelBtnActive,
                  ]}
                  activeOpacity={0.7}
                  onPress={() => setSelectedChannel(channel.id as any)}
                >
                  <Text
                    style={[
                      styles.channelBtnText,
                      selectedChannel === channel.id && styles.channelBtnTextActive,
                    ]}
                  >
                    {channel.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            {/* Feature Entitlements Breakdown */}
            <Text style={styles.sectionHeader}>What's Included</Text>
            <View style={styles.featuresCard}>
              {[
                { title: 'Core GPS Vehicle Tracker', sub: 'Live location, trip playback, engine control', free: true },
                { title: 'Dashcam Streaming & Events', sub: 'Front/rear live video, incident recordings', free: true },
                { title: 'Vehicle Care & Service History', sub: 'Odometer, oil, brake and battery logs', free: false },
                { title: 'Predictive Service Reminders', sub: 'Date & mileage automated service alerts', free: false },
                { title: 'AutoDoc Digital Garage Sync', sub: 'Vehicle document & renewal management', free: false },
                { title: 'Escrow Booking Protection', sub: 'Secure payments released on satisfaction', free: true },
              ].map((item, idx) => (
                <View key={idx} style={styles.featureItem}>
                  <CheckmarkCircleIcon color={item.free ? '#059669' : '#EA580C'} size={18} />
                  <View style={styles.featureInfo}>
                    <Text style={styles.featureTitle}>{item.title}</Text>
                    <Text style={styles.featureSub}>{item.sub}</Text>
                  </View>
                  <View style={[styles.tierTag, item.free ? styles.tierTagFree : styles.tierTagPro]}>
                    <Text style={[styles.tierTagText, item.free ? styles.tierTagFreeText : styles.tierTagProText]}>
                      {item.free ? 'FREE' : 'PRO'}
                    </Text>
                  </View>
                </View>
              ))}
            </View>

            {/* Action Button */}
            <TouchableOpacity
              style={[styles.checkoutBtn, processing && styles.checkoutBtnDisabled]}
              disabled={processing || !selectedPlan}
              activeOpacity={0.85}
              onPress={handleSubscribe}
            >
              {processing ? (
                <ActivityIndicator color="#FFFFFF" size="small" />
              ) : (
                <Text style={styles.checkoutBtnText}>
                  {isPremium ? 'Renew / Switch Plan' : `Subscribe • ₦${(selectedPlan?.price ?? 0).toLocaleString()}`}
                </Text>
              )}
            </TouchableOpacity>

            <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, marginTop: 14 }}>
              <LockSecurityIcon color="#94A3B8" size={13} />
              <Text style={styles.securityNote}>
                Protected by 256-bit server-side encryption. No card details stored.
              </Text>
            </View>
          </ScrollView>
        )}
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
  historyBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
  },
  historyBtnText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#334155',
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
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 90,
  },
  statusCard: {
    padding: 18,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 24,
  },
  statusTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  statusBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
    backgroundColor: '#ECFDF5',
  },
  statusBadgeText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#059669',
  },
  statusBadgeTextFree: {
    color: '#EA580C',
  },
  cancelText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#EF4444',
  },
  statusTitle: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
    marginBottom: 4,
  },
  statusSub: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
    lineHeight: 18,
  },
  sectionHeader: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 12,
  },
  plansContainer: {
    gap: 12,
    marginBottom: 24,
  },
  planCard: {
    padding: 18,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    position: 'relative',
  },
  planCardSelected: {
    borderColor: '#EA580C',
    backgroundColor: '#FFFAF5',
  },
  discountPill: {
    position: 'absolute',
    top: -10,
    right: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#EA580C',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 10,
  },
  discountPillGreen: {
    backgroundColor: '#059669',
  },
  discountPillText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#FFFFFF',
  },
  planHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  planName: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
  },
  planDuration: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  priceColumn: {
    alignItems: 'flex-end',
  },
  planPrice: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#EA580C',
  },
  planBasePrice: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
    textDecorationLine: 'line-through',
  },
  planDescription: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#475569',
    marginBottom: 14,
  },
  planFooter: {
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 10,
  },
  radioRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  radioCircle: {
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  radioCircleSelected: {
    borderColor: '#EA580C',
  },
  radioInner: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#EA580C',
  },
  radioText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#64748B',
  },
  radioTextSelected: {
    color: '#EA580C',
    fontWeight: '700',
  },
  channelRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 24,
  },
  channelBtn: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 12,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    alignItems: 'center',
  },
  channelBtnActive: {
    backgroundColor: '#FFF7ED',
    borderColor: '#EA580C',
  },
  channelBtnText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#475569',
    fontWeight: '600',
  },
  channelBtnTextActive: {
    color: '#EA580C',
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
  featuresCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 24,
    gap: 12,
  },
  featureItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  featureInfo: {
    flex: 1,
  },
  featureTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  featureSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  tierTag: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  tierTagFree: {
    backgroundColor: '#F1F5F9',
  },
  tierTagPro: {
    backgroundColor: '#FFF7ED',
  },
  tierTagText: {
    fontSize: 9,
    fontFamily: 'Helvetica',
    fontWeight: '800',
  },
  tierTagFreeText: {
    color: '#64748B',
  },
  tierTagProText: {
    color: '#EA580C',
  },
  checkoutBtn: {
    height: 54,
    borderRadius: 16,
    backgroundColor: '#111827',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#111827',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 4,
    marginBottom: 12,
  },
  checkoutBtnDisabled: {
    opacity: 0.6,
  },
  checkoutBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#FFFFFF',
  },
  securityNote: {
    textAlign: 'center',
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
});
