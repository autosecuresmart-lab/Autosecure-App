import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import { useFocusEffect } from '@react-navigation/native';
import React from 'react';
import { RefreshControl } from 'react-native';

import { ApiError } from '../api/client';
import { vehicleApi } from '../api/endpoints';
import type { Vehicle } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import {
  Body,
  Card,
  EmptyState,
  ErrorState,
  Heading,
  KeyValueRow,
  LoadingState,
  Pill,
  PrimaryButton,
  Screen,
  SectionTitle,
} from '../components/ui';
import type { MainTabParamList } from '../navigation/types';
import { colors } from '../theme';

type Props = BottomTabScreenProps<MainTabParamList, 'Home'>;

/**
 * Home tab: vehicle status, urgent alerts, next maintenance items and shortcuts.
 *
 * Phase 1 shows what the platform can actually prove: the customer's vehicles
 * and their device bindings. Care reminders and security alerts appear once
 * Phase 3 (Vehicle Care) and Phase 2 (tracker integration) land.
 */
export function HomeScreen({ navigation }: Props): React.JSX.Element {
  const { user, entitlements } = useAuth();

  const [vehicles, setVehicles] = React.useState<Vehicle[]>([]);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isRefreshing, setIsRefreshing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  const load = React.useCallback(async (refresh = false) => {
    if (refresh) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }

    setError(null);

    try {
      const response = await vehicleApi.list();
      setVehicles(response.data);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Unable to load your vehicles.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useFocusEffect(
    React.useCallback(() => {
      void load();
    }, [load]),
  );

  if (isLoading && vehicles.length === 0) {
    return (
      <Screen scroll={false}>
        <LoadingState message="Loading your vehicles…" />
      </Screen>
    );
  }

  const primary = vehicles.find((vehicle) => vehicle.is_primary) ?? vehicles[0] ?? null;

  const offlineDevices = vehicles
    .flatMap((vehicle) => vehicle.devices ?? [])
    .filter((device) => !device.is_online);

  return (
    <Screen
      refreshControl={
        <RefreshControl
          refreshing={isRefreshing}
          onRefresh={() => void load(true)}
          tintColor={colors.accent}
          colors={[colors.accent]}
        />
      }
    >
      <React.Fragment>
        <Heading>Hello{user?.name ? `, ${user.name.split(' ')[0]}` : ''}</Heading>
        <Body muted>{vehicles.length === 1 ? '1 vehicle' : `${vehicles.length} vehicles`} protected by AUTOSECURE</Body>

        {error !== null ? <ErrorState message={error} onRetry={() => void load()} /> : null}

        {primary === null ? (
          <EmptyState
            title="Add your first vehicle"
            message="Add a vehicle to link a tracker or dashcam, start care records and use Finder."
            actionLabel="Add a vehicle"
            onAction={() => navigation.navigate('Account')}
          />
        ) : (
          <>
            <SectionTitle>Primary vehicle</SectionTitle>

            <Card>
              <Pill
                label={primary.status === 'active' ? 'Active' : primary.status}
                tone={primary.status === 'active' ? 'success' : 'neutral'}
              />
              <Heading>{primary.display_name}</Heading>
              <KeyValueRow label="Plate" value={primary.plate_number} />
              {primary.make !== null || primary.model !== null ? (
                <KeyValueRow
                  label="Vehicle"
                  value={[primary.make, primary.model, primary.year]
                    .filter((part): part is string | number => part !== null)
                    .join(' ')}
                />
              ) : null}
              <KeyValueRow
                label="Mileage"
                value={
                  primary.odometer_km === null
                    ? 'Not recorded'
                    : `${primary.odometer_km.toLocaleString()} km (${primary.odometer_source})`
                }
              />
              <KeyValueRow
                label="Devices"
                value={
                  (primary.devices ?? []).length === 0
                    ? 'None bound yet'
                    : (primary.devices ?? [])
                        .map((device) => `${device.type}${device.is_online ? '' : ' (offline)'}`)
                        .join(', ')
                }
              />

              <PrimaryButton
                label="Open My AUTOSECURE"
                onPress={() => navigation.navigate('Account')}
                variant="ghost"
              />
            </Card>

            <SectionTitle>Alerts</SectionTitle>

            {offlineDevices.length === 0 ? (
              <Card tone="success">
                <Body>No device problems reported.</Body>
              </Card>
            ) : (
              <Card tone="warning">
                <Body>
                  {offlineDevices.length === 1
                    ? '1 device is reporting offline.'
                    : `${offlineDevices.length} devices are reporting offline.`}
                </Body>
              </Card>
            )}

            <SectionTitle>Your plan</SectionTitle>

            <Card tone={entitlements?.is_premium === true ? 'success' : 'accent'}>
              <Pill
                label={entitlements?.is_premium === true ? 'Premium' : 'Free'}
                tone={entitlements?.is_premium === true ? 'success' : 'neutral'}
              />
              <Body>
                {entitlements?.is_premium === true
                  ? 'Vehicle care, service history and AutoDoc are unlocked.'
                  : 'Tracker and dashcam security are included. Premium adds vehicle care, service history and AutoDoc.'}
              </Body>
              {entitlements?.is_premium !== true ? (
                <PrimaryButton
                  label="Compare plans"
                  onPress={() => navigation.navigate('Account')}
                  variant="ghost"
                />
              ) : null}
            </Card>

            <SectionTitle>Shortcuts</SectionTitle>

            <Card>
              <PrimaryButton
                label="Security & tracker"
                onPress={() => navigation.navigate('Security')}
                variant="ghost"
              />
              <PrimaryButton
                label="Dashcam"
                onPress={() => navigation.navigate('Camera')}
                variant="ghost"
              />
              <PrimaryButton
                label="Find a service"
                onPress={() => navigation.navigate('Finder')}
                variant="ghost"
              />
            </Card>
          </>
        )}
      </React.Fragment>
    </Screen>
  );
}
