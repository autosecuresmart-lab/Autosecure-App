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
import { Checkbox, PasswordEyeIcon } from './AuthIcons';

interface LoginScreenProps {
  onSwitchToRegister: () => void;
  onForgotPassword: () => void;
}

type AccountType = 'individual' | 'business';

export function LoginScreen({
  onSwitchToRegister,
  onForgotPassword,
}: LoginScreenProps): React.JSX.Element {
  const insets = useSafeAreaInsets();
  const { signIn } = useAuth();

  const [accountType, setAccountType] = useState<AccountType>('individual');
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);

  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleLogin = async () => {
    if (!identifier.trim() || !password) return;

    setError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      await signIn({ email: identifier.trim(), password });
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors(caught.validationErrors);
        setError(caught.validationErrors.email?.[0] ?? caught.message);
      } else {
        setError('Unable to sign in. Please check your credentials.');
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
        {/* Top Header Logo: Circle Badge + autoSecure */}
        <View style={styles.header}>
          <HeaderBrandLogo />
        </View>

        {/* Title Section */}
        <View style={styles.titleSection}>
          <Text style={styles.mainTitle}>Welcome back</Text>
          <Text style={styles.mainTitle}>Ready to hit the road.</Text>
          <Text style={styles.subtitle}>
            Sign in to access your personalized dashboard{'\n'}and all available services
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
          {/* Identifier Input */}
          <View
            style={[
              styles.inputContainer,
              fieldErrors.email ? styles.inputError : null,
            ]}
          >
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

          {/* Options Row (Remember Me + Forgot Password) */}
          <View style={styles.optionsRow}>
            <TouchableOpacity
              activeOpacity={0.8}
              onPress={() => setRememberMe(!rememberMe)}
              style={styles.rememberMeGroup}
            >
              <Checkbox checked={rememberMe} onChange={setRememberMe} />
              <Text style={styles.rememberMeText}>Remember Me</Text>
            </TouchableOpacity>

            <TouchableOpacity activeOpacity={0.7} onPress={onForgotPassword}>
              <Text style={styles.forgotPasswordText}>Forgot Password</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Action Buttons */}
        <View style={styles.actionsSection}>
          {/* Login Button */}
          <TouchableOpacity
            activeOpacity={0.88}
            onPress={handleLogin}
            disabled={isSubmitting || !identifier.trim() || !password}
            style={[
              styles.loginButton,
              (!identifier.trim() || !password) && styles.buttonDisabled,
            ]}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#FFFFFF" size="small" />
            ) : (
              <Text style={styles.loginButtonText}>Login</Text>
            )}
          </TouchableOpacity>

          {/* Sign up Button */}
          <TouchableOpacity
            activeOpacity={0.85}
            onPress={onSwitchToRegister}
            style={styles.signupButton}
          >
            <Text style={styles.signupButtonText}>Sign up</Text>
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
    marginBottom: 60,
  },
  titleSection: {
    marginBottom: 32,
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
    marginBottom: 26,
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
    marginBottom: 42,
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

  optionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 14,
  },
  rememberMeGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  rememberMeText: {
    fontFamily: fonts.body,
    fontSize: 12.5,
    color: '#6B7280',
    fontWeight: '400',
  },
  forgotPasswordText: {
    fontFamily: fonts.body,
    fontSize: 12.5,
    color: '#374151',
    fontWeight: '500',
  },

  actionsSection: {
    gap: 12,
    marginTop: 'auto',
    paddingTop: 16,
  },
  loginButton: {
    width: '100%',
    height: 52,
    backgroundColor: '#1E2538',
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  loginButtonText: {
    fontFamily: fonts.header,
    fontSize: 16,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  signupButton: {
    width: '100%',
    height: 52,
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    borderWidth: 1.2,
    borderColor: '#1E2538',
    alignItems: 'center',
    justifyContent: 'center',
  },
  signupButtonText: {
    fontFamily: fonts.header,
    fontSize: 16,
    fontWeight: '700',
    color: '#1E2538',
  },
});
