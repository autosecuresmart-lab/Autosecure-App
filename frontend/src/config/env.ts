import { Platform } from 'react-native';

/**
 * Runtime configuration.
 *
 * EXPO_PUBLIC_* variables are inlined at build time by Expo and are therefore
 * NOT for secrets. Anything sensitive (device provider credentials, payment
 * keys) must stay on the Laravel backend and never reach the app.
 */

/**
 * The Android emulator reaches the host machine on 10.0.2.2, not localhost.
 */
const localHost = Platform.select({
  android: 'http://10.0.2.2:8000',
  default: 'http://localhost:8000',
});

const rawBaseUrl = process.env.EXPO_PUBLIC_API_URL ?? `${localHost}/api/v1`;

export const API_BASE_URL = rawBaseUrl.replace(/\/+$/, '');

export const API_VERSION = 'v1';

/** Milliseconds before a request is aborted. */
export const API_TIMEOUT_MS = Number(process.env.EXPO_PUBLIC_API_TIMEOUT_MS ?? 20000);

export const APP_ENV = process.env.EXPO_PUBLIC_APP_ENV ?? 'development';

export const IS_PRODUCTION = APP_ENV === 'production';

/**
 * Feature areas that are blocked until AUTOSECURE supplies provider
 * documentation. The backend answers HTTP 501 for these, and the app says so
 * plainly instead of showing controls that cannot work.
 *
 * Keep in sync with App\Http\Controllers\Api\V1\PendingController.
 */
export const PENDING_INTEGRATIONS = {
  security: {
    title: 'Tracker & Security',
    summary: 'Live location, trip playback, call vehicle and remote shutdown.',
  },
  dashcam: {
    title: 'Dashcam',
    summary: 'Live video, playback, emergencies, snapshots and device controls.',
  },
  autodoc: {
    title: 'AutoDoc',
    summary: 'Vehicle documentation and renewal reminders from the AutoDoc app.',
  },
  care: {
    title: 'Vehicle Care',
    summary: 'Service history, reminders, mileage and fuel insights.',
  },
  finder: {
    title: 'Finder',
    summary: 'Verified parts sellers, car washes and mechanics near you.',
  },
  bookings: {
    title: 'Bookings & Orders',
    summary: 'Book a service, track it and pay in the app.',
  },
  payments: {
    title: 'Payments',
    summary: 'Subscriptions, bookings and refunds.',
  },
} as const;

export type PendingIntegrationKey = keyof typeof PENDING_INTEGRATIONS;
