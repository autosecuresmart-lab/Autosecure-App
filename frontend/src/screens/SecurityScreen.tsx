import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import React from 'react';

import { PendingIntegration } from '../components/PendingIntegration';
import { Body, Card, Heading, Pill, Screen, SectionTitle } from '../components/ui';
import type { MainTabParamList } from '../navigation/types';
import { useAuth } from '../auth/AuthContext';

type Props = BottomTabScreenProps<MainTabParamList, 'Security'>;

/**
 * Security tab — tracker controls, theft trigger, live location, playback.
 *
 * Nothing here is stubbed to look finished. The free-tier entitlement list is
 * real (resolved by the server), and the feature list below is what Phase 2
 * delivers once the tracker API is supplied.
 */
export function SecurityScreen(_props: Props): React.JSX.Element {
  const { can } = useAuth();

  const freeFeatures = [
    { key: 'security.live_location', label: 'Live location' },
    { key: 'security.playback', label: 'Route & trip playback' },
    { key: 'security.remote_shutdown', label: 'Remote shutdown' },
    { key: 'security.call_vehicle', label: 'Call vehicle' },
    { key: 'security.theft_trigger', label: 'Theft trigger' },
  ];

  return (
    <Screen>
      <Heading>Security</Heading>
      <Body muted>Tracker controls, theft response and live location.</Body>

      <SectionTitle>Included on your plan</SectionTitle>

      <Card>
        <Body muted>
          These are part of every AUTOSECURE plan, including Free, and stay
          available even if a Premium subscription lapses.
        </Body>

        {freeFeatures.map((feature) => (
          <Pill
            key={feature.key}
            label={`${can(feature.key) ? '✓' : '·'} ${feature.label}`}
            tone={can(feature.key) ? 'success' : 'neutral'}
          />
        ))}
      </Card>

      <SectionTitle>Vehicle security</SectionTitle>

      <PendingIntegration moduleKey="security" />

      <SectionTitle>Theft trigger workflow</SectionTitle>

      <Card>
        <Body muted>
          The approved workflow is being built exactly as specified:
        </Body>

        {[
          '1. Trigger the theft alert from the selected vehicle.',
          '2. Confirm with PIN or biometrics.',
          '3. See live location, dashcam shortcut and call-vehicle shortcut.',
          '4. Issue authorised controls, including remote shutdown.',
          '5. Every trigger, command and response is recorded.',
          '6. Close the event with a resolution note.',
        ].map((step) => (
          <Body key={step}>{step}</Body>
        ))}

        <Body muted>
          Remote shutdown will never be shown as successful until the device
          confirms it. Until then the command stays pending.
        </Body>
      </Card>
    </Screen>
  );
}
