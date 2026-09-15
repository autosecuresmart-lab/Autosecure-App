import React from 'react';
import { Text, View, StyleSheet } from 'react-native';

import { ApiError } from '../../api/client';
import { useAuth } from '../../auth/AuthContext';
import { Body, Card, Heading, PrimaryButton, Screen, TextField } from '../../components/ui';
import { colors, spacing, typography } from '../../theme';

interface FieldErrors {
  [field: string]: string[] | undefined;
}

export function RegisterScreen({ onSwitchToLogin }: { onSwitchToLogin: () => void }): React.JSX.Element {
  const { signUp } = useAuth();

  const [form, setForm] = React.useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  });

  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<FieldErrors>({});
  const [isSubmitting, setIsSubmitting] = React.useState(false);

  const update = (key: keyof typeof form) => (value: string) =>
    setForm((current) => ({ ...current, [key]: value }));

  const canSubmit =
    form.name.trim() !== ''
    && form.email.trim() !== ''
    && form.password.length >= 8
    && form.password === form.password_confirmation;

  const submit = async () => {
    setError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      await signUp({
        name: form.name.trim(),
        email: form.email.trim(),
        phone: form.phone.trim() === '' ? undefined : form.phone.trim(),
        password: form.password,
        password_confirmation: form.password_confirmation,
      });
    } catch (caught) {
      if (caught instanceof ApiError) {
        const errors = caught.validationErrors;
        setFieldErrors(errors);
        setError(
          errors.name?.[0]
            ?? errors.email?.[0]
            ?? errors.phone?.[0]
            ?? errors.password?.[0]
            ?? caught.message,
        );
      } else {
        setError('Unable to create your account. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Screen>
      <View style={styles.header}>
        <Text style={styles.brandMark}>AS</Text>
        <Heading>Create your account</Heading>
        <Body muted>One account protects every vehicle you add.</Body>
      </View>

      {error !== null ? (
        <Card tone="danger">
          <Body>{error}</Body>
        </Card>
      ) : null}

      <Card>
        <TextField
          label="Full name"
          value={form.name}
          onChangeText={update('name')}
          autoComplete="name"
          placeholder="Amina Bello"
          error={fieldErrors.name?.[0] ?? null}
        />

        <TextField
          label="Email"
          value={form.email}
          onChangeText={update('email')}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          placeholder="you@example.com"
          error={fieldErrors.email?.[0] ?? null}
        />

        <TextField
          label="Phone (optional)"
          value={form.phone}
          onChangeText={update('phone')}
          keyboardType="phone-pad"
          placeholder="+234 801 234 5678"
          error={fieldErrors.phone?.[0] ?? null}
        />

        <TextField
          label="Password"
          value={form.password}
          onChangeText={update('password')}
          secureTextEntry
          autoCapitalize="none"
          placeholder="At least 8 characters"
          error={fieldErrors.password?.[0] ?? null}
        />

        <TextField
          label="Confirm password"
          value={form.password_confirmation}
          onChangeText={update('password_confirmation')}
          secureTextEntry
          autoCapitalize="none"
          placeholder="Repeat your password"
          error={
            form.password_confirmation !== '' && form.password !== form.password_confirmation
              ? 'Passwords do not match.'
              : null
          }
        />

        <PrimaryButton
          label="Create account"
          onPress={() => void submit()}
          loading={isSubmitting}
          disabled={!canSubmit}
        />
      </Card>

      <Card>
        <Body muted>Already have an account?</Body>
        <PrimaryButton label="Sign in instead" onPress={onSwitchToLogin} variant="ghost" />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { marginBottom: spacing.lg, marginTop: spacing.xl },
  brandMark: { ...typography.title, color: colors.accent, marginBottom: spacing.sm },
});
