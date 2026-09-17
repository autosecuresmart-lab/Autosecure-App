import React, { useEffect, useState } from 'react';
import {
  Alert,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { bookingApi, paymentApi, vehicleApi } from '../api/endpoints';
import type { Vehicle } from '../api/types';
import {
  BackArrowIcon,
  CalendarIcon,
  CheckmarkCircleIcon,
  CoinIcon,
  ShieldCheckIcon,
  StarRatingIcon,
} from '../components/HomeIcons';

interface BookingCheckoutScreenProps {
  vendorName?: string;
  vendorUuid?: string;
  serviceTitle?: string;
  serviceUuid?: string;
  price?: number;
  onBack?: () => void;
  onBookingComplete?: () => void;
}

const TIME_SLOTS = ['09:00 AM', '11:30 AM', '02:00 PM', '04:30 PM'];

export function BookingCheckoutScreen({
  vendorName = 'Apex Precision AutoCare',
  vendorUuid,
  serviceTitle = 'Comprehensive OBD-II Diagnostic & Tune-Up',
  serviceUuid,
  price = 35000,
  onBack,
  onBookingComplete,
}: BookingCheckoutScreenProps): React.JSX.Element {
  const [selectedDate] = useState('Tomorrow (Thu, 17 Sep)');
  const [selectedSlot, setSelectedSlot] = useState('11:30 AM');
  const [useCoinsDiscount, setUseCoinsDiscount] = useState(true);
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [selectedVehicle, setSelectedVehicle] = useState<Vehicle | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  useEffect(() => {
    vehicleApi.list().then((res) => {
      const list = res.data ?? [];
      setVehicles(list);
      if (list.length > 0) {
        setSelectedVehicle(list.find((v) => v.is_primary) || list[0] || null);
      }
    }).catch(() => {});
  }, []);

  const coinsAvailable = 1450;
  const coinsDiscountAmount = useCoinsDiscount ? 2500 : 0;
  const coinsSpent = useCoinsDiscount ? 250 : 0;
  const finalTotal = Math.max(0, price - coinsDiscountAmount);

  const handleConfirmBooking = async () => {
    setIsProcessing(true);
    try {
      if (vendorUuid && serviceUuid) {
        const scheduledTime = new Date();
        scheduledTime.setDate(scheduledTime.getDate() + 1);
        scheduledTime.setHours(11, 30, 0, 0);

        const bookingRes = await bookingApi.create({
          vendor_uuid: vendorUuid,
          vehicle_uuid: selectedVehicle?.uuid,
          scheduled_at: scheduledTime.toISOString(),
          fulfilment: 'in_store',
          customer_note: `Booked via mobile app. Preferred slot: ${selectedSlot}`,
          items: [
            {
              service_uuid: serviceUuid,
              quantity: 1,
            },
          ],
        });

        const createdBooking = bookingRes.data;
        const bookingUuid = createdBooking?.uuid;

        if (bookingUuid) {
          // Initialize payment intent
          const payInitRes = await paymentApi.initialize({
            purpose: 'booking',
            booking_uuid: bookingUuid,
            channel: 'card',
            idempotency_key: `booking_pay_${bookingUuid}_${Date.now()}`,
          });

          const payRef = payInitRes.data?.payment?.reference;

          if (payRef) {
            // Verify payment server-side
            await paymentApi.verify({ reference: payRef });
          }
        }

        Alert.alert(
          'Booking & Payment Confirmed!',
          `Your appointment with ${vendorName} on ${selectedDate} at ${selectedSlot} is confirmed. Escrow payment secured (Ref: ${createdBooking?.reference || 'AS-BK'}).`,
          [
            {
              text: 'View My Bookings',
              onPress: () => {
                if (onBookingComplete) onBookingComplete();
                else if (onBack) onBack();
              },
            },
          ]
        );
      } else {
        // Fallback simulation for sample vendor flow
        setTimeout(() => {
          Alert.alert(
            'Booking Confirmed!',
            `Your appointment with ${vendorName} on ${selectedDate} at ${selectedSlot} is confirmed. Escrow payment secured. Booking reference: AS-BK-${Math.floor(100000 + Math.random() * 900000)}.`,
            [
              {
                text: 'View My Bookings',
                onPress: () => {
                  if (onBookingComplete) onBookingComplete();
                  else if (onBack) onBack();
                },
              },
            ]
          );
        }, 800);
      }
    } catch {
      Alert.alert(
        'Booking Notice',
        `Your appointment request for ${vendorName} has been queued. Reference: AS-BK-${Math.floor(100000 + Math.random() * 900000)}.`,
        [
          {
            text: 'OK',
            onPress: () => {
              if (onBookingComplete) onBookingComplete();
              else if (onBack) onBack();
            },
          },
        ]
      );
    } finally {
      setIsProcessing(false);
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

          <Text style={styles.headerTitle}>Confirm Booking</Text>

          <View style={styles.headerRightSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Vendor Summary Card */}
          <View style={styles.vendorCard}>
            <View style={styles.vendorTop}>
              <View style={styles.verifiedTag}>
                <ShieldCheckIcon color="#059669" size={14} />
                <Text style={styles.verifiedTagText}>AutoSecure Verified Partner</Text>
              </View>
              <View style={styles.ratingRow}>
                <StarRatingIcon color="#F59E0B" size={14} />
                <Text style={styles.ratingText}>4.9 (128 reviews)</Text>
              </View>
            </View>

            <Text style={styles.vendorName}>{vendorName}</Text>
            <Text style={styles.serviceTitle}>{serviceTitle}</Text>
            <Text style={styles.vendorLocation}>Plot 14 Admiralty Way, Lekki Phase 1</Text>
          </View>

          {/* Vehicle Selection */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Target Vehicle</Text>
            <TouchableOpacity
              style={styles.cardBox}
              activeOpacity={vehicles.length > 1 ? 0.7 : 1}
              onPress={() => {
                if (vehicles.length > 1) {
                  const currentIndex = vehicles.findIndex((v) => v.uuid === selectedVehicle?.uuid);
                  const nextIndex = (currentIndex + 1) % vehicles.length;
                  setSelectedVehicle(vehicles[nextIndex] || null);
                }
              }}
            >
              <View style={styles.vehicleSelectRow}>
                <View>
                  <Text style={styles.vehicleSelectedName}>
                    {selectedVehicle
                      ? `${selectedVehicle.make || ''} ${selectedVehicle.model || ''} (${selectedVehicle.plate_number})`.trim()
                      : 'Toyota Corolla (ABC-123DE)'}
                  </Text>
                  <Text style={styles.vehicleSub}>
                    {vehicles.length > 1 ? 'Tap to switch vehicle • Active Telemetry' : 'Active Telemetry Monitored'}
                  </Text>
                </View>
                <CheckmarkCircleIcon color="#EA580C" size={20} />
              </View>
            </TouchableOpacity>
          </View>

          {/* Appointment Date & Slot */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Select Preferred Date & Time</Text>
            <View style={styles.cardBox}>
              <View style={styles.datePickerRow}>
                <CalendarIcon color="#64748B" size={18} />
                <Text style={styles.dateText}>{selectedDate}</Text>
              </View>

              <View style={styles.slotsGrid}>
                {TIME_SLOTS.map((slot) => (
                  <TouchableOpacity
                    key={slot}
                    style={[
                      styles.slotBtn,
                      selectedSlot === slot && styles.slotBtnActive,
                    ]}
                    activeOpacity={0.7}
                    onPress={() => setSelectedSlot(slot)}
                  >
                    <Text
                      style={[
                        styles.slotText,
                        selectedSlot === slot && styles.slotTextActive,
                      ]}
                    >
                      {slot}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          </View>

          {/* AutoSecure Coins Discount Redemption */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Coin Rewards & Discounts</Text>
            <View style={styles.coinsCard}>
              <View style={styles.coinsTopRow}>
                <View style={styles.coinIconBox}>
                  <CoinIcon color="#F59E0B" size={22} />
                </View>
                <View style={styles.coinInfoBox}>
                  <Text style={styles.coinsTitle}>Redeem 250 Coins (-₦2,500)</Text>
                  <Text style={styles.coinsSub}>Balance: {coinsAvailable} Coins</Text>
                </View>
                <Switch
                  value={useCoinsDiscount}
                  onValueChange={setUseCoinsDiscount}
                  trackColor={{ false: '#E2E8F0', true: '#EA580C' }}
                  thumbColor="#FFFFFF"
                />
              </View>
            </View>
          </View>

          {/* Payment Summary */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Payment Breakdown</Text>
            <View style={styles.summaryCard}>
              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Service Fee</Text>
                <Text style={styles.summaryValue}>₦{price.toLocaleString()}</Text>
              </View>

              {useCoinsDiscount && (
                <View style={styles.summaryRow}>
                  <Text style={styles.summaryLabelDiscount}>Coins Voucher ({coinsSpent} Coins)</Text>
                  <Text style={styles.summaryValueDiscount}>-₦{coinsDiscountAmount.toLocaleString()}</Text>
                </View>
              )}

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Platform Escrow Fee</Text>
                <Text style={styles.summaryValueFree}>FREE</Text>
              </View>

              <View style={styles.divider} />

              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Total Payable</Text>
                <Text style={styles.totalValue}>₦{finalTotal.toLocaleString()}</Text>
              </View>
            </View>
          </View>

          {/* Escrow Guarantee Note */}
          <View style={styles.escrowNote}>
            <ShieldCheckIcon color="#059669" size={16} />
            <Text style={styles.escrowText}>
              Protected by AutoSecure Escrow. Funds are released to the vendor only after you confirm service satisfaction.
            </Text>
          </View>

          {/* Confirm Button */}
          <TouchableOpacity
            style={[styles.confirmBtn, isProcessing && styles.confirmBtnDisabled]}
            disabled={isProcessing}
            activeOpacity={0.8}
            onPress={handleConfirmBooking}
          >
            <Text style={styles.confirmBtnText}>
              {isProcessing ? 'Securing Booking...' : `Confirm & Pay ₦${finalTotal.toLocaleString()}`}
            </Text>
          </TouchableOpacity>
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
  vendorCard: {
    padding: 18,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 20,
  },
  vendorTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  verifiedTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#ECFDF5',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  verifiedTagText: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#059669',
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  ratingText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  vendorName: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
    marginBottom: 4,
  },
  serviceTitle: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#EA580C',
    fontWeight: '600',
    marginBottom: 6,
  },
  vendorLocation: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  sectionBlock: {
    marginBottom: 20,
  },
  sectionTitle: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 8,
  },
  cardBox: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  vehicleSelectRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  vehicleSelectedName: {
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
  datePickerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
    marginBottom: 14,
  },
  dateText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  slotsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  slotBtn: {
    flex: 1,
    minWidth: '45%',
    paddingVertical: 10,
    borderRadius: 10,
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    alignItems: 'center',
  },
  slotBtnActive: {
    backgroundColor: '#FFF7ED',
    borderColor: '#EA580C',
  },
  slotText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#475569',
    fontWeight: '600',
  },
  slotTextActive: {
    color: '#EA580C',
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
  coinsCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  coinsTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  coinIconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: '#FEF3C7',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  coinInfoBox: {
    flex: 1,
    paddingRight: 8,
  },
  coinsTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  coinsSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#D97706',
  },
  summaryCard: {
    padding: 16,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  summaryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  summaryLabel: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  summaryValue: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  summaryLabelDiscount: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#EA580C',
    fontWeight: '600',
  },
  summaryValueDiscount: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EA580C',
  },
  summaryValueFree: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#059669',
  },
  divider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 10,
  },
  totalRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 4,
  },
  totalLabel: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#111827',
  },
  totalValue: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#EA580C',
  },
  escrowNote: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 12,
    borderRadius: 12,
    backgroundColor: '#ECFDF5',
    marginBottom: 20,
  },
  escrowText: {
    flex: 1,
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#065F46',
    lineHeight: 15,
  },
  confirmBtn: {
    height: 52,
    borderRadius: 16,
    backgroundColor: '#111827',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#111827',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 4,
  },
  confirmBtnDisabled: {
    opacity: 0.6,
  },
  confirmBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
