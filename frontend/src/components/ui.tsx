import React from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  type RefreshControlProps,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';

import { colors, radii, spacing, toneColors, typography, type Tone } from '../theme';

/**
 * Shared UI primitives.
 *
 * Keeping these in one module means the AUTOSECURE design system is applied
 * consistently and screens stay readable.
 */

export function Screen({
  children,
  scroll = true,
  refreshControl,
}: {
  children: React.ReactNode;
  scroll?: boolean;
  refreshControl?: React.ReactElement<RefreshControlProps>;
}): React.JSX.Element {
  if (!scroll) {
    return <View style={styles.screen}>{children}</View>;
  }

  return (
    <ScrollView
      style={styles.screen}
      contentContainerStyle={styles.screenContent}
      keyboardShouldPersistTaps="handled"
      refreshControl={refreshControl}
    >
      {children}
    </ScrollView>
  );
}

export function Card({
  children,
  style,
  tone,
}: {
  children: React.ReactNode;
  style?: ViewStyle;
  tone?: Tone;
}): React.JSX.Element {
  return (
    <View
      style={[
        styles.card,
        tone === undefined ? null : { borderColor: toneColors[tone].fg + '55' },
        style,
      ]}
    >
      {children}
    </View>
  );
}

export function SectionTitle({ children }: { children: React.ReactNode }): React.JSX.Element {
  return <Text style={styles.sectionTitle}>{children}</Text>;
}

export function Heading({ children }: { children: React.ReactNode }): React.JSX.Element {
  return <Text style={styles.heading}>{children}</Text>;
}

export function Body({
  children,
  muted = false,
}: {
  children: React.ReactNode;
  muted?: boolean;
}): React.JSX.Element {
  return <Text style={[styles.body, muted ? styles.muted : null]}>{children}</Text>;
}

export function Pill({
  label,
  tone = 'neutral',
}: {
  label: string;
  tone?: Tone;
}): React.JSX.Element {
  return (
    <View style={[styles.pill, { backgroundColor: toneColors[tone].bg }]}>
      <Text style={[styles.pillText, { color: toneColors[tone].fg }]}>{label}</Text>
    </View>
  );
}

export function PrimaryButton({
  label,
  onPress,
  loading = false,
  disabled = false,
  variant = 'solid',
}: {
  label: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  variant?: 'solid' | 'ghost' | 'danger';
}): React.JSX.Element {
  const isInactive = disabled || loading;

  const background =
    variant === 'solid' ? colors.accent : variant === 'danger' ? colors.dangerSoft : 'transparent';

  const foreground =
    variant === 'solid' ? colors.textInverse : variant === 'danger' ? colors.danger : colors.text;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: isInactive, busy: loading }}
      disabled={isInactive}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: background },
        variant === 'ghost' ? styles.buttonGhost : null,
        pressed && !isInactive ? styles.buttonPressed : null,
        isInactive ? styles.buttonDisabled : null,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={foreground} size="small" />
      ) : (
        <Text style={[styles.buttonText, { color: foreground }]}>{label}</Text>
      )}
    </Pressable>
  );
}

export function TextField({
  label,
  error,
  hint,
  ...inputProps
}: TextInputProps & {
  label: string;
  error?: string | null;
  hint?: string;
}): React.JSX.Element {
  return (
    <View style={styles.field}>
      <Text style={styles.fieldLabel}>{label}</Text>

      <TextInput
        placeholderTextColor={colors.textMuted}
        style={[styles.input, error ? styles.inputError : null]}
        {...inputProps}
      />

      {hint !== undefined && error == null ? <Text style={styles.fieldHint}>{hint}</Text> : null}

      {error != null ? <Text style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

export function KeyValueRow({
  label,
  value,
}: {
  label: string;
  value: string;
}): React.JSX.Element {
  return (
    <View style={styles.kvRow}>
      <Text style={styles.kvLabel}>{label}</Text>
      <Text style={styles.kvValue}>{value}</Text>
    </View>
  );
}

export function LoadingState({ message = 'Loading…' }: { message?: string }): React.JSX.Element {
  return (
    <View style={styles.centred}>
      <ActivityIndicator color={colors.accent} />
      <Text style={styles.centredText}>{message}</Text>
    </View>
  );
}

export function EmptyState({
  title,
  message,
  actionLabel,
  onAction,
}: {
  title: string;
  message: string;
  actionLabel?: string;
  onAction?: () => void;
}): React.JSX.Element {
  return (
    <Card>
      <Text style={styles.subheading}>{title}</Text>
      <Text style={[styles.body, styles.muted]}>{message}</Text>

      {actionLabel !== undefined && onAction !== undefined ? (
        <View style={styles.actionSpacer}>
          <PrimaryButton label={actionLabel} onPress={onAction} variant="ghost" />
        </View>
      ) : null}
    </Card>
  );
}

export function ErrorState({
  message,
  onRetry,
}: {
  message: string;
  onRetry?: () => void;
}): React.JSX.Element {
  return (
    <Card tone="danger">
      <Text style={[styles.subheading, { color: colors.danger }]}>Something went wrong</Text>
      <Text style={[styles.body, styles.muted]}>{message}</Text>

      {onRetry !== undefined ? (
        <View style={styles.actionSpacer}>
          <PrimaryButton label="Try again" onPress={onRetry} variant="ghost" />
        </View>
      ) : null}
    </Card>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  screenContent: { padding: spacing.lg, paddingBottom: spacing.xxl * 2 },

  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radii.md,
    padding: spacing.lg,
    marginBottom: spacing.md,
  },

  heading: { ...typography.title, color: colors.text, marginBottom: spacing.xs },
  subheading: { ...typography.subheading, color: colors.text, marginBottom: spacing.sm },
  body: { ...typography.body, color: colors.text, lineHeight: 20 },
  muted: { color: colors.textMuted },
  sectionTitle: {
    ...typography.label,
    color: colors.textMuted,
    marginTop: spacing.lg,
    marginBottom: spacing.sm,
  },

  pill: {
    alignSelf: 'flex-start',
    borderRadius: radii.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
    marginBottom: spacing.sm,
  },
  pillText: { ...typography.label },

  button: {
    borderRadius: radii.sm,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 46,
  },
  buttonGhost: { borderWidth: 1, borderColor: colors.border },
  buttonPressed: { opacity: 0.85 },
  buttonDisabled: { opacity: 0.5 },
  buttonText: { ...typography.subheading },

  field: { marginBottom: spacing.md },
  fieldLabel: { ...typography.label, color: colors.textMuted, marginBottom: spacing.sm },
  input: {
    backgroundColor: colors.surfaceRaised,
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radii.sm,
    color: colors.text,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    ...typography.body,
  },
  inputError: { borderColor: colors.danger },
  fieldHint: { ...typography.caption, color: colors.textMuted, marginTop: spacing.xs },
  fieldError: { ...typography.caption, color: colors.danger, marginTop: spacing.xs },

  kvRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
    borderBottomColor: colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  kvLabel: { ...typography.body, color: colors.textMuted, flex: 1 },
  kvValue: { ...typography.body, color: colors.text, flex: 1, textAlign: 'right' },

  centred: { alignItems: 'center', paddingVertical: spacing.xxl },
  centredText: { ...typography.caption, color: colors.textMuted, marginTop: spacing.sm },
  actionSpacer: { marginTop: spacing.md },
});
