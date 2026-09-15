import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import React from 'react';

import { PendingIntegration } from '../components/PendingIntegration';
import { Body, Card, Heading, Pill, Screen, SectionTitle } from '../components/ui';
import type { MainTabParamList } from '../navigation/types';
import { useAuth } from '../auth/AuthContext';

type Props = BottomTabScreenProps<MainTabParamList, 'Camera'>;

/**
 * Camera tab — the embedded dashcam experience.
 *
 * The proposal keeps the existing dashcam tab as the entry point and embeds the
 * functions already demonstrated in the supplied dashcam screens. The server
 * contract for those functions is documented (see PENDING-INFORMATION.md) but
 * cannot be implemented until the provider SDK/API is supplied.
 */
export function CameraScreen(_props: Props): React.JSX.Element {
  const { can } = useAuth();

  return (
    <Screen>
      <Heading>Camera</Heading>
      <Body muted>Live video, recordings, emergencies and device controls.</Body>

      <SectionTitle>Included on your plan</SectionTitle>

      <Card>
        <Pill
          label={can('dashcam.live') ? '✓ Dashcam access included' : '· Dashcam access'}
          tone={can('dashcam.live') ? 'success' : 'neutral'}
        />
        <Body muted>
          Dashcam access is included on every plan, including Free.
        </Body>
      </Card>

      <SectionTitle>Dashcam experience</SectionTitle>

      <Card>
        <Body>The following functions are planned and come from the supplied dashcam screens:</Body>

        {[
          'Live video with front / rear-cabin switching',
          'Vehicle location, timestamp and speed alongside the stream',
          'Playback library browsable by date and time',
          'Protected emergency recordings listed separately',
          'In-app snapshot from the active camera',
          'Album of loop recordings, events, snapshots and clips',
          'Find my car with navigation context',
          'Geofence and trajectory shortcuts',
          'Device controls: name, network, sharing, SD card, restart, unbind',
          'Pairing by QR code, barcode or manual entry',
        ].map((item) => (
          <Body key={item}>• {item}</Body>
        ))}
      </Card>

      <SectionTitle>Integration status</SectionTitle>

      <PendingIntegration moduleKey="dashcam" />

      <Card>
        <Body muted>
          Streaming credentials and permanent device tokens will be exchanged on
          the server and never stored in the app.
        </Body>
      </Card>
    </Screen>
  );
}
