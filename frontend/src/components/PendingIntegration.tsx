import React from 'react';

import { api } from '../api/client';
import { ApiError } from '../api/client';
import type { ApiErrorBody } from '../api/types';
import { PENDING_INTEGRATIONS, type PendingIntegrationKey } from '../config/env';
import { Body, Card, KeyValueRow, Pill, PrimaryButton } from './ui';

/**
 * Honest placeholder for a module that cannot be built yet.
 *
 * It does not fake controls. It states what the module will do and lists the
 * exact documentation the backend is waiting for, fetched live from the API
 * (the 501 response carries `blocked_by`). If the API is unreachable the card
 * still renders with the local summary.
 */
export function PendingIntegration({
  moduleKey,
}: {
  moduleKey: PendingIntegrationKey;
}): React.JSX.Element {
  const integration = PENDING_INTEGRATIONS[moduleKey];

  const [blockedBy, setBlockedBy] = React.useState<string[]>([]);
  const [isChecking, setIsChecking] = React.useState(true);

  const load = React.useCallback(async () => {
    setIsChecking(true);

    try {
      await api.get(`/${moduleKey}`);

      // Reaching here means the backend is no longer returning 501, i.e. the
      // integration has been implemented and this screen needs replacing.
      setBlockedBy(['This module is now available on the server. Update the app.']);
    } catch (error) {
      if (error instanceof ApiError && error.isIntegrationPending) {
        const body = error.body as ApiErrorBody;
        setBlockedBy(Array.isArray(body.blocked_by) ? body.blocked_by : []);
      } else {
        setBlockedBy([]);
      }
    } finally {
      setIsChecking(false);
    }
  }, [moduleKey]);

  React.useEffect(() => {
    void load();
  }, [load]);

  return (
    <>
      <Card tone="warning">
        <Pill label="Pending provider documentation" tone="warning" />
        <Body>{integration.summary}</Body>
      </Card>

      <Card>
        <Body muted>
          This module is not built yet because AUTOSECURE has not supplied the
          integration details it depends on. Rather than show controls that cannot
          work, the app shows you exactly what is outstanding.
        </Body>
      </Card>

      {isChecking ? (
        <Card>
          <Body muted>Checking the API for the current blocker list…</Body>
        </Card>
      ) : blockedBy.length > 0 ? (
        <Card>
          <Body>Still required from AUTOSECURE / the provider:</Body>

          <React.Fragment>
            {blockedBy.map((item) => (
              <KeyValueRow key={item} label="•" value={item} />
            ))}
          </React.Fragment>

          <PrimaryButton label="Check again" onPress={() => void load()} variant="ghost" />
        </Card>
      ) : null}
    </>
  );
}
