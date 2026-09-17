import React, { useState } from 'react';
import {
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { HeaderBrandLogo } from '../../components/AutoSecureLogo';
import { fonts } from '../../theme';
import { BackspaceIcon } from './AuthIcons';

interface VerifyOtpScreenProps {
  phoneOrEmail?: string;
  onVerified: () => void;
  onBackToLogin: () => void;
}

export function VerifyOtpScreen({
  phoneOrEmail = '+234******00',
  onVerified,
}: VerifyOtpScreenProps): React.JSX.Element {
  const insets = useSafeAreaInsets();
  const [otp, setOtp] = useState(['6', '9', '0', '']);
  const [activeSlot, setActiveSlot] = useState(3);

  const handleKeyPress = (key: string) => {
    const nextOtp = [...otp];
    const firstEmptyIndex = nextOtp.findIndex((digit) => digit === '');
    const targetIndex = firstEmptyIndex === -1 ? 3 : firstEmptyIndex;

    nextOtp[targetIndex] = key;
    setOtp(nextOtp);

    const nextSlot = Math.min(targetIndex + 1, 3);
    setActiveSlot(nextSlot);
  };

  const handleBackspace = () => {
    const nextOtp = [...otp];
    let lastFilledIndex = -1;
    for (let i = nextOtp.length - 1; i >= 0; i--) {
      if (nextOtp[i] !== '') {
        lastFilledIndex = i;
        break;
      }
    }

    if (lastFilledIndex !== -1) {
      nextOtp[lastFilledIndex] = '';
      setOtp(nextOtp);
      setActiveSlot(lastFilledIndex);
    }
  };

  const isComplete = otp.every((digit) => digit !== '');

  const handleContinue = () => {
    if (isComplete) {
      onVerified();
    }
  };

  const handleResend = () => {
    setOtp(['', '', '', '']);
    setActiveSlot(0);
  };

  return (
    <View style={styles.container}>
      <StatusBar style="dark" />

      {/* Top Main Section */}
      <View
        style={[
          styles.contentSection,
          {
            paddingTop: Math.max(insets.top, 20) + 10,
          },
        ]}
      >
        {/* Top Header Logo: Circle Badge + autoSecure */}
        <View style={styles.header}>
          <HeaderBrandLogo />
        </View>

        {/* Title Section */}
        <View style={styles.titleSection}>
          <Text style={styles.mainTitle}>Enter verification code</Text>
          <Text style={styles.subtitle}>
            We have sent a code to :{' '}
            <Text style={styles.phoneHighlight}>{phoneOrEmail}</Text>
          </Text>
        </View>

        {/* 4-Digit OTP Boxes */}
        <View style={styles.otpContainer}>
          {otp.map((digit, index) => {
            const isSelected = activeSlot === index;
            return (
              <TouchableOpacity
                key={`otp-slot-${index}`}
                activeOpacity={0.85}
                onPress={() => setActiveSlot(index)}
                style={[
                  styles.otpBox,
                  isSelected && styles.otpBoxActive,
                  digit !== '' && styles.otpBoxFilled,
                ]}
              >
                {digit !== '' ? (
                  <Text style={styles.otpDigitText}>{digit}</Text>
                ) : isSelected ? (
                  <View style={styles.cursorBar} />
                ) : null}
              </TouchableOpacity>
            );
          })}
        </View>

        {/* Continue Button */}
        <TouchableOpacity
          activeOpacity={0.88}
          onPress={handleContinue}
          style={[styles.continueButton, !isComplete && styles.buttonDisabled]}
        >
          <Text style={styles.continueButtonText}>Continue</Text>
        </TouchableOpacity>

        {/* Resend OTP */}
        <View style={styles.resendRow}>
          <Text style={styles.resendPrompt}>Didn't receive the OTP? </Text>
          <TouchableOpacity activeOpacity={0.7} onPress={handleResend}>
            <Text style={styles.resendLink}>Resend.</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Interactive Custom Keypad */}
      <View
        style={[
          styles.keypadContainer,
          {
            paddingBottom: Math.max(insets.bottom, 16) + 12,
          },
        ]}
      >
        <Text style={styles.keypadPreview}>{otp.join('') || ' '}</Text>

        <View style={styles.keypadGrid}>
          {/* Row 1 */}
          <View style={styles.keypadRow}>
            {['1', '2', '3'].map((key) => (
              <TouchableOpacity
                key={key}
                activeOpacity={0.75}
                onPress={() => handleKeyPress(key)}
                style={styles.keypadButton}
              >
                <Text style={styles.keypadKeyText}>{key}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Row 2 */}
          <View style={styles.keypadRow}>
            {['4', '5', '6'].map((key) => (
              <TouchableOpacity
                key={key}
                activeOpacity={0.75}
                onPress={() => handleKeyPress(key)}
                style={styles.keypadButton}
              >
                <Text style={styles.keypadKeyText}>{key}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Row 3 */}
          <View style={styles.keypadRow}>
            {['7', '8', '9'].map((key) => (
              <TouchableOpacity
                key={key}
                activeOpacity={0.75}
                onPress={() => handleKeyPress(key)}
                style={styles.keypadButton}
              >
                <Text style={styles.keypadKeyText}>{key}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Row 4: Empty, 0, Backspace */}
          <View style={styles.keypadRow}>
            <View style={styles.keypadEmptySpace} />

            <TouchableOpacity
              activeOpacity={0.75}
              onPress={() => handleKeyPress('0')}
              style={styles.keypadButton}
            >
              <Text style={styles.keypadKeyText}>0</Text>
            </TouchableOpacity>

            <TouchableOpacity
              activeOpacity={0.75}
              onPress={handleBackspace}
              style={styles.keypadButton}
            >
              <BackspaceIcon size={22} color="#1E2538" />
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    justifyContent: 'space-between',
  },
  contentSection: {
    paddingHorizontal: 22,
  },
  header: {
    alignItems: 'flex-start',
    marginBottom: 24,
  },
  titleSection: {
    marginTop: 10,
    alignItems: 'center',
  },
  mainTitle: {
    fontFamily: fonts.header,
    fontSize: 24,
    fontWeight: '800',
    color: '#000000',
    letterSpacing: -0.5,
    textAlign: 'center',
  },
  subtitle: {
    fontFamily: fonts.body,
    fontSize: 13,
    lineHeight: 18,
    color: '#71717A',
    marginTop: 8,
    textAlign: 'center',
  },
  phoneHighlight: {
    fontFamily: fonts.header,
    color: '#DE8635',
    fontWeight: '600',
  },

  otpContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 14,
    marginTop: 28,
    marginBottom: 24,
  },
  otpBox: {
    width: 48,
    height: 50,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  otpBoxActive: {
    borderColor: '#DE8635',
    borderWidth: 1.5,
  },
  otpBoxFilled: {
    borderColor: '#CBD5E1',
  },
  otpDigitText: {
    fontFamily: fonts.header,
    fontSize: 20,
    fontWeight: '700',
    color: '#0F172A',
  },
  cursorBar: {
    width: 2,
    height: 18,
    backgroundColor: '#DE8635',
    borderRadius: 1,
  },

  continueButton: {
    width: '100%',
    height: 48,
    backgroundColor: '#1E2538',
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buttonDisabled: {
    opacity: 0.65,
  },
  continueButtonText: {
    fontFamily: fonts.header,
    fontSize: 15,
    fontWeight: '700',
    color: '#FFFFFF',
  },

  resendRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 18,
  },
  resendPrompt: {
    fontFamily: fonts.body,
    fontSize: 12.5,
    color: '#71717A',
  },
  resendLink: {
    fontFamily: fonts.body,
    fontSize: 12.5,
    color: '#DE8635',
    fontWeight: '700',
  },

  /* Custom Numeric Keypad */
  keypadContainer: {
    backgroundColor: '#ECEEF2',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingTop: 12,
    paddingHorizontal: 24,
  },
  keypadPreview: {
    textAlign: 'center',
    fontFamily: fonts.header,
    fontSize: 12,
    color: '#8A92A0',
    marginBottom: 6,
    fontWeight: '600',
    letterSpacing: 2,
  },
  keypadGrid: {
    gap: 8,
  },
  keypadRow: {
    flexDirection: 'row',
    gap: 10,
    justifyContent: 'center',
  },
  keypadButton: {
    flex: 1,
    height: 46,
    backgroundColor: '#FFFFFF',
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  keypadEmptySpace: {
    flex: 1,
    height: 46,
  },
  keypadKeyText: {
    fontFamily: fonts.header,
    fontSize: 19,
    fontWeight: '700',
    color: '#0F172A',
  },
});
