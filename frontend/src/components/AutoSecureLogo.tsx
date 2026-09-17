import {
  Image,
  StyleSheet,
  Text,
  View,
  type ImageStyle,
  type StyleProp,
  type ViewStyle,
} from 'react-native';

import { fonts } from '../theme';

const LOGO_IMAGE = require('../../assets/images/logo.png');

interface AutoSecureLogoProps {
  size?: number;
  style?: StyleProp<ViewStyle>;
  imageStyle?: StyleProp<ImageStyle>;
  theme?: 'light' | 'dark';
}

/**
 * Full AutoSecure Logo Image
 */
export function AutoSecureLogo({
  size = 125,
  style,
  imageStyle,
}: AutoSecureLogoProps): React.JSX.Element {
  return (
    <View style={[styles.container, style]}>
      <Image
        source={LOGO_IMAGE}
        style={[
          styles.logoImage,
          {
            width: size,
            height: size,
          },
          imageStyle,
        ]}
        resizeMode="contain"
      />
    </View>
  );
}

/**
 * Circular White Header Badge with Shield + "autoSecure" text
 * Pixel-perfect match for the header on Login, OTP, and Reset Password screens
 */
export function HeaderBrandLogo({
  style,
  theme = 'light',
}: {
  style?: StyleProp<ViewStyle>;
  theme?: 'light' | 'dark';
}): React.JSX.Element {
  return (
    <View style={[styles.headerRow, style]}>
      {/* Circular Badge */}
      <View style={styles.badgeCircle}>
        <Image
          source={LOGO_IMAGE}
          style={styles.badgeImage}
          resizeMode="contain"
        />
      </View>

      {/* Brand Name Text: auto (dark) Secure (orange) */}
      <View style={styles.brandTextRow}>
        <Text
          style={[
            styles.brandAutoText,
            theme === 'dark' ? styles.brandTextDark : styles.brandTextLight,
          ]}
        >
          auto
        </Text>
        <Text style={styles.brandSecureText}>Secure</Text>
      </View>
    </View>
  );
}

/**
 * Circular White Badge for Onboarding screens
 */
export function LogoBadge({
  size = 64,
  style,
}: {
  size?: number;
  style?: StyleProp<ViewStyle>;
}): React.JSX.Element {
  return (
    <View
      style={[
        styles.badgeContainer,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
        },
        style,
      ]}
    >
      <Image
        source={LOGO_IMAGE}
        style={{
          width: size * 0.96,
          height: size * 0.96,
        }}
        resizeMode="contain"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoImage: {
    alignSelf: 'center',
  },

  /* Header Brand Logo (Badge + Text Row) */
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  badgeCircle: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    marginRight: 10,
    overflow: 'hidden',
  },
  badgeImage: {
    width: 34,
    height: 34,
  },
  brandTextRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
  },
  brandAutoText: {
    fontFamily: fonts.header,
    fontSize: 20,
    fontWeight: '800',
    fontStyle: 'italic',
    letterSpacing: -0.4,
  },
  brandTextLight: {
    color: '#0F172A',
  },
  brandTextDark: {
    color: '#FFFFFF',
  },
  brandSecureText: {
    fontFamily: fonts.header,
    fontSize: 20,
    fontWeight: '800',
    fontStyle: 'italic',
    color: '#DE8635',
    letterSpacing: -0.4,
  },

  badgeContainer: {
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.16,
    shadowRadius: 8,
    elevation: 5,
  },
});
