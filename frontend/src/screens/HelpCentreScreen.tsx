import React, { useState } from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '../auth/AuthContext';
import { BackArrowIcon, SearchIcon } from '../components/HomeIcons';

interface HelpCentreScreenProps {
  onBack?: () => void;
}

interface FaqItem {
  id: string;
  question: string;
  answer: string;
}

const FAQ_ITEMS: FaqItem[] = [
  {
    id: 'faq_1',
    question: 'How do i create an account',
    answer:
      'You can create a Boon account by: download and open the autoSecure application first then select "Create Account" on the welcome screen. Follow the instructions to register your mobile number and email.',
  },
  {
    id: 'faq_2',
    question: 'How to Download the app?',
    answer:
      'Quick guide to downloading the app on your device: Visit Google Play Store or Apple App Store, search for "AUTOSECURE GPS", and tap install.',
  },
  {
    id: 'faq_3',
    question: 'How to remotely switch off vehicle engine?',
    answer:
      'Go to the vehicle detail screen, enter your Engine Password if enabled, and tap "Switch Engine Off". The tracker will safely cut fuel delivery when vehicle speed drops below 20 km/h.',
  },
  {
    id: 'faq_4',
    question: 'How do geofence notifications work?',
    answer:
      'You can set custom virtual perimeters on the map. Whenever your vehicle crosses the boundary, instant push notifications and SMS alerts are dispatched immediately.',
  },
];

export function HelpCentreScreen({ onBack }: HelpCentreScreenProps): React.JSX.Element {
  const { user } = useAuth();
  const [searchQuery, setSearchQuery] = useState('');
  const [expandedId, setExpandedId] = useState<string | null>('faq_1');

  const firstName = user?.name ? user.name.split(' ')[0] : 'Iyanu';

  const filteredFaqs = FAQ_ITEMS.filter((item) =>
    item.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
    item.answer.toLowerCase().includes(searchQuery.toLowerCase())
  );

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

          <Text style={styles.headerTitle}>Help Centre</Text>

          <View style={styles.headerSpacer} />
        </View>

        <ScrollView
          style={styles.scrollContainer}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* Greeting */}
          <View style={styles.greetingBlock}>
            <Text style={styles.greetingTitle}>Hello, {firstName}!</Text>
            <Text style={styles.greetingSubtitle}>What can i help you for today</Text>
          </View>

          {/* Search Box */}
          <View style={styles.searchCard}>
            <SearchIcon color="#64748B" size={18} />
            <TextInput
              style={styles.searchInput}
              placeholder="Search"
              placeholderTextColor="#94A3B8"
              value={searchQuery}
              onChangeText={setSearchQuery}
            />
          </View>
          <Text style={styles.searchHelperText}>
            You can search some keywords from your problem for faster solution you might have.
          </Text>

          {/* Frequently Asked Section */}
          <View style={styles.faqSection}>
            <Text style={styles.faqSectionTitle}>Frequently Asked</Text>

            <View style={styles.faqList}>
              {filteredFaqs.map((faq) => {
                const isExpanded = expandedId === faq.id;
                return (
                  <TouchableOpacity
                    key={faq.id}
                    style={styles.faqCard}
                    activeOpacity={0.8}
                    onPress={() => setExpandedId(isExpanded ? null : faq.id)}
                  >
                    <Text style={styles.faqQuestion}>{faq.question}</Text>
                    {isExpanded && (
                      <Text style={styles.faqAnswer}>{faq.answer}</Text>
                    )}
                  </TouchableOpacity>
                );
              })}
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
    paddingVertical: 18,
  },
  greetingBlock: {
    marginBottom: 16,
  },
  greetingTitle: {
    fontSize: 20,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  greetingSubtitle: {
    fontSize: 13,
    fontFamily: 'Aeonik',
    color: '#6B7280',
  },
  searchCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#111827',
    borderRadius: 10,
    paddingHorizontal: 14,
    height: 48,
    gap: 10,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    fontFamily: 'Aeonik',
    color: '#111827',
  },
  searchHelperText: {
    fontSize: 11,
    fontFamily: 'Aeonik',
    color: '#6B7280',
    marginTop: 8,
    lineHeight: 16,
    marginBottom: 24,
  },
  faqSection: {
    gap: 12,
  },
  faqSectionTitle: {
    fontSize: 15,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    marginBottom: 4,
  },
  faqList: {
    gap: 12,
  },
  faqCard: {
    backgroundColor: '#FFFFFF',
    borderWidth: 1.5,
    borderColor: '#1E2538',
    borderRadius: 12,
    padding: 16,
    gap: 8,
  },
  faqQuestion: {
    fontSize: 14,
    fontFamily: 'Helvetica',
    fontWeight: '700',
    color: '#111827',
    lineHeight: 20,
  },
  faqAnswer: {
    fontSize: 12,
    fontFamily: 'Aeonik',
    color: '#4B5563',
    lineHeight: 18,
    marginTop: 4,
  },
});
