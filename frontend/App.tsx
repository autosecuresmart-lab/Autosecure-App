import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { AuthProvider } from './src/auth/AuthContext';
import { RootNavigator } from './src/navigation/RootNavigator';

/**
 * AUTOSECURE 2.0 mobile application.
 *
 * Phase 1 delivers a real, navigable shell over the Laravel API: session
 * handling, vehicle records, subscription entitlements, Coins and the module map.
 * Tracker, dashcam, AutoDoc, Finder, bookings and payments are surfaced as
 * pending — they depend on provider documentation and commercial sign-off, and
 * the app says so rather than showing controls that cannot work.
 */
export default function App(): React.JSX.Element {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <StatusBar style="light" />
        <RootNavigator />
      </AuthProvider>
    </SafeAreaProvider>
  );
}
