import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import React from 'react';

import { PendingIntegration } from '../components/PendingIntegration';
import { Body, Card, Heading, Pill, Screen, SectionTitle } from '../components/ui';
import type { MainTabParamList } from '../navigation/types';
import { useAuth } from '../auth/AuthContext';

type Props = BottomTabScreenProps<MainTabParamList, 'Finder'>;

/**
 * Finder tab — verified nearby vendors, search, booking and payment.
 *
 * The customer journey and vendor rules are approved (proposal sections 07 and
 * 08) but the commercial figures are not, so nothing is wired up yet. Browsing
 * and paying in-app are free-tier features, which is reflected below from the
 * server-resolved entitlement list.
 */
export function FinderScreen(_props: Props): React.JSX.Element {
  const { can } = useAuth();

  const customerJourney = [
    'Select a vehicle, then the service or product you need.',
    'Search verified vendors and filter by category, location, rating or price.',
    'Open a vendor profile: verification status, services, prices and policies.',
    'Choose a service and a date, then confirm the booking or order.',
    'Pay inside the app with the supported payment options.',
    'Get confirmation, status updates, a receipt and Coins once it qualifies.',
    'Rate the completed service or report a problem.',
  ];

  return (
    <Screen>
      <Heading>Finder</Heading>
      <Body muted>Verified parts sellers, car washes and mechanics near you.</Body>

      <SectionTitle>Included on your plan</SectionTitle>

      <Card>
        {[
          { key: 'finder.browse', label: 'Browse verified vendors' },
          { key: 'finder.book', label: 'Book services and order parts' },
          { key: 'finder.pay', label: 'Pay vendors inside the app' },
          { key: 'coins.earn', label: 'Earn Coins on qualifying payments' },
        ].map((item) => (
          <Pill
            key={item.key}
            label={`${can(item.key) ? '✓' : '·'} ${item.label}`}
            tone={can(item.key) ? 'success' : 'neutral'}
          />
        ))}
        <Body muted>Marketplace access is included on every plan, including Free.</Body>
      </Card>

      <SectionTitle>How booking will work</SectionTitle>

      <Card>
        {customerJourney.map((step, index) => (
          <Body key={step}>
            {index + 1}. {step}
          </Body>
        ))}
      </Card>

      <SectionTitle>Vendor categories</SectionTitle>

      <Card>
        <Pill label="Auto Parts Sellers" tone="info" />
        <Body muted>
          Search by part or category, vehicle compatibility, location, availability and price.
        </Body>

        <Pill label="Car Washes" tone="info" />
        <Body muted>
          Service packages, location, opening hours, price and bookable slots.
        </Body>

        <Pill label="Mechanics" tone="info" />
        <Body muted>
          Search by specialty, supported vehicle types, location and availability.
        </Body>
      </Card>

      <SectionTitle>Status</SectionTitle>

      <PendingIntegration moduleKey="finder" />

      <Card tone="warning">
        <Body muted>
          Only vendors that pass identity and business verification, and hold a
          current subscription, will be listed or able to take a booking.
        </Body>
      </Card>
    </Screen>
  );
}
