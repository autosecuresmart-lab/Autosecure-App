import React, { useState } from 'react';
import {
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '../auth/AuthContext';
import {
  BackArrowIcon,
  BellIcon,
  CameraBadgeIcon,
  ChevronRightIcon,
  DocumentTextIcon,
  EditPencilIcon,
  EyeIcon,
  EyeOffIcon,
  HeadsetSupportIcon,
  HeartOutlineIcon,
  InviteFriendsIcon,
  LockOutlineIcon,
  LogoutArrowIcon,
  MoreHorizontalIcon,
  SettingsGearIcon,
  TrashOutlineIcon,
} from '../components/HomeIcons';

interface AccountScreenProps {
  onNavigateToSettings?: () => void;
  onNavigateToFavoriteCars?: () => void;
  onNavigateToCoins?: () => void;
  onNavigateToSubscriptions?: () => void;
  onNavigateToPaymentHistory?: () => void;
  onNavigateToPrivacyPolicy?: () => void;
  onNavigateToHelpCentre?: () => void;
  onNavigateToNotificationSettings?: () => void;
  onNavigateToNotifications?: () => void;
  onBack?: () => void;
}

export function AccountScreen({
  onNavigateToSettings,
  onNavigateToFavoriteCars,
  onNavigateToCoins,
  onNavigateToSubscriptions,
  onNavigateToPaymentHistory,
  onNavigateToPrivacyPolicy,
  onNavigateToHelpCentre,
  onNavigateToNotificationSettings,
  onNavigateToNotifications,
  onBack,
}: AccountScreenProps): React.JSX.Element {
  const { user, signOut } = useAuth();

  // Profile Form States
  const [firstName, setFirstName] = useState(user?.name ? user.name.split(' ')[0] : 'Boluwatife');
  const [lastName, setLastName] = useState(user?.name ? user.name.split(' ')[1] || 'Iyanu' : 'Iyanu');
  const [email, setEmail] = useState(user?.email || 'iyanutife@gmail.com');
  const [phone, setPhone] = useState(user?.phone || '+234 812 345 6789');

  // Modals & Bottom Sheets
  const [isEditProfileVisible, setIsEditProfileVisible] = useState(false);
  const [isSetEnginePasswordVisible, setIsSetEnginePasswordVisible] = useState(false);
  const [isLogoutModalVisible, setIsLogoutModalVisible] = useState(false);
  const [isDeleteAccountModalVisible, setIsDeleteAccountModalVisible] = useState(false);

  // Engine Password State
  const [enginePassword, setEnginePassword] = useState('');
  const [confirmEnginePassword, setConfirmEnginePassword] = useState('');
  const [showEnginePassword, setShowEnginePassword] = useState(false);
  const [showConfirmEnginePassword, setShowConfirmEnginePassword] = useState(false);

  const fullName = `${firstName} ${lastName}`.trim();

  const handleSaveProfile = () => {
    setIsEditProfileVisible(false);
    Alert.alert('Profile Updated', 'Your profile details have been successfully saved.');
  };

  const handleSaveEnginePassword = () => {
    if (!enginePassword) {
      Alert.alert('Error', 'Please enter an engine password');
      return;
    }
    if (enginePassword !== confirmEnginePassword) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }
    setIsSetEnginePasswordVisible(false);
    setEnginePassword('');
    setConfirmEnginePassword('');
    Alert.alert('Engine Password Set', 'Engine immobilization password configured successfully.');
  };

  const handleConfirmLogout = async () => {
    setIsLogoutModalVisible(false);
    try {
      await signOut();
    } catch {
      // Dev fallback
    }
  };

  const handleConfirmDeleteAccount = async () => {
    setIsDeleteAccountModalVisible(false);
    Alert.alert('Account Deleted', 'Your account and data have been removed.');
    try {
      await signOut();
    } catch {
      // Dev fallback
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

          <Text style={styles.headerTitle}>Profile</Text>

          <TouchableOpacity
            style={styles.headerCircleBtn}
            activeOpacity={0.7}
            hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
            onPress={() => setIsEditProfileVisible(true)}
          >
            <MoreHorizontalIcon color="#1E2538" size={18} />
          </TouchableOpacity>
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* User Profile Card */}
          <View style={styles.profileHeaderCard}>
            <View style={styles.avatarContainer}>
              <View style={styles.avatarWrapper}>
                <View style={styles.avatarCircle}>
                  <Text style={styles.avatarInitials}>
                    {(firstName || 'B').charAt(0)}
                    {(lastName || 'I').charAt(0)}
                  </Text>
                </View>
                <View style={styles.cameraBadge}>
                  <CameraBadgeIcon color="#FFFFFF" size={10} />
                </View>
              </View>
            </View>

            <View style={styles.profileInfoColumn}>
              <Text style={styles.profileName} numberOfLines={1}>{fullName}</Text>
              <Text style={styles.profileEmail} numberOfLines={1}>{email}</Text>
            </View>

            <TouchableOpacity
              style={styles.editProfileBtn}
              activeOpacity={0.7}
              onPress={() => setIsEditProfileVisible(true)}
            >
              <EditPencilIcon color="#94A3B8" size={14} />
              <Text style={styles.editProfileText}>Edit profile</Text>
            </TouchableOpacity>
          </View>

          {/* General Section */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>General</Text>

            <View style={styles.menuCard}>
              {/* Require password to control engine */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => setIsSetEnginePasswordVisible(true)}
              >
                <View style={styles.menuIconContainer}>
                  <LockOutlineIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Require password to control engine</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Favorite Cars */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToFavoriteCars) {
                    onNavigateToFavoriteCars();
                  } else {
                    Alert.alert('Favorite Cars', 'Manage your bookmarked vehicles.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <HeartOutlineIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Favorite Cars</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* AutoSecure Coins */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToCoins) {
                    onNavigateToCoins();
                  } else {
                    Alert.alert('AutoSecure Coins', 'Earn coins on safe driving and referrals.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <HeartOutlineIcon color="#F59E0B" size={20} />
                </View>
                <Text style={styles.menuLabel}>AutoSecure Coins & Rewards</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Subscriptions */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToSubscriptions) {
                    onNavigateToSubscriptions();
                  } else {
                    Alert.alert('AutoSecure Premium', 'Manage subscription plans and billing.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <DocumentTextIcon color="#EA580C" size={20} />
                </View>
                <Text style={styles.menuLabel}>AutoSecure Premium Plans</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Payment History */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToPaymentHistory) {
                    onNavigateToPaymentHistory();
                  } else {
                    Alert.alert('Payment History', 'View transaction receipts.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <DocumentTextIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Payment History & Receipts</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Notification Settings */}
              <TouchableOpacity
                style={[styles.menuRow, styles.lastMenuRow]}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToNotificationSettings) {
                    onNavigateToNotificationSettings();
                  } else if (onNavigateToNotifications) {
                    onNavigateToNotifications();
                  } else {
                    Alert.alert('Notification Settings', 'Configure alerts and push notifications.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <BellIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Notification</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>
            </View>
          </View>

          {/* Support Section */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Support</Text>

            <View style={styles.menuCard}>
              {/* Settings */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToSettings) {
                    onNavigateToSettings();
                  } else {
                    Alert.alert('Settings', 'App preferences & language');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <SettingsGearIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Settings</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Invite Friends */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => Alert.alert('Invite Friends', 'Share AUTOSECURE with your family and colleagues.')}
              >
                <View style={styles.menuIconContainer}>
                  <InviteFriendsIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Invite Friends</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Delete Account */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => setIsDeleteAccountModalVisible(true)}
              >
                <View style={styles.menuIconContainer}>
                  <TrashOutlineIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Delete Account</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Privacy Policy */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToPrivacyPolicy) {
                    onNavigateToPrivacyPolicy();
                  } else {
                    Alert.alert('Privacy Policy', 'View AUTOSECURE privacy terms.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <DocumentTextIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Privacy policy</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Help Support */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => {
                  if (onNavigateToHelpCentre) {
                    onNavigateToHelpCentre();
                  } else {
                    Alert.alert('Help Support', 'Contact AUTOSECURE support.');
                  }
                }}
              >
                <View style={styles.menuIconContainer}>
                  <HeadsetSupportIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Help Support</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              {/* Log out */}
              <TouchableOpacity
                style={[styles.menuRow, styles.lastMenuRow]}
                activeOpacity={0.7}
                onPress={() => setIsLogoutModalVisible(true)}
              >
                <View style={styles.menuIconContainer}>
                  <LogoutArrowIcon color="#64748B" size={20} />
                </View>
                <Text style={styles.menuLabel}>Log out</Text>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>
            </View>
          </View>
        </ScrollView>

        {/* 1. Edit Profile Modal */}
        <Modal
          visible={isEditProfileVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setIsEditProfileVisible(false)}
        >
          <View style={styles.bottomSheetBackdropLight}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsEditProfileVisible(false)}
            />
            <View style={styles.whiteBottomSheet}>
              <View style={styles.modalHeader}>
                <TouchableOpacity
                  style={styles.headerCircleBtn}
                  onPress={() => setIsEditProfileVisible(false)}
                  activeOpacity={0.7}
                >
                  <BackArrowIcon color="#1E2538" size={18} />
                </TouchableOpacity>

                <Text style={styles.headerTitle}>Edit Profile</Text>

                <TouchableOpacity
                  style={styles.headerCircleBtn}
                  activeOpacity={0.7}
                >
                  <MoreHorizontalIcon color="#1E2538" size={18} />
                </TouchableOpacity>
              </View>

              <ScrollView
                style={styles.modalScroll}
                contentContainerStyle={styles.modalScrollContent}
                keyboardShouldPersistTaps="handled"
                showsVerticalScrollIndicator={false}
              >
                {/* Avatar Center */}
                <View style={styles.modalAvatarCenter}>
                  <View style={styles.avatarWrapperLarge}>
                    <View style={styles.avatarCircleLarge}>
                      <Text style={styles.avatarInitialsLarge}>
                        {(firstName || 'B').charAt(0)}
                        {(lastName || 'I').charAt(0)}
                      </Text>
                    </View>
                    <View style={styles.cameraBadgeLarge}>
                      <EditPencilIcon color="#FFFFFF" size={12} />
                    </View>
                  </View>
                  <Text style={styles.modalUserName}>{fullName}</Text>
                </View>

                {/* Input Fields */}
                <View style={styles.inputsContainer}>
                  <View style={styles.inputCard}>
                    <TextInput
                      style={styles.textInput}
                      placeholder="First Name"
                      placeholderTextColor="#94A3B8"
                      value={firstName}
                      onChangeText={setFirstName}
                    />
                  </View>

                  <View style={styles.inputCard}>
                    <TextInput
                      style={styles.textInput}
                      placeholder="Last Name"
                      placeholderTextColor="#94A3B8"
                      value={lastName}
                      onChangeText={setLastName}
                    />
                  </View>

                  <View style={styles.inputCard}>
                    <TextInput
                      style={styles.textInput}
                      placeholder="Email"
                      placeholderTextColor="#94A3B8"
                      keyboardType="email-address"
                      autoCapitalize="none"
                      value={email}
                      onChangeText={setEmail}
                    />
                  </View>

                  <View style={styles.inputCard}>
                    <TextInput
                      style={styles.textInput}
                      placeholder="Phone"
                      placeholderTextColor="#94A3B8"
                      keyboardType="phone-pad"
                      value={phone}
                      onChangeText={setPhone}
                    />
                  </View>
                </View>

                {/* Save Button */}
                <TouchableOpacity
                  style={styles.primaryDarkBtn}
                  activeOpacity={0.8}
                  onPress={handleSaveProfile}
                >
                  <Text style={styles.primaryDarkBtnText}>Save Changes</Text>
                </TouchableOpacity>
              </ScrollView>
            </View>
          </View>
        </Modal>

        {/* 2. Set Engine Password Modal */}
        <Modal
          visible={isSetEnginePasswordVisible}
          animationType="slide"
          transparent={true}
          onRequestClose={() => setIsSetEnginePasswordVisible(false)}
        >
          <View style={styles.bottomSheetBackdropLight}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsSetEnginePasswordVisible(false)}
            />
            <View style={styles.whiteBottomSheet}>
              <View style={styles.modalHeader}>
                <TouchableOpacity
                  style={styles.headerCircleBtn}
                  onPress={() => setIsSetEnginePasswordVisible(false)}
                  activeOpacity={0.7}
                >
                  <BackArrowIcon color="#1E2538" size={18} />
                </TouchableOpacity>

                <Text style={styles.headerTitle}>Set Engine Password</Text>

                <TouchableOpacity
                  style={styles.headerCircleBtn}
                  activeOpacity={0.7}
                >
                  <MoreHorizontalIcon color="#1E2538" size={18} />
                </TouchableOpacity>
              </View>

              <ScrollView
                style={styles.modalScroll}
                contentContainerStyle={styles.modalScrollContent}
                keyboardShouldPersistTaps="handled"
                showsVerticalScrollIndicator={false}
              >
                {/* Avatar Center */}
                <View style={styles.modalAvatarCenter}>
                  <View style={styles.avatarCircleLarge}>
                    <Text style={styles.avatarInitialsLarge}>
                      {(firstName || 'B').charAt(0)}
                      {(lastName || 'I').charAt(0)}
                    </Text>
                  </View>
                  <Text style={styles.modalUserName}>{fullName}</Text>
                </View>

                {/* Password Inputs */}
                <View style={styles.inputsContainer}>
                  <View style={styles.passwordInputCard}>
                    <TextInput
                      style={styles.passwordTextInput}
                      placeholder="Password"
                      placeholderTextColor="#94A3B8"
                      secureTextEntry={!showEnginePassword}
                      value={enginePassword}
                      onChangeText={setEnginePassword}
                    />
                    <TouchableOpacity
                      onPress={() => setShowEnginePassword(!showEnginePassword)}
                      hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
                    >
                      {showEnginePassword ? (
                        <EyeOffIcon color="#94A3B8" size={18} />
                      ) : (
                        <EyeIcon color="#94A3B8" size={18} />
                      )}
                    </TouchableOpacity>
                  </View>

                  <View style={styles.passwordInputCard}>
                    <TextInput
                      style={styles.passwordTextInput}
                      placeholder="Confirm Password"
                      placeholderTextColor="#94A3B8"
                      secureTextEntry={!showConfirmEnginePassword}
                      value={confirmEnginePassword}
                      onChangeText={setConfirmEnginePassword}
                    />
                    <TouchableOpacity
                      onPress={() => setShowConfirmEnginePassword(!showConfirmEnginePassword)}
                      hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
                    >
                      {showConfirmEnginePassword ? (
                        <EyeOffIcon color="#94A3B8" size={18} />
                      ) : (
                        <EyeIcon color="#94A3B8" size={18} />
                      )}
                    </TouchableOpacity>
                  </View>
                </View>

                {/* Save Button */}
                <TouchableOpacity
                  style={styles.primaryDarkBtn}
                  activeOpacity={0.8}
                  onPress={handleSaveEnginePassword}
                >
                  <Text style={styles.primaryDarkBtnText}>Save Changes</Text>
                </TouchableOpacity>
              </ScrollView>
            </View>
          </View>
        </Modal>

        {/* 3. Logout Bottom Sheet */}
        <Modal
          visible={isLogoutModalVisible}
          transparent={true}
          animationType="slide"
          onRequestClose={() => setIsLogoutModalVisible(false)}
        >
          <View style={styles.bottomSheetBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsLogoutModalVisible(false)}
            />
            <View style={styles.darkBottomSheet}>
              <Text style={styles.sheetRedTitle}>Logout</Text>
              <Text style={styles.sheetQuestion}>Are you sure you want to log out</Text>

              <View style={styles.sheetActionsRow}>
                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsLogoutModalVisible(false)}
                >
                  <Text style={styles.sheetCancelText}>Cancel</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetGoldBtn}
                  activeOpacity={0.8}
                  onPress={handleConfirmLogout}
                >
                  <Text style={styles.sheetGoldText}>Yes, Logout</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </Modal>

        {/* 4. Delete Account Bottom Sheet */}
        <Modal
          visible={isDeleteAccountModalVisible}
          transparent={true}
          animationType="slide"
          onRequestClose={() => setIsDeleteAccountModalVisible(false)}
        >
          <View style={styles.bottomSheetBackdrop}>
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsDeleteAccountModalVisible(false)}
            />
            <View style={styles.darkBottomSheet}>
              <Text style={styles.sheetRedTitle}>Delete Account</Text>
              <Text style={styles.sheetQuestion}>Are you sure you want to delete your account</Text>

              <View style={styles.sheetActionsRow}>
                <TouchableOpacity
                  style={styles.sheetCancelBtn}
                  activeOpacity={0.8}
                  onPress={() => setIsDeleteAccountModalVisible(false)}
                >
                  <Text style={styles.sheetCancelText}>Cancel</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={styles.sheetGoldBtn}
                  activeOpacity={0.8}
                  onPress={handleConfirmDeleteAccount}
                >
                  <Text style={styles.sheetGoldText}>Yes, Delete</Text>
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
  },
  scrollContent: {
    paddingBottom: 100, // accommodate bottom tab bar
  },
  profileHeaderCard: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 18,
    borderBottomWidth: 1,
    borderBottomColor: '#F0F2F5',
  },
  avatarContainer: {
    marginRight: 14,
  },
  avatarWrapper: {
    position: 'relative',
  },
  avatarCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#1E2538',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitials: {
    fontSize: 20,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  cameraBadge: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#94A3B8',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  profileInfoColumn: {
    flex: 1,
  },
  profileName: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  profileEmail: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
  editProfileBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  editProfileText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  sectionBlock: {
    marginTop: 18,
  },
  sectionTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    paddingHorizontal: 20,
    marginBottom: 8,
  },
  menuCard: {
    backgroundColor: '#FFFFFF',
  },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F8FAFC',
  },
  lastMenuRow: {
    borderBottomWidth: 0,
  },
  menuIconContainer: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  menuLabel: {
    flex: 1,
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#1E2538',
  },
  /* Modal Styles */
  bottomSheetBackdropLight: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  whiteBottomSheet: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    maxHeight: '88%',
    paddingBottom: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 20,
    overflow: 'hidden',
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F0F2F5',
  },
  modalScroll: {
    maxHeight: '100%',
  },
  modalScrollContent: {
    paddingHorizontal: 24,
    paddingVertical: 24,
    alignItems: 'center',
  },
  modalAvatarCenter: {
    alignItems: 'center',
    marginBottom: 24,
  },
  avatarWrapperLarge: {
    position: 'relative',
    marginBottom: 10,
  },
  avatarCircleLarge: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#1E2538',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitialsLarge: {
    fontSize: 26,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  cameraBadgeLarge: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#94A3B8',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  modalUserName: {
    fontSize: 16,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  inputsContainer: {
    width: '100%',
    gap: 12,
    marginBottom: 24,
  },
  inputCard: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    paddingHorizontal: 16,
    height: 50,
    justifyContent: 'center',
  },
  textInput: {
    fontSize: 14,
    fontFamily: 'Aeonik',
    color: '#111827',
  },
  passwordInputCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    paddingHorizontal: 16,
    height: 50,
  },
  passwordTextInput: {
    flex: 1,
    fontSize: 14,
    fontFamily: 'Aeonik',
    color: '#111827',
  },
  primaryDarkBtn: {
    width: '100%',
    backgroundColor: '#1E2538',
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryDarkBtnText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  /* Dark Bottom Sheet Styles */
  bottomSheetBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.65)',
    justifyContent: 'flex-end',
  },
  darkBottomSheet: {
    backgroundColor: '#1E2538',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingHorizontal: 24,
    paddingTop: 28,
    paddingBottom: 40,
    alignItems: 'center',
  },
  sheetRedTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#EF4444',
    marginBottom: 12,
  },
  sheetQuestion: {
    fontSize: 14,
    fontFamily: 'Aeonik',
    color: '#FFFFFF',
    marginBottom: 28,
    textAlign: 'center',
  },
  sheetActionsRow: {
    flexDirection: 'row',
    gap: 12,
    width: '100%',
  },
  sheetCancelBtn: {
    flex: 1,
    backgroundColor: '#2A334B',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetCancelText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
  sheetGoldBtn: {
    flex: 1,
    backgroundColor: '#FFA500',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetGoldText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
