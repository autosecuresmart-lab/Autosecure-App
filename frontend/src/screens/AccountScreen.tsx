import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import { useFocusEffect } from '@react-navigation/native';
import React from 'react';
import { Alert } from 'react-native';

import { ApiError } from '../api/client';
import { coinApi, subscriptionApi, vehicleApi } from '../api/endpoints';
import type { CoinWallet, SubscriptionPlan, Vehicle } from '../api/types';
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
  TextField,
} from '../components/ui';
import type { MainTabParamList } from '../navigation/types';

type Props = BottomTabScreenProps<MainTabParamList, 'Account'>;

/**
 * My AUTOSECURE tab: vehicle records, subscription, Coins, AutoDoc and settings.
 *
 * Real data only — vehicles, plan and Coins all come from the API. AutoDoc and
 * vehicle care are marked pending because their integrations are not supplied.
 */
export function AccountScreen(_props: Props): React.JSX.Element {
  const { user, entitlements, signOut, refreshProfile } = useAuth();

  const [vehicles, setVehicles] = React.useState<Vehicle[]>([]);
  const [plans, setPlans] = React.useState<SubscriptionPlan[]>([]);
  const [wallet, setWallet] = React.useState<CoinWallet | null>(null);

  const [isLoading, setIsLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const [isAdding, setIsAdding] = React.useState(false);
  const [newPlate, setNewPlate] = React.useState('');
  const [addError, setAddError] = React.useState<string | null>(null);
  const [isSaving, setIsSaving] = React.useState(false);

  const load = React.useCallback(async () => {
    setError(null);

    try {
      const [vehicleResponse, planResponse, walletResponse] = await Promise.all([
        vehicleApi.list(),
        subscriptionApi.plans(),
        coinApi.wallet(),
      ]);

      setVehicles(vehicleResponse.data);
      setPlans(planResponse.plans);
      setWallet(walletResponse.wallet);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'Unable to load your account.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useFocusEffect(
    React.useCallback(() => {
      void load();
      void refreshProfile().catch(() => undefined);
    }, [load, refreshProfile]),
  );

  const addVehicle = async () => {
    setAddError(null);
    setIsSaving(true);

    try {
      await vehicleApi.create({ plate_number: newPlate.trim() });

      setNewPlate('');
      setIsAdding(false);
      await load();
    } catch (caught) {
      setAddError(
        caught instanceof ApiError
          ? caught.validationErrors.plate_number?.[0] ?? caught.message
          : 'Unable to add this vehicle.',
      );
    } finally {
      setIsSaving(false);
    }
  };

  const removeVehicle = (vehicle: Vehicle) => {
    Alert.alert(
      'Remove vehicle',
      `Remove ${vehicle.display_name}? Its care records stay in the database but the vehicle will no longer appear.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Remove',
          style: 'destructive',
          onPress: () => {
            void (async () => {
              try {
                await vehicleApi.remove(vehicle.uuid);
                await load();
              } catch (caught) {
                setError(caught instanceof ApiError ? caught.message : 'Unable to remove the vehicle.');
              }
            })();
          },
        },
      ],
    );
  };

  if (isLoading && vehicles.length === 0 && wallet === null) {
    return (
      <Screen scroll={false}>
        <LoadingState message="Loading your account…" />
      </Screen>
    );
  }

  return (
    <Screen>
      <Heading>My AUTOSECURE</Heading>
      <Body muted>{user?.email ?? ''}</Body>

      {error !== null ? <ErrorState message={error} onRetry={() => void load()} /> : null}

      {/* ---------------------------------------------------------------- */}
      <SectionTitle>My vehicles</SectionTitle>

      {vehicles.length === 0 ? (
        <EmptyState
          title="No vehicles yet"
          message="Add a vehicle to link a tracker or dashcam and start care records."
          actionLabel="Add a vehicle"
          onAction={() => setIsAdding(true)}
        />
      ) : (
        vehicles.map((vehicle) => (
          <Card key={vehicle.uuid}>
            <Pill
              label={vehicle.is_primary ? 'Primary' : vehicle.status}
              tone={vehicle.is_primary ? 'accent' : 'neutral'}
            />
            <Heading>{vehicle.display_name}</Heading>
            <KeyValueRow label="Plate" value={vehicle.plate_number} />
            <KeyValueRow
              label="Mileage"
              value={
                vehicle.odometer_km === null
                  ? 'Not recorded'
                  : `${vehicle.odometer_km.toLocaleString()} km`
              }
            />
            <KeyValueRow
              label="Mileage source"
              value={vehicle.odometer_source === 'tracker' ? 'Tracker' : 'Manual entry'}
            />
            <PrimaryButton
              label="Remove vehicle"
              onPress={() => removeVehicle(vehicle)}
              variant="danger"
            />
          </Card>
        ))
      )}

      {isAdding ? (
        <Card>
          <Body>Add a vehicle</Body>

          <TextField
            label="Number plate"
            value={newPlate}
            onChangeText={setNewPlate}
            autoCapitalize="characters"
            placeholder="LAG-123-AA"
            error={addError}
          />

          <PrimaryButton
            label="Save vehicle"
            onPress={() => void addVehicle()}
            loading={isSaving}
            disabled={newPlate.trim() === ''}
          />

          <PrimaryButton
            label="Cancel"
            onPress={() => {
              setIsAdding(false);
              setAddError(null);
              setNewPlate('');
            }}
            variant="ghost"
          />
        </Card>
      ) : (
        <PrimaryButton label="Add another vehicle" onPress={() => setIsAdding(true)} variant="ghost" />
      )}

      {/* ---------------------------------------------------------------- */}
      <SectionTitle>Subscription</SectionTitle>

      <Card tone={entitlements?.is_premium === true ? 'success' : 'accent'}>
        <Pill
          label={entitlements?.is_premium === true ? 'Premium' : 'Free'}
          tone={entitlements?.is_premium === true ? 'success' : 'neutral'}
        />
        <KeyValueRow label="Status" value={entitlements?.status ?? 'expired'} />
        {entitlements?.plan !== null && entitlements?.plan !== undefined ? (
          <KeyValueRow label="Plan" value={entitlements.plan.name} />
        ) : null}
        <KeyValueRow
          label="Renews / ends"
          value={entitlements?.ends_at === null || entitlements?.ends_at === undefined
            ? '—'
            : new Date(entitlements.ends_at).toDateString()}
        />
        <Body muted>
          Tracker and dashcam security remain available whichever plan you are on.
        </Body>
      </Card>

      {plans.length > 0 ? (
        <Card>
          <Body>Available plans</Body>

          {plans.map((plan) => (
            <KeyValueRow
              key={plan.uuid}
              label={plan.name}
              value={
                plan.is_free
                  ? 'Free'
                  : `${plan.currency} ${plan.price.toLocaleString()} / ${plan.interval.replace('_', '-')}`
              }
            />
          ))}

          <Body muted>
            Plan changes are handled in Phase 3. Prices shown are the approved
            proposals and are subject to final sign-off.
          </Body>
        </Card>
      ) : null}

      {/* ---------------------------------------------------------------- */}
      <SectionTitle>Coins</SectionTitle>

      <Card>
        <KeyValueRow label="Available" value={(wallet?.balance ?? 0).toLocaleString()} />
        <KeyValueRow label="Pending" value={(wallet?.pending_balance ?? 0).toLocaleString()} />
        <KeyValueRow label="Lifetime earned" value={(wallet?.lifetime_earned ?? 0).toLocaleString()} />
        <KeyValueRow
          label="Lifetime redeemed"
          value={(wallet?.lifetime_redeemed ?? 0).toLocaleString()}
        />
        <Body muted>
          Coins are awarded after a qualifying in-app payment completes and are
          held until any dispute window closes. Redemption unlocks in Phase 6.
        </Body>
      </Card>

      {/* ---------------------------------------------------------------- */}
      <SectionTitle>AutoDoc</SectionTitle>

      <Card>
        <Pill label="Pending" tone="warning" />
        <Body muted>
          AutoDoc stays a separate application. AUTOSECURE will open the right
          vehicle in AutoDoc and show a renewal summary here once the integration
          level is agreed.
        </Body>
      </Card>

      {/* ---------------------------------------------------------------- */}
      <SectionTitle>Session</SectionTitle>

      <Card>
        <Body muted>
          The token for this device is stored in the device keychain, never in
          plain storage.
        </Body>
        <PrimaryButton label="Sign out" onPress={() => void signOut()} variant="ghost" />
      </Card>
    </Screen>
  );
}
