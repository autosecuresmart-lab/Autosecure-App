import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { HeaderBrandLogo } from '../../components/AutoSecureLogo';
import { fonts } from '../../theme';

interface ForgotPasswordScreenProps {
  onContinue: (identifier: string) => void;
  onBackToLogin: () => void;
}

export function ForgotPasswordScreen({
  onContinue,
  onBackToLogin,
}: ForgotPasswordScreenProps): React.JSX.Element {
  const insets = useSafeAreaInsets();
  const [identifier, setIdentifier] = useState('');

  const handleSubmit = () => {
    if (!identifier.trim()) return;
    onContinue(identifier.trim());
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <StatusBar style="dark" />
      <ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          {
            paddingTop: Math.max(insets.top, 20) + 10,
            paddingBottom: Math.max(insets.bottom, 16) + 16,
          },
        ]}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        {/* Top Header Logo: Circle Badge + autoSecure */}
        <View style={styles.header}>
          <HeaderBrandLogo />
        </View>

        {/* Title Section */}
        <View style={styles.titleSection}>
          <Text style={styles.mainTitle}>Reset your password</Text>
          <Text style={styles.subtitle}>
            Enter your email and we'll help you reset your password.
          </Text>
        </View>

        {/* Form Field */}
        <View style={styles.formSection}>
          <View style={styles.inputContainer}>
            <TextInput
              style={styles.textInput}
              value={identifier}
              onChangeText={setIdentifier}
              placeholder="Email/Phone Number"
              placeholderTextColor="#94A3B8"
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="email-address"
            />
          </View>
        </View>

        {/* Bottom Actions */}
        <View style={styles.actionsSection}>
          <TouchableOpacity
            activeOpacity={0.88}
            onPress={handleSubmit}
            disabled={!identifier.trim()}
            style={[
              styles.continueButton,
              !identifier.trim() && styles.buttonDisabled,
            ]}
          >
            <Text style={styles.continueButtonText}>Continue</Text>
          </TouchableOpacity>

          <TouchableOpacity
            activeOpacity={0.7}
            onPress={onBackToLogin}
            style={styles.backButton}
          >
            <Text style={styles.backButtonText}>Back to login</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  scrollContent: {
    paddingHorizontal: 22,
    flexGrow: 1,
  },
  header: {
    alignItems: 'flex-start',
    marginBottom: 30,
  },
  titleSection: {
    marginBottom: 28,
  },
  mainTitle: {
    fontFamily: fonts.header,
    fontSize: 27,
    fontWeight: '800',
    color: '#000000',
    letterSpacing: -0.6,
  },
  subtitle: {
    fontFamily: fonts.body,
    fontSize: 13.5,
    lineHeight: 19.5,
    color: '#71717A',
    marginTop: 10,
  },

  formSection: {
    marginBottom: 32,
  },
  inputContainer: {
    height: 50,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
  },
  textInput: {
    flex: 1,
    fontFamily: fonts.body,
    fontSize: 14,
    color: '#0F172A',
    height: '100%',
  },

  actionsSection: {
    marginTop: 'auto',
    paddingTop: 16,
    gap: 12,
  },
  continueButton: {
    width: '100%',
    height: 52,
    backgroundColor: '#1E2538',
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  continueButtonText: {
    fontFamily: fonts.header,
    fontSize: 16,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  backButton: {
    alignItems: 'center',
    paddingVertical: 8,
  },
  backButtonText: {
    fontFamily: fonts.body,
    fontSize: 14,
    color: '#6B7280',
    fontWeight: '500',
  },
});
