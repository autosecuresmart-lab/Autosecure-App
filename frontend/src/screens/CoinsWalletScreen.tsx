import React, { useState } from 'react';
import {
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  BackArrowIcon,
  CoinIcon,
  InviteFriendsIcon,
  ShieldCheckIcon,
  WrenchToolIcon,
} from '../components/HomeIcons';

interface CoinTransaction {
  id: string;
  title: string;
  date: string;
  amount: number;
  type: 'credit' | 'debit';
  category: string;
}

const TRANSACTIONS: CoinTransaction[] = [
  {
    id: 'tx-1',
    title: 'Safe Driving Streak Reward',
    date: 'Today • 08:30 AM',
    amount: 50,
    type: 'credit',
    category: 'Driving Performance',
  },
  {
    id: 'tx-2',
    title: 'Service Booking Discount',
    date: 'Yesterday • 04:12 PM',
    amount: -250,
    type: 'debit',
    category: 'Finder Marketplace',
  },
  {
    id: 'tx-3',
    title: 'Friend Referral (David K.)',
    date: '12 Sep 2026',
    amount: 200,
    type: 'credit',
    category: 'Referral Bonus',
  },
  {
    id: 'tx-4',
    title: 'Incident Verification Contribution',
    date: '08 Sep 2026',
    amount: 100,
    type: 'credit',
    category: 'Community Safety',
  },
];

interface CoinsWalletScreenProps {
  onBack?: () => void;
  onNavigateToFinder?: () => void;
}

export function CoinsWalletScreen({
  onBack,
  onNavigateToFinder,
}: CoinsWalletScreenProps): React.JSX.Element {
  const [balance, setBalance] = useState(1450);
  const [isRedeemModalVisible, setIsRedeemModalVisible] = useState(false);

  const handleRedeemReward = (coinsCost: number, title: string) => {
    if (balance < coinsCost) {
      Alert.alert('Insufficient Balance', 'You need more AutoSecure Coins to redeem this reward.');
      return;
    }
    setBalance((prev) => prev - coinsCost);
    setIsRedeemModalVisible(false);
    Alert.alert(
      'Reward Redeemed!',
      `You have successfully redeemed: ${title}. Voucher code has been applied to your account.`
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

          <Text style={styles.headerTitle}>AutoSecure Coins</Text>

          <View style={styles.headerRightSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Balance Hero Card */}
          <View style={styles.heroCard}>
            <View style={styles.heroTop}>
              <View style={styles.coinBadge}>
                <CoinIcon color="#F59E0B" size={24} />
                <Text style={styles.coinBadgeText}>Reward Wallet</Text>
              </View>
              <Text style={styles.tierBadge}>Gold Tier</Text>
            </View>

            <View style={styles.balanceContainer}>
              <Text style={styles.balanceAmount}>{balance.toLocaleString()}</Text>
              <Text style={styles.balanceUnit}>Coins</Text>
            </View>

            <Text style={styles.balanceValueText}>
              Estimated Value: ₦{(balance * 10).toLocaleString()} in service discounts
            </Text>

            <View style={styles.heroActionsRow}>
              <TouchableOpacity
                style={styles.redeemBtn}
                activeOpacity={0.8}
                onPress={() => setIsRedeemModalVisible(true)}
              >
                <Text style={styles.redeemBtnText}>Redeem Rewards</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.earnBtn}
                activeOpacity={0.8}
                onPress={() => {
                  Alert.alert(
                    'Invite & Earn',
                    'Share your referral code AUTOSECURE-9082 with friends to earn 200 Coins per active vehicle installation.'
                  );
                }}
              >
                <Text style={styles.earnBtnText}>Invite & Earn</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* How to Earn Coins */}
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Ways to Earn Coins</Text>
          </View>

          <View style={styles.earnCardsList}>
            {/* Way 1 */}
            <View style={styles.earnCard}>
              <View style={[styles.earnIconBox, { backgroundColor: '#ECFDF5' }]}>
                <ShieldCheckIcon color="#059669" size={20} />
              </View>
              <View style={styles.earnTextBox}>
                <Text style={styles.earnTitle}>Safe Driving Streak</Text>
                <Text style={styles.earnDesc}>Zero speed breaches or hard braking for 7 days</Text>
              </View>
              <View style={styles.earnRewardBadge}>
                <Text style={styles.earnRewardText}>+50 Coins</Text>
              </View>
            </View>

            {/* Way 2 */}
            <View style={styles.earnCard}>
              <View style={[styles.earnIconBox, { backgroundColor: '#EFF6FF' }]}>
                <InviteFriendsIcon color="#2563EB" size={20} />
              </View>
              <View style={styles.earnTextBox}>
                <Text style={styles.earnTitle}>Referral Bonus</Text>
                <Text style={styles.earnDesc}>When a friend pairs their first tracking unit</Text>
              </View>
              <View style={styles.earnRewardBadge}>
                <Text style={styles.earnRewardText}>+200 Coins</Text>
              </View>
            </View>

            {/* Way 3 */}
            <View style={styles.earnCard}>
              <View style={[styles.earnIconBox, { backgroundColor: '#FFF7ED' }]}>
                <WrenchToolIcon color="#EA580C" size={20} />
              </View>
              <View style={styles.earnTextBox}>
                <Text style={styles.earnTitle}>Finder Service Cash-Back</Text>
                <Text style={styles.earnDesc}>Earn 5% Coins on completed vendor bookings</Text>
              </View>
              <View style={styles.earnRewardBadge}>
                <Text style={styles.earnRewardText}>5% Back</Text>
              </View>
            </View>
          </View>

          {/* Transactions History */}
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Coin Activity Ledger</Text>
          </View>

          <View style={styles.ledgerCard}>
            {TRANSACTIONS.map((tx, idx) => (
              <View
                key={tx.id}
                style={[
                  styles.txRow,
                  idx === TRANSACTIONS.length - 1 && styles.lastTxRow,
                ]}
              >
                <View style={styles.txInfo}>
                  <Text style={styles.txTitle}>{tx.title}</Text>
                  <Text style={styles.txDate}>{tx.date} • {tx.category}</Text>
                </View>

                <Text
                  style={[
                    styles.txAmount,
                    tx.type === 'credit' ? styles.txCredit : styles.txDebit,
                  ]}
                >
                  {tx.type === 'credit' ? `+${tx.amount}` : tx.amount} Coins
                </Text>
              </View>
            ))}
          </View>
        </ScrollView>

        {/* Redeem Bottom Sheet Modal */}
        <Modal
          visible={isRedeemModalVisible}
          transparent={true}
          animationType="slide"
          onRequestClose={() => setIsRedeemModalVisible(false)}
        >
          <View style={styles.bottomSheetBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsRedeemModalVisible(false)}
            />

            <View style={styles.redeemBottomSheet}>
              <View style={styles.sheetHandleContainer}>
                <View style={styles.sheetHandle} />
              </View>

              <Text style={styles.sheetTitle}>Redeem Coins</Text>
              <Text style={styles.sheetSubtitle}>
                Apply your available {balance} Coins to discounts and service perks.
              </Text>

              <View style={styles.rewardsList}>
                {/* Reward 1 */}
                <TouchableOpacity
                  style={styles.rewardCard}
                  activeOpacity={0.7}
                  onPress={() => handleRedeemReward(250, '₦2,500 Finder Service Voucher')}
                >
                  <View style={styles.rewardTextBox}>
                    <Text style={styles.rewardTitle}>₦2,500 Off Any Finder Service</Text>
                    <Text style={styles.rewardSub}>Valid on mechanics, car wash & towing</Text>
                  </View>
                  <View style={styles.rewardPriceBadge}>
                    <Text style={styles.rewardPriceText}>250 Coins</Text>
                  </View>
                </TouchableOpacity>

                {/* Reward 2 */}
                <TouchableOpacity
                  style={styles.rewardCard}
                  activeOpacity={0.7}
                  onPress={() => handleRedeemReward(500, '₦5,000 Off Spare Parts Order')}
                >
                  <View style={styles.rewardTextBox}>
                    <Text style={styles.rewardTitle}>₦5,000 Off Spare Parts Order</Text>
                    <Text style={styles.rewardSub}>Verified OEM & aftermarket sellers</Text>
                  </View>
                  <View style={styles.rewardPriceBadge}>
                    <Text style={styles.rewardPriceText}>500 Coins</Text>
                  </View>
                </TouchableOpacity>

                {/* Reward 3 */}
                <TouchableOpacity
                  style={styles.rewardCard}
                  activeOpacity={0.7}
                  onPress={() => handleRedeemReward(1000, '1 Month Free Premium Fleet Telematics')}
                >
                  <View style={styles.rewardTextBox}>
                    <Text style={styles.rewardTitle}>1 Month Free Premium Telematics</Text>
                    <Text style={styles.rewardSub}>Unlimited continuous 1s GPS refresh</Text>
                  </View>
                  <View style={styles.rewardPriceBadge}>
                    <Text style={styles.rewardPriceText}>1,000 Coins</Text>
                  </View>
                </TouchableOpacity>
              </View>

              <TouchableOpacity
                style={styles.sheetCloseBtn}
                activeOpacity={0.7}
                onPress={() => setIsRedeemModalVisible(false)}
              >
                <Text style={styles.sheetCloseBtnText}>Cancel</Text>
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
  heroCard: {
    padding: 22,
    borderRadius: 24,
    backgroundColor: '#0F172A',
    marginBottom: 24,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.16,
    shadowRadius: 14,
    elevation: 4,
  },
  heroTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  coinBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  coinBadgeText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#F8FAFC',
  },
  tierBadge: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#F59E0B',
    backgroundColor: 'rgba(245, 158, 11, 0.15)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  balanceContainer: {
    flexDirection: 'row',
    alignItems: 'baseline',
    gap: 8,
    marginBottom: 4,
  },
  balanceAmount: {
    fontSize: 38,
    fontFamily: 'Helvetica',
    fontWeight: '800',
    color: '#FFFFFF',
    letterSpacing: -0.5,
  },
  balanceUnit: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#F59E0B',
  },
  balanceValueText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
    marginBottom: 20,
  },
  heroActionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  redeemBtn: {
    flex: 1,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#F59E0B',
    alignItems: 'center',
    justifyContent: 'center',
  },
  redeemBtnText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#0F172A',
  },
  earnBtn: {
    flex: 1,
    height: 44,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.15)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  earnBtnText: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  sectionHeader: {
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
  earnCardsList: {
    gap: 10,
    marginBottom: 24,
  },
  earnCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 16,
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  earnIconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  earnTextBox: {
    flex: 1,
    paddingRight: 8,
  },
  earnTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  earnDesc: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  earnRewardBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: '#FFF7ED',
  },
  earnRewardText: {
    fontSize: 11,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EA580C',
  },
  ledgerCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    overflow: 'hidden',
  },
  txRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  lastTxRow: {
    borderBottomWidth: 0,
  },
  txInfo: {
    flex: 1,
    paddingRight: 10,
  },
  txTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  txDate: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  txAmount: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
  },
  txCredit: {
    color: '#059669',
  },
  txDebit: {
    color: '#EA580C',
  },

  /* Redeem Modal */
  bottomSheetBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  redeemBottomSheet: {
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
  sheetTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  sheetSubtitle: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#64748B',
    marginBottom: 18,
  },
  rewardsList: {
    gap: 10,
    marginBottom: 18,
  },
  rewardCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    backgroundColor: '#FFFFFF',
  },
  rewardTextBox: {
    flex: 1,
    paddingRight: 8,
  },
  rewardTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  rewardSub: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  rewardPriceBadge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    backgroundColor: '#FFF7ED',
  },
  rewardPriceText: {
    fontSize: 12,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EA580C',
  },
  sheetCloseBtn: {
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
});
