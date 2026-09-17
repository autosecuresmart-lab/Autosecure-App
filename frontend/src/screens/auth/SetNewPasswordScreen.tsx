import React, { useState } from 'react';
import {
  Alert,
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
import { PasswordEyeIcon } from './AuthIcons';

interface SetNewPasswordScreenProps {
  onPasswordResetSuccess: () => void;
  onBackToLogin: () => void;
}

export function SetNewPasswordScreen({
  onPasswordResetSuccess,
  onBackToLogin,
}: SetNewPasswordScreenProps): React.JSX.Element {
  const insets = useSafeAreaInsets();
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const isValid =
    password.length >= 6 && confirmPassword.length >= 6 && password === confirmPassword;

  const handleSubmit = () => {
    setErrorMessage(null);

    if (password.length < 6) {
      setErrorMessage('Password must be at least 6 characters.');
      return;
    }

    if (password !== confirmPassword) {
      setErrorMessage('Passwords do not match.');
      return;
    }

    Alert.alert(
      'Password Updated',
      'Your password has been successfully reset. Please log in with your new credentials.',
      [
        {
          text: 'Log In',
          onPress: onPasswordResetSuccess,
        },
      ],
    );
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
          <Text style={styles.mainTitle}>Set New Password</Text>
          <Text style={styles.subtitle}>Choose a new password to continue</Text>
        </View>

        {/* Error Alert */}
        {errorMessage !== null && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorText}>{errorMessage}</Text>
          </View>
        )}

        {/* Form Fields */}
        <View style={styles.formSection}>
          {/* Password Input */}
          <View style={styles.inputContainer}>
            <TextInput
              style={styles.textInput}
              value={password}
              onChangeText={setPassword}
              placeholder="Password"
              placeholderTextColor="#94A3B8"
              secureTextEntry={!showPassword}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              activeOpacity={0.7}
              onPress={() => setShowPassword(!showPassword)}
              style={styles.eyeButton}
            >
              <PasswordEyeIcon visible={showPassword} color="#94A3B8" size={20} />
            </TouchableOpacity>
          </View>

          {/* Confirm Password Input */}
          <View style={[styles.inputContainer, styles.inputSpacing]}>
            <TextInput
              style={styles.textInput}
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              placeholder="Confirm Password"
              placeholderTextColor="#94A3B8"
              secureTextEntry={!showConfirmPassword}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              activeOpacity={0.7}
              onPress={() => setShowConfirmPassword(!showConfirmPassword)}
              style={styles.eyeButton}
            >
              <PasswordEyeIcon
                visible={showConfirmPassword}
                color="#94A3B8"
                size={20}
              />
            </TouchableOpacity>
          </View>
        </View>

        {/* Bottom Actions */}
        <View style={styles.actionsSection}>
          <TouchableOpacity
            activeOpacity={0.88}
            onPress={handleSubmit}
            disabled={!isValid}
            style={[styles.continueButton, !isValid && styles.buttonDisabled]}
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

  errorBanner: {
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FECACA',
    borderRadius: 10,
    padding: 12,
    marginBottom: 16,
  },
  errorText: {
    fontFamily: fonts.body,
    color: '#DC2626',
    fontSize: 13,
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
  inputSpacing: {
    marginTop: 14,
  },
  textInput: {
    flex: 1,
    fontFamily: fonts.body,
    fontSize: 14,
    color: '#0F172A',
    height: '100%',
  },
  eyeButton: {
    padding: 4,
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
