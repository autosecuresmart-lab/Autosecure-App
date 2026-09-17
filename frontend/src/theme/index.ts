import { Platform } from 'react-native';

/**
 * AUTOSECURE design tokens.
 *
 * Single place for colour, spacing and type so the existing Figma design can be
 * matched without hunting through screens. The visual language is intentionally
 * the same as the /manage portal so the ecosystem reads as one product.
 */

export const colors = {
  background: '#0B1220',
  surface: '#121B2D',
  surfaceRaised: '#17223A',
  border: '#24304A',

  text: '#E6EBF5',
  textMuted: '#93A1BD',
  textInverse: '#12100C',

  accent: '#FF7A1A',
  accentSoft: 'rgba(255, 122, 26, 0.12)',

  success: '#35C48A',
  successSoft: 'rgba(53, 196, 138, 0.12)',

  warning: '#F4B740',
  warningSoft: 'rgba(244, 183, 64, 0.12)',

  danger: '#F2585B',
  dangerSoft: 'rgba(242, 88, 91, 0.12)',

  info: '#4EA8F5',
  infoSoft: 'rgba(78, 168, 245, 0.12)',
} as const;

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  xxl: 32,
} as const;

export const radii = {
  sm: 8,
  md: 12,
  lg: 16,
  pill: 999,
} as const;

export const fonts = {
  header: Platform.select({
    ios: 'Helvetica Neue',
    android: 'Helvetica',
    default: 'Helvetica, "Helvetica Neue", Arial, sans-serif',
  }),
  body: Platform.select({
    ios: 'Aeonik',
    android: 'Aeonik',
    default: 'Aeonik, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
  }),
} as const;

export const typography = {
  title: { fontFamily: fonts.header, fontSize: 24, fontWeight: '700' },
  heading: { fontFamily: fonts.header, fontSize: 18, fontWeight: '700' },
  subheading: { fontFamily: fonts.header, fontSize: 15, fontWeight: '600' },
  body: { fontFamily: fonts.body, fontSize: 14, fontWeight: '400' },
  caption: { fontFamily: fonts.body, fontSize: 12, fontWeight: '400' },
  label: { fontFamily: fonts.header, fontSize: 11, fontWeight: '700', letterSpacing: 0.6, textTransform: 'uppercase' },
} as const;

/**
 * Status tone used by pills and banners.
 */
export type Tone = 'neutral' | 'accent' | 'success' | 'warning' | 'danger' | 'info';

export const toneColors: Record<Tone, { fg: string; bg: string }> = {
  neutral: { fg: colors.textMuted, bg: colors.surfaceRaised },
  accent: { fg: colors.accent, bg: colors.accentSoft },
  success: { fg: colors.success, bg: colors.successSoft },
  warning: { fg: colors.warning, bg: colors.warningSoft },
  danger: { fg: colors.danger, bg: colors.dangerSoft },
  info: { fg: colors.info, bg: colors.infoSoft },
};
