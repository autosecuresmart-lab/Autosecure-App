import React, { useState } from 'react';
import {
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Svg, { Path } from 'react-native-svg';

import { BackArrowIcon, ShieldCheckIcon, UserAvatarIcon } from '../components/HomeIcons';

interface ShareIncidentScreenProps {
  incidentId?: string;
  vehicleName?: string;
  onBack?: () => void;
  onSendSuccess?: () => void;
}

interface TrustedContact {
  id: string;
  relation: string;
  phone: string;
  selected: boolean;
}

const INITIAL_CONTACTS: TrustedContact[] = [
  { id: 'c1', relation: 'Wife', phone: '+234 803 111 2233', selected: true },
  { id: 'c2', relation: 'Brother', phone: '+234 803 222 3344', selected: true },
  { id: 'c3', relation: 'Fleet Manager', phone: '+234 803 333 4455', selected: false },
  { id: 'c4', relation: 'Best Friend', phone: '+234 803 444 5566', selected: false },
];

export function ShareIncidentScreen({
  incidentId = 'INC-2026-000245',
  vehicleName = 'Toyota Corolla',
  onBack,
  onSendSuccess,
}: ShareIncidentScreenProps): React.JSX.Element {
  const [contacts, setContacts] = useState<TrustedContact[]>(INITIAL_CONTACTS);

  const toggleContact = (id: string) => {
    setContacts((prev) =>
      prev.map((c) => (c.id === id ? { ...c, selected: !c.selected } : c))
    );
  };

  const handleSend = () => {
    const selectedCount = contacts.filter((c) => c.selected).length;
    Alert.alert(
      'Notifications Sent',
      `Emergency incident notification dispatched to ${selectedCount} trusted contacts.`,
      [
        {
          text: 'OK',
          onPress: () => {
            if (onSendSuccess) {
              onSendSuccess();
            } else if (onBack) {
              onBack();
            }
          },
        },
      ]
    );
  };

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

          <Text style={styles.headerTitle}>Share Incident</Text>

          <View style={styles.headerSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Avatar Group & Headline */}
          <View style={styles.topSection}>
            <View style={styles.avatarGroup}>
              <View style={[styles.avatarCircleSmall, { backgroundColor: '#FDE68A' }]}>
                <UserAvatarIcon color="#D97706" size={16} />
              </View>
              <View style={[styles.avatarCircleSmall, { backgroundColor: '#ECFDF5', marginHorizontal: -8, zIndex: 2 }]}>
                <ShieldCheckIcon color="#10B981" size={20} />
              </View>
              <View style={[styles.avatarCircleSmall, { backgroundColor: '#BFDBFE' }]}>
                <UserAvatarIcon color="#2563EB" size={16} />
              </View>
            </View>

            <Text style={styles.headlineTitle}>Notify Trusted Contacts</Text>
            <Text style={styles.headlineSubtitle}>
              Select contacts to notify about this incident.
            </Text>
          </View>

          {/* Contact Checkbox Cards */}
          <View style={styles.contactsList}>
            {contacts.map((contact) => (
              <TouchableOpacity
                key={contact.id}
                style={[
                  styles.contactCard,
                  contact.selected && styles.contactCardSelected,
                ]}
                activeOpacity={0.8}
                onPress={() => toggleContact(contact.id)}
              >
                <View
                  style={[
                    styles.checkbox,
                    contact.selected ? styles.checkboxSelected : styles.checkboxUnselected,
                  ]}
                >
                  {contact.selected && (
                    <Svg width={12} height={12} viewBox="0 0 24 24" fill="none">
                      <Path d="M20 6L9 17L4 12" stroke="#FFFFFF" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    </Svg>
                  )}
                </View>

                <View style={styles.contactInfo}>
                  <Text style={styles.contactRelation}>{contact.relation}</Text>
                  <Text style={styles.contactPhone}>{contact.phone}</Text>
                </View>
              </TouchableOpacity>
            ))}
          </View>

          {/* Message Preview */}
          <View style={styles.previewBox}>
            <Text style={styles.previewLabel}>MESSAGE PREVIEW</Text>
            <Text style={styles.previewText}>
              {vehicleName} has been reported stolen. Incident No: {incidentId}
            </Text>
          </View>
        </ScrollView>

        {/* Bottom Button */}
        <View style={styles.bottomBar}>
          <TouchableOpacity
            style={styles.sendBtn}
            activeOpacity={0.8}
            onPress={handleSend}
          >
            <Text style={styles.sendBtnText}>Send Notifications</Text>
          </TouchableOpacity>
        </View>
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
    fontSize: 17,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
  },
  headerSpacer: {
    width: 36,
  },
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 24,
    paddingBottom: 20,
  },
  topSection: {
    alignItems: 'center',
    marginBottom: 24,
  },
  avatarGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  avatarCircleSmall: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  avatarEmoji: {
    fontSize: 20,
  },
  headlineTitle: {
    fontSize: 18,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 6,
  },
  headlineSubtitle: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#6B7280',
    textAlign: 'center',
  },
  contactsList: {
    gap: 12,
    marginBottom: 20,
  },
  contactCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#E5E7EB',
    borderRadius: 14,
    padding: 16,
    gap: 14,
  },
  contactCardSelected: {
    borderColor: '#10B981',
    backgroundColor: '#F0FDF4',
  },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxSelected: {
    backgroundColor: '#10B981',
  },
  checkboxUnselected: {
    borderWidth: 1.5,
    borderColor: '#D1D5DB',
    backgroundColor: '#FFFFFF',
  },
  contactInfo: {
    flex: 1,
  },
  contactRelation: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 2,
  },
  contactPhone: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
  previewBox: {
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#ECEFF3',
  },
  previewLabel: {
    fontSize: 10,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#94A3B8',
    letterSpacing: 0.8,
    marginBottom: 6,
  },
  previewText: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#334155',
    lineHeight: 18,
  },
  bottomBar: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#F0F2F5',
  },
  sendBtn: {
    backgroundColor: '#1E2538',
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendBtnText: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
