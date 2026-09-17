import React, { useState } from 'react';
import {
  Alert,
  Modal,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  BackArrowIcon,
  BellIcon,
  CheckmarkCircleIcon,
  ChevronRightIcon,
  DevicePhoneIcon,
  DocumentTextIcon,
  FaceIdIcon,
  GeoFenceIcon,
  MoonIcon,
  ShieldCheckIcon,
  SunIcon,
  TrashOutlineIcon,
} from '../components/HomeIcons';

export type ThemeMode = 'light' | 'dark' | 'system';

interface SettingsScreenProps {
  onBack?: () => void;
  onNavigateToNotificationSettings?: () => void;
  onNavigateToPrivacyPolicy?: () => void;
}

export function SettingsScreen({
  onBack,
  onNavigateToNotificationSettings,
  onNavigateToPrivacyPolicy,
}: SettingsScreenProps): React.JSX.Element {
  // Appearance / Theme Mode state
  const [themeMode, setThemeMode] = useState<ThemeMode>('system');
  const [isThemeModalVisible, setIsThemeModalVisible] = useState<boolean>(false);

  // Security Toggles
  const [biometricsEnabled, setBiometricsEnabled] = useState<boolean>(true);
  const [autoArmGeofence, setAutoArmGeofence] = useState<boolean>(true);
  const [highPrecisionGps, setHighPrecisionGps] = useState<boolean>(true);

  // Notification Toggles
  const [pushNotifications, setPushNotifications] = useState<boolean>(true);
  const [criticalSirenAlerts, setCriticalSirenAlerts] = useState<boolean>(true);

  // Cache & Storage
  const [cacheSize, setCacheSize] = useState<string>('28.4 MB');

  const getThemeModeLabel = (mode: ThemeMode) => {
    switch (mode) {
      case 'light':
        return 'Light Mode';
      case 'dark':
        return 'Dark Mode';
      case 'system':
      default:
        return 'System Default';
    }
  };

  const handleClearCache = () => {
    Alert.alert(
      'Clear Telemetry Cache',
      'Are you sure you want to clear stored offline map tiles and cached vehicle telemetry?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Clear',
          style: 'destructive',
          onPress: () => {
            setCacheSize('0.0 KB');
            Alert.alert('Cache Cleared', 'Local telemetry and map cache cleared successfully.');
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
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

          <Text style={styles.headerTitle}>Settings</Text>

          <View style={styles.headerRightSpacer} />
        </View>

        {/* Scrollable Content */}
        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Section 1: Appearance & Preferences */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Appearance & Display</Text>
            <View style={styles.menuCard}>
              {/* Theme Mode Selector */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => setIsThemeModalVisible(true)}
              >
                <View style={[styles.menuIconContainer, { backgroundColor: '#FEF3C7' }]}>
                  {themeMode === 'light' ? (
                    <SunIcon color="#D97706" size={18} />
                  ) : themeMode === 'dark' ? (
                    <MoonIcon color="#4F46E5" size={18} />
                  ) : (
                    <DevicePhoneIcon color="#059669" size={18} />
                  )}
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Theme Mode</Text>
                  <Text style={styles.menuSubLabel}>Choose light, dark, or system mode</Text>
                </View>
                <View style={styles.menuValueContainer}>
                  <Text style={styles.menuValueText}>{getThemeModeLabel(themeMode)}</Text>
                  <ChevronRightIcon color="#94A3B8" size={16} />
                </View>
              </TouchableOpacity>

              {/* Language */}
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={() => Alert.alert('Language', 'autoSecure currently operates in English (US).')}
              >
                <View style={[styles.menuIconContainer, { backgroundColor: '#EFF6FF' }]}>
                  <ShieldCheckIcon color="#2563EB" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>App Language</Text>
                  <Text style={styles.menuSubLabel}>Regional dialect and formats</Text>
                </View>
                <View style={styles.menuValueContainer}>
                  <Text style={styles.menuValueText}>English (US)</Text>
                  <ChevronRightIcon color="#94A3B8" size={16} />
                </View>
              </TouchableOpacity>

              {/* Distance Units */}
              <TouchableOpacity
                style={[styles.menuRow, styles.lastMenuRow]}
                activeOpacity={0.7}
                onPress={() => Alert.alert('Distance Units', 'Units set to Metric (Kilometers / km/h).')}
              >
                <View style={[styles.menuIconContainer, { backgroundColor: '#F3E8FF' }]}>
                  <GeoFenceIcon color="#7C3AED" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Units of Measure</Text>
                  <Text style={styles.menuSubLabel}>Speed and telemetry distance</Text>
                </View>
                <View style={styles.menuValueContainer}>
                  <Text style={styles.menuValueText}>Metric (km, km/h)</Text>
                  <ChevronRightIcon color="#94A3B8" size={16} />
                </View>
              </TouchableOpacity>
            </View>
          </View>

          {/* Section 2: Security & Vehicle Protection */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Security & Access</Text>
            <View style={styles.menuCard}>
              {/* Biometrics */}
              <View style={styles.menuRow}>
                <View style={[styles.menuIconContainer, { backgroundColor: '#FEE2E2' }]}>
                  <FaceIdIcon color="#DC2626" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Biometric Unlock</Text>
                  <Text style={styles.menuSubLabel}>Require Face ID / Touch ID for app access</Text>
                </View>
                <Switch
                  value={biometricsEnabled}
                  onValueChange={setBiometricsEnabled}
                  trackColor={{ false: '#E2E8F0', true: '#EA580C' }}
                  thumbColor="#FFFFFF"
                />
              </View>

              {/* Auto-Arm Geofence */}
              <View style={styles.menuRow}>
                <View style={[styles.menuIconContainer, { backgroundColor: '#FEF3C7' }]}>
                  <GeoFenceIcon color="#D97706" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Auto-Arm Perimeter</Text>
                  <Text style={styles.menuSubLabel}>Activate security boundary on parking</Text>
                </View>
                <Switch
                  value={autoArmGeofence}
                  onValueChange={setAutoArmGeofence}
                  trackColor={{ false: '#E2E8F0', true: '#EA580C' }}
                  thumbColor="#FFFFFF"
                />
              </View>

              {/* High Precision GPS */}
              <View style={[styles.menuRow, styles.lastMenuRow]}>
                <View style={[styles.menuIconContainer, { backgroundColor: '#ECFDF5' }]}>
                  <ShieldCheckIcon color="#059669" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>High-Rate Telemetry</Text>
                  <Text style={styles.menuSubLabel}>Continuous 1-second refresh rate</Text>
                </View>
                <Switch
                  value={highPrecisionGps}
                  onValueChange={setHighPrecisionGps}
                  trackColor={{ false: '#E2E8F0', true: '#EA580C' }}
                  thumbColor="#FFFFFF"
                />
              </View>
            </View>
          </View>

          {/* Section 3: Notifications & Alarms */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Notifications</Text>
            <View style={styles.menuCard}>
              {/* Push Alerts */}
              <View style={styles.menuRow}>
                <View style={[styles.menuIconContainer, { backgroundColor: '#EFF6FF' }]}>
                  <BellIcon color="#2563EB" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Push Notifications</Text>
                  <Text style={styles.menuSubLabel}>Instant alerts on vehicle movements</Text>
                </View>
                <Switch
                  value={pushNotifications}
                  onValueChange={setPushNotifications}
                  trackColor={{ false: '#E2E8F0', true: '#EA580C' }}
                  thumbColor="#FFFFFF"
                />
              </View>

              {/* Critical Siren */}
              <View style={styles.menuRow}>
                <View style={[styles.menuIconContainer, { backgroundColor: '#FEE2E2' }]}>
                  <ShieldCheckIcon color="#DC2626" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Critical Emergency Siren</Text>
                  <Text style={styles.menuSubLabel}>Override Do Not Disturb for theft alerts</Text>
                </View>
                <Switch
                  value={criticalSirenAlerts}
                  onValueChange={setCriticalSirenAlerts}
                  trackColor={{ false: '#E2E8F0', true: '#EA580C' }}
                  thumbColor="#FFFFFF"
                />
              </View>

              {/* Manage Detailed Notifications */}
              <TouchableOpacity
                style={[styles.menuRow, styles.lastMenuRow]}
                activeOpacity={0.7}
                onPress={onNavigateToNotificationSettings}
              >
                <View style={[styles.menuIconContainer, { backgroundColor: '#F1F5F9' }]}>
                  <BellIcon color="#475569" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Notification Preferences</Text>
                  <Text style={styles.menuSubLabel}>Configure geofence, speed & ignition triggers</Text>
                </View>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>
            </View>
          </View>

          {/* Section 4: Storage & Diagnostics */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>Data & Storage</Text>
            <View style={styles.menuCard}>
              <TouchableOpacity
                style={[styles.menuRow, styles.lastMenuRow]}
                activeOpacity={0.7}
                onPress={handleClearCache}
              >
                <View style={[styles.menuIconContainer, { backgroundColor: '#F1F5F9' }]}>
                  <TrashOutlineIcon color="#64748B" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Offline Telemetry Cache</Text>
                  <Text style={styles.menuSubLabel}>Cached map tiles and trip breadcrumbs</Text>
                </View>
                <View style={styles.menuValueContainer}>
                  <Text style={styles.menuValueText}>{cacheSize}</Text>
                  <Text style={styles.clearLinkText}>Clear</Text>
                </View>
              </TouchableOpacity>
            </View>
          </View>

          {/* Section 5: Legal & Version */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionTitle}>About</Text>
            <View style={styles.menuCard}>
              <TouchableOpacity
                style={styles.menuRow}
                activeOpacity={0.7}
                onPress={onNavigateToPrivacyPolicy}
              >
                <View style={[styles.menuIconContainer, { backgroundColor: '#F1F5F9' }]}>
                  <DocumentTextIcon color="#64748B" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>Privacy Policy</Text>
                </View>
                <ChevronRightIcon color="#94A3B8" size={16} />
              </TouchableOpacity>

              <View style={[styles.menuRow, styles.lastMenuRow]}>
                <View style={[styles.menuIconContainer, { backgroundColor: '#F1F5F9' }]}>
                  <ShieldCheckIcon color="#64748B" size={18} />
                </View>
                <View style={styles.menuTextContainer}>
                  <Text style={styles.menuLabel}>App Version</Text>
                  <Text style={styles.menuSubLabel}>autoSecure Mobile Core</Text>
                </View>
                <Text style={styles.versionBadge}>v2.0.4 (Build 2026)</Text>
              </View>
            </View>
          </View>
        </ScrollView>

        {/* Theme Mode Bottom Sheet Modal (Light, Dark, System) */}
        <Modal
          visible={isThemeModalVisible}
          transparent={true}
          animationType="slide"
          onRequestClose={() => setIsThemeModalVisible(false)}
        >
          <View style={styles.bottomSheetBackdrop}>
            {/* Dismiss area on top tap */}
            <TouchableOpacity
              style={styles.modalDismissArea}
              activeOpacity={1}
              onPress={() => setIsThemeModalVisible(false)}
            />

            {/* Bottom Sheet Card */}
            <View style={styles.themeBottomSheet}>
              {/* Drag Indicator Pill */}
              <View style={styles.sheetHandleContainer}>
                <View style={styles.sheetHandle} />
              </View>

              {/* Header */}
              <View style={styles.themeSheetHeader}>
                <Text style={styles.themeSheetTitle}>Mode Settings</Text>
                <Text style={styles.themeSheetSubtitle}>
                  Choose how autoSecure displays across your device
                </Text>
              </View>

              {/* Theme Options */}
              <View style={styles.themeOptionsList}>
                {/* 1. Light Mode */}
                <TouchableOpacity
                  style={[
                    styles.themeOptionCard,
                    themeMode === 'light' && styles.themeOptionCardActive,
                  ]}
                  activeOpacity={0.7}
                  onPress={() => setThemeMode('light')}
                >
                  <View style={[styles.themeOptionIconBox, { backgroundColor: '#FEF3C7' }]}>
                    <SunIcon color="#D97706" size={22} />
                  </View>
                  <View style={styles.themeOptionTextBox}>
                    <Text style={styles.themeOptionTitle}>Light Mode</Text>
                    <Text style={styles.themeOptionDesc}>Classic high-clarity daylight theme</Text>
                  </View>
                  <View style={styles.radioContainer}>
                    {themeMode === 'light' ? (
                      <CheckmarkCircleIcon color="#EA580C" size={22} />
                    ) : (
                      <View style={styles.radioUnchecked} />
                    )}
                  </View>
                </TouchableOpacity>

                {/* 2. Dark Mode */}
                <TouchableOpacity
                  style={[
                    styles.themeOptionCard,
                    themeMode === 'dark' && styles.themeOptionCardActive,
                  ]}
                  activeOpacity={0.7}
                  onPress={() => setThemeMode('dark')}
                >
                  <View style={[styles.themeOptionIconBox, { backgroundColor: '#EEF2FF' }]}>
                    <MoonIcon color="#4F46E5" size={22} />
                  </View>
                  <View style={styles.themeOptionTextBox}>
                    <Text style={styles.themeOptionTitle}>Dark Mode</Text>
                    <Text style={styles.themeOptionDesc}>Sleek low-glare night interface</Text>
                  </View>
                  <View style={styles.radioContainer}>
                    {themeMode === 'dark' ? (
                      <CheckmarkCircleIcon color="#EA580C" size={22} />
                    ) : (
                      <View style={styles.radioUnchecked} />
                    )}
                  </View>
                </TouchableOpacity>

                {/* 3. System Default */}
                <TouchableOpacity
                  style={[
                    styles.themeOptionCard,
                    themeMode === 'system' && styles.themeOptionCardActive,
                  ]}
                  activeOpacity={0.7}
                  onPress={() => setThemeMode('system')}
                >
                  <View style={[styles.themeOptionIconBox, { backgroundColor: '#ECFDF5' }]}>
                    <DevicePhoneIcon color="#059669" size={22} />
                  </View>
                  <View style={styles.themeOptionTextBox}>
                    <Text style={styles.themeOptionTitle}>System Mode</Text>
                    <Text style={styles.themeOptionDesc}>Automatically match device OS setting</Text>
                  </View>
                  <View style={styles.radioContainer}>
                    {themeMode === 'system' ? (
                      <CheckmarkCircleIcon color="#EA580C" size={22} />
                    ) : (
                      <View style={styles.radioUnchecked} />
                    )}
                  </View>
                </TouchableOpacity>
              </View>

              {/* Confirm / Apply Button */}
              <TouchableOpacity
                style={styles.applyThemeButton}
                activeOpacity={0.8}
                onPress={() => {
                  setIsThemeModalVisible(false);
                  Alert.alert(
                    'Theme Applied',
                    `App appearance updated to ${getThemeModeLabel(themeMode)}.`
                  );
                }}
              >
                <Text style={styles.applyThemeButtonText}>Save Mode</Text>
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
    paddingBottom: 80,
  },
  sectionBlock: {
    marginTop: 20,
  },
  sectionTitle: {
    fontSize: 13,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#64748B',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    paddingHorizontal: 20,
    marginBottom: 8,
  },
  menuCard: {
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: '#F1F5F9',
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
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  menuTextContainer: {
    flex: 1,
    paddingRight: 10,
  },
  menuLabel: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#1E293B',
    marginBottom: 1,
  },
  menuSubLabel: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
  },
  menuValueContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  menuValueText: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#64748B',
    fontWeight: '500',
  },
  clearLinkText: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#EA580C',
    fontWeight: '700',
    marginLeft: 4,
  },
  versionBadge: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#94A3B8',
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },

  /* Mode Settings Bottom Sheet */
  bottomSheetBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    flex: 1,
  },
  themeBottomSheet: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 20,
    paddingBottom: 32,
    paddingTop: 12,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: -6 },
    shadowOpacity: 0.15,
    shadowRadius: 18,
    elevation: 24,
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
  themeSheetHeader: {
    marginBottom: 18,
  },
  themeSheetTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  themeSheetSubtitle: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  themeOptionsList: {
    gap: 10,
    marginBottom: 20,
  },
  themeOptionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    backgroundColor: '#FFFFFF',
  },
  themeOptionCardActive: {
    borderColor: '#EA580C',
    backgroundColor: '#FFF7ED',
  },
  themeOptionIconBox: {
    width: 42,
    height: 42,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  themeOptionTextBox: {
    flex: 1,
  },
  themeOptionTitle: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  themeOptionDesc: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#64748B',
  },
  radioContainer: {
    marginLeft: 8,
  },
  radioUnchecked: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: '#CBD5E1',
  },
  applyThemeButton: {
    backgroundColor: '#111827',
    borderRadius: 14,
    height: 48,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#111827',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.18,
    shadowRadius: 10,
    elevation: 4,
  },
  applyThemeButtonText: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
