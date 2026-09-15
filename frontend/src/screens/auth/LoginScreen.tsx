import React from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { ApiError } from '../../api/client';
import { useAuth } from '../../auth/AuthContext';
import { Body, Card, Heading, PrimaryButton, Screen, TextField } from '../../components/ui';
import { colors, spacing, typography } from '../../theme';

export function LoginScreen({ onSwitchToRegister }: { onSwitchToRegister: () => void }): React.JSX.Element {
  const { signIn } = useAuth();

  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string[]>>({});
  const [isSubmitting, setIsSubmitting] = React.useState(false);

  const submit = async () => {
    setError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      await signIn({ email: email.trim(), password });
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors(caught.validationErrors);
        setError(caught.validationErrors.email?.[0] ?? caught.message);
      } else {
        setError('Unable to sign in. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Screen>
      <View style={styles.header}>
        <Text style={styles.brandMark}>AS</Text>
        <Heading>AUTOSECURE</Heading>
        <Body muted>Protect your vehicle, remember its care, find trusted services.</Body>
      </View>

      {error !== null ? (
        <Card tone="danger">
          <Body>{error}</Body>
        </Card>
      ) : null}

      <Card>
        <TextField
          label="Email"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          placeholder="you@example.com"
          error={fieldErrors.email?.[0] ?? null}
        />

        <TextField
          label="Password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoCapitalize="none"
          autoComplete="password"
          placeholder="Your password"
          error={fieldErrors.password?.[0] ?? null}
        />

        <PrimaryButton
          label="Sign in"
          onPress={() => void submit()}
          loading={isSubmitting}
          disabled={email.trim() === '' || password === ''}
        />
      </Card>

      <Card>
        <Body muted>New to AUTOSECURE?</Body>
        <PrimaryButton label="Create an account" onPress={onSwitchToRegister} variant="ghost" />
      </Card>

      <Body muted>
        Your essential tracker and dashcam security stays available on the free plan.
      </Body>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { marginBottom: spacing.lg, marginTop: spacing.xxl },
  brandMark: {
    ...typography.title,
    color: colors.accent,
    marginBottom: spacing.sm,
  },
});
