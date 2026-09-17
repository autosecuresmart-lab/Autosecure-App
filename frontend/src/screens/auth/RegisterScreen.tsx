import React, { useState } from 'react';
import {
  ActivityIndicator,
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

import { ApiError } from '../../api/client';
import { useAuth } from '../../auth/AuthContext';
import { HeaderBrandLogo } from '../../components/AutoSecureLogo';
import { fonts } from '../../theme';
import { PasswordEyeIcon } from './AuthIcons';

interface RegisterScreenProps {
  onSwitchToLogin: () => void;
}

type AccountType = 'individual' | 'business';

interface FieldErrors {
  first_name?: string[];
  last_name?: string[];
  email?: string[];
  phone?: string[];
  password?: string[];
  password_confirmation?: string[];
  [key: string]: string[] | undefined;
}

export function RegisterScreen({ onSwitchToLogin }: RegisterScreenProps): React.JSX.Element {
  const insets = useSafeAreaInsets();
  const { signUp } = useAuth();

  const [accountType, setAccountType] = useState<AccountType>('individual');
  const [companyName, setCompanyName] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const canSubmit =
    firstName.trim() !== '' &&
    lastName.trim() !== '' &&
    email.trim() !== '' &&
    phone.trim() !== '' &&
    (accountType === 'individual' || companyName.trim() !== '') &&
    password.length >= 8 &&
    password === confirmPassword;

  const handleSubmit = async () => {
    setError(null);
    setFieldErrors({});

    if (!canSubmit) {
      if (password !== confirmPassword) {
        setError('Passwords do not match.');
      }
      return;
    }

    setIsSubmitting(true);

    try {
      const fullName = `${firstName.trim()} ${lastName.trim()}`.trim();
      await signUp({
        name: fullName,
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        password,
        password_confirmation: confirmPassword,
        account_type: accountType,
        company_name: accountType === 'business' ? companyName.trim() : undefined,
      });
    } catch (caught) {
      if (caught instanceof ApiError) {
        const errors = caught.validationErrors as FieldErrors;
        setFieldErrors(errors);
        setError(
          errors.first_name?.[0] ??
            errors.last_name?.[0] ??
            errors.email?.[0] ??
            errors.phone?.[0] ??
            errors.password?.[0] ??
            caught.message,
        );
      } else {
        setError('Unable to create your account. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
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
        {/* Top Header Logo */}
        <View style={styles.header}>
          <HeaderBrandLogo />
        </View>

        {/* Title Section */}
        <View style={styles.titleSection}>
          <Text style={styles.mainTitle}>Create your account</Text>
          <Text style={styles.mainTitle}>Ready to hit the road.</Text>
          <Text style={styles.subtitle}>
            Sign up to access your personalized dashboard{'\n'}and protect your vehicles
          </Text>
        </View>

        {/* Error Alert */}
        {error !== null && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* Account Type Selector (Individual / Business) */}
        <View style={styles.accountTypeRow}>
          {/* Individual Card */}
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => setAccountType('individual')}
            style={[
              styles.typeCard,
              accountType === 'individual'
                ? styles.typeCardIndividualSelected
                : styles.typeCardUnselected,
            ]}
          >
            <View
              style={[
                styles.radioOuter,
                accountType === 'individual'
                  ? styles.radioOuterOrange
                  : styles.radioOuterSlate,
              ]}
            >
              {accountType === 'individual' && <View style={styles.radioInnerOrange} />}
            </View>
            <Text
              style={[
                styles.typeText,
                accountType === 'individual'
                  ? styles.typeTextOrange
                  : styles.typeTextSlate,
              ]}
            >
              Individual
            </Text>
          </TouchableOpacity>

          {/* Business Card */}
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={() => setAccountType('business')}
            style={[
              styles.typeCard,
              accountType === 'business'
                ? styles.typeCardBusinessSelected
                : styles.typeCardUnselected,
            ]}
          >
            <View
              style={[
                styles.radioOuter,
                accountType === 'business'
                  ? styles.radioOuterDark
                  : styles.radioOuterSlate,
              ]}
            >
              {accountType === 'business' && <View style={styles.radioInnerDark} />}
            </View>
            <Text
              style={[
                styles.typeText,
                accountType === 'business'
                  ? styles.typeTextDark
                  : styles.typeTextSlate,
              ]}
            >
              Business
            </Text>
          </TouchableOpacity>
        </View>

        {/* Input Form Fields */}
        <View style={styles.formSection}>
          {/* Separate Name Row: First Name & Last Name */}
          <View style={styles.nameRow}>
            {/* First Name */}
            <View
              style={[
                styles.inputContainer,
                styles.nameInput,
                fieldErrors.first_name ? styles.inputError : null,
              ]}
            >
              <TextInput
                style={styles.textInput}
                value={firstName}
                onChangeText={setFirstName}
                placeholder="First Name"
                placeholderTextColor="#94A3B8"
                autoCapitalize="words"
                autoCorrect={false}
              />
            </View>

            {/* Last Name */}
            <View
              style={[
                styles.inputContainer,
                styles.nameInput,
                fieldErrors.last_name ? styles.inputError : null,
              ]}
            >
              <TextInput
                style={styles.textInput}
                value={lastName}
                onChangeText={setLastName}
                placeholder="Last Name"
                placeholderTextColor="#94A3B8"
                autoCapitalize="words"
                autoCorrect={false}
              />
            </View>
          </View>

          {/* Company / Fleet Name (Shown only for Business accounts) */}
          {accountType === 'business' && (
            <View
              style={[
                styles.inputContainer,
                styles.inputSpacing,
                fieldErrors.company_name ? styles.inputError : null,
              ]}
            >
              <TextInput
                style={styles.textInput}
                value={companyName}
                onChangeText={setCompanyName}
                placeholder="Company / Business Name (e.g. Apex Logistics Ltd)"
                placeholderTextColor="#94A3B8"
                autoCapitalize="words"
                autoCorrect={false}
              />
            </View>
          )}

          {/* Email Input (Compulsory) */}
          <View
            style={[
              styles.inputContainer,
              styles.inputSpacing,
              fieldErrors.email ? styles.inputError : null,
            ]}
          >
            <TextInput
              style={styles.textInput}
              value={email}
              onChangeText={setEmail}
              placeholder="Email Address"
              placeholderTextColor="#94A3B8"
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="email-address"
            />
          </View>

          {/* Phone Number Input (Compulsory) */}
          <View
            style={[
              styles.inputContainer,
              styles.inputSpacing,
              fieldErrors.phone ? styles.inputError : null,
            ]}
          >
            <TextInput
              style={styles.textInput}
              value={phone}
              onChangeText={setPhone}
              placeholder="Phone Number (e.g. +234 801 234 5678)"
              placeholderTextColor="#94A3B8"
              keyboardType="phone-pad"
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>

          {/* Password Input */}
          <View
            style={[
              styles.inputContainer,
              styles.inputSpacing,
              fieldErrors.password ? styles.inputError : null,
            ]}
          >
            <TextInput
              style={styles.textInput}
              value={password}
              onChangeText={setPassword}
              placeholder="Password (at least 8 characters)"
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
          <View
            style={[
              styles.inputContainer,
              styles.inputSpacing,
              confirmPassword !== '' && password !== confirmPassword
                ? styles.inputError
                : null,
            ]}
          >
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

        {/* Action Buttons */}
        <View style={styles.actionsSection}>
          {/* Create Account Button */}
          <TouchableOpacity
            activeOpacity={0.88}
            onPress={handleSubmit}
            disabled={isSubmitting || !canSubmit}
            style={[styles.signupButton, !canSubmit && styles.buttonDisabled]}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#FFFFFF" size="small" />
            ) : (
              <Text style={styles.signupButtonText}>Create account</Text>
            )}
          </TouchableOpacity>

          {/* Sign In Button */}
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={onSwitchToLogin}
            style={styles.loginButton}
          >
            <Text style={styles.loginButtonText}>Sign in</Text>
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
    marginBottom: 26,
  },
  mainTitle: {
    fontFamily: fonts.header,
    fontSize: 27,
    fontWeight: '800',
    color: '#000000',
    letterSpacing: -0.6,
    lineHeight: 35,
  },
  subtitle: {
    fontFamily: fonts.body,
    fontSize: 13.5,
    lineHeight: 19.5,
    color: '#71717A',
    marginTop: 10,
    letterSpacing: -0.1,
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

  accountTypeRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 24,
  },
  typeCard: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    height: 50,
    borderRadius: 10,
    paddingHorizontal: 14,
    backgroundColor: '#FFFFFF',
  },
  typeCardIndividualSelected: {
    borderWidth: 1.5,
    borderColor: '#DE8635',
  },
  typeCardBusinessSelected: {
    borderWidth: 1.5,
    borderColor: '#334155',
  },
  typeCardUnselected: {
    borderWidth: 1.5,
    borderColor: '#334155',
  },
  radioOuter: {
    width: 20,
    height: 20,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  radioOuterOrange: {
    borderWidth: 2,
    borderColor: '#DE8635',
  },
  radioOuterDark: {
    borderWidth: 2,
    borderColor: '#334155',
  },
  radioOuterSlate: {
    borderWidth: 1.8,
    borderColor: '#4A5568',
  },
  radioInnerOrange: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#DE8635',
  },
  radioInnerDark: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#334155',
  },
  typeText: {
    fontFamily: fonts.header,
    fontSize: 13.5,
    letterSpacing: -0.1,
    fontWeight: '500',
  },
  typeTextOrange: {
    color: '#DE8635',
  },
  typeTextDark: {
    color: '#334155',
  },
  typeTextSlate: {
    color: '#334155',
  },

  formSection: {
    marginBottom: 28,
  },
  nameRow: {
    flexDirection: 'row',
    gap: 12,
  },
  nameInput: {
    flex: 1,
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
  inputError: {
    borderColor: '#EF4444',
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
    gap: 12,
    marginTop: 'auto',
    paddingTop: 8,
  },
  signupButton: {
    width: '100%',
    height: 52,
    backgroundColor: '#1E2538',
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonDisabled: {
    opacity: 0.65,
  },
  signupButtonText: {
    fontFamily: fonts.header,
    fontSize: 16,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  loginButton: {
    width: '100%',
    height: 52,
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    borderWidth: 1.2,
    borderColor: '#1E2538',
    alignItems: 'center',
    justifyContent: 'center',
  },
  loginButtonText: {
    fontFamily: fonts.header,
    fontSize: 16,
    fontWeight: '700',
    color: '#1E2538',
  },
});
