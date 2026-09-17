import React from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { BackArrowIcon } from '../components/HomeIcons';

interface PrivacyPolicyScreenProps {
  onBack?: () => void;
}

export function PrivacyPolicyScreen({ onBack }: PrivacyPolicyScreenProps): React.JSX.Element {
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

          <Text style={styles.headerTitle}>Privacy Policy</Text>

          <View style={styles.headerSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Section 1 */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionHeading}>1. Types data we collect</Text>
            <Text style={styles.sectionBody}>
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
            </Text>
          </View>

          {/* Section 2 */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionHeading}>2. Use of your personal data</Text>
            <Text style={styles.sectionBody}>
              Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae.
            </Text>
          </View>

          {/* Section 3 */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionHeading}>3. Disclosure of your personal data</Text>
            <Text style={styles.sectionBody}>
              At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga.
            </Text>
          </View>

          {/* Section 4 */}
          <View style={styles.sectionBlock}>
            <Text style={styles.sectionHeading}>4. Security of Telemetry & GPS Data</Text>
            <Text style={styles.sectionBody}>
              All live vehicle telemetry, OBD-II diagnostic stream, and dashcam recordings are encrypted at rest using AES-256 and transmitted securely via TLS 1.3 to dedicated AUTOSECURE servers.
            </Text>
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
    paddingHorizontal: 24,
    paddingVertical: 20,
    gap: 24,
  },
  sectionBlock: {
    gap: 8,
  },
  sectionHeading: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  sectionBody: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#4B5563',
    lineHeight: 20,
  },
});
