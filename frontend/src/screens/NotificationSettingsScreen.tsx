import React, { useState } from 'react';
import {
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { BackArrowIcon } from '../components/HomeIcons';

interface NotificationSettingsScreenProps {
  onBack?: () => void;
}

export function NotificationSettingsScreen({
  onBack,
}: NotificationSettingsScreenProps): React.JSX.Element {
  const [vibration, setVibration] = useState(true);
  const [deviceMoving, setDeviceMoving] = useState(true);
  const [geofenceEnter, setGeofenceEnter] = useState(false);
  const [geofenceExit, setGeofenceExit] = useState(false);
  const [ignitionOn, setIgnitionOn] = useState(false);
  const [ignitionOff, setIgnitionOff] = useState(false);

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

          <Text style={styles.headerTitle}>Notifications</Text>

          <View style={styles.headerSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* General Section */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionHeading}>General</Text>

            <View style={styles.settingRow}>
              <View style={styles.textColumn}>
                <Text style={styles.settingTitle}>Vibration</Text>
                <Text style={styles.settingSubtitle}>Vibrate Device for notification</Text>
              </View>

              <Switch
                value={vibration}
                onValueChange={setVibration}
                trackColor={{ false: '#E2E8F0', true: '#FFA500' }}
                thumbColor="#FFFFFF"
                ios_backgroundColor="#E2E8F0"
              />
            </View>
          </View>

          {/* Divider */}
          <View style={styles.sectionDivider} />

          {/* Notification Type Section */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionHeading}>Notification Type</Text>

            <View style={styles.settingRow}>
              <View style={styles.textColumn}>
                <Text style={styles.settingTitle}>Device Moving</Text>
                <Text style={styles.settingSubtitle}>Notify when start moving</Text>
              </View>
              <Switch
                value={deviceMoving}
                onValueChange={setDeviceMoving}
                trackColor={{ false: '#E2E8F0', true: '#FFA500' }}
                thumbColor="#FFFFFF"
                ios_backgroundColor="#E2E8F0"
              />
            </View>

            <View style={styles.settingRow}>
              <View style={styles.textColumn}>
                <Text style={styles.settingTitle}>Geofence Enter</Text>
                <Text style={styles.settingSubtitle}>Notify for geofence enter</Text>
              </View>
              <Switch
                value={geofenceEnter}
                onValueChange={setGeofenceEnter}
                trackColor={{ false: '#E2E8F0', true: '#FFA500' }}
                thumbColor="#FFFFFF"
                ios_backgroundColor="#E2E8F0"
              />
            </View>

            <View style={styles.settingRow}>
              <View style={styles.textColumn}>
                <Text style={styles.settingTitle}>Geofence Exit</Text>
                <Text style={styles.settingSubtitle}>Notify for geofence exit</Text>
              </View>
              <Switch
                value={geofenceExit}
                onValueChange={setGeofenceExit}
                trackColor={{ false: '#E2E8F0', true: '#FFA500' }}
                thumbColor="#FFFFFF"
                ios_backgroundColor="#E2E8F0"
              />
            </View>

            <View style={styles.settingRow}>
              <View style={styles.textColumn}>
                <Text style={styles.settingTitle}>Ignition On</Text>
                <Text style={styles.settingSubtitle}>Notify when ignition turns on</Text>
              </View>
              <Switch
                value={ignitionOn}
                onValueChange={setIgnitionOn}
                trackColor={{ false: '#E2E8F0', true: '#FFA500' }}
                thumbColor="#FFFFFF"
                ios_backgroundColor="#E2E8F0"
              />
            </View>

            <View style={styles.settingRow}>
              <View style={styles.textColumn}>
                <Text style={styles.settingTitle}>Ignition off</Text>
                <Text style={styles.settingSubtitle}>Notify when ignition turns off</Text>
              </View>
              <Switch
                value={ignitionOff}
                onValueChange={setIgnitionOff}
                trackColor={{ false: '#E2E8F0', true: '#FFA500' }}
                thumbColor="#FFFFFF"
                ios_backgroundColor="#E2E8F0"
              />
            </View>
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
    paddingBottom: 16,
    backgroundColor: '#FFFFFF',
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
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  headerSpacer: {
    width: 36,
  },
  scrollContainer: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingVertical: 20,
  },
  sectionBlock: {
    gap: 16,
  },
  sectionHeading: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  settingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  textColumn: {
    flex: 1,
    paddingRight: 16,
  },
  settingTitle: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '600',
    color: '#111827',
    marginBottom: 3,
  },
  settingSubtitle: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
  sectionDivider: {
    height: 1,
    backgroundColor: '#ECEFF3',
    marginVertical: 20,
  },
});
