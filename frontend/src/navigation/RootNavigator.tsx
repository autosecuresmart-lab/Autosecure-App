import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { DarkTheme, NavigationContainer, type Theme } from '@react-navigation/native';
import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

import { useAuth } from '../auth/AuthContext';
import { colors, radii, spacing, typography } from '../theme';
import { AccountScreen } from '../screens/AccountScreen';
import { CameraScreen } from '../screens/CameraScreen';
import { FinderScreen } from '../screens/FinderScreen';
import { HomeScreen } from '../screens/HomeScreen';
import { SecurityScreen } from '../screens/SecurityScreen';
import { LoginScreen } from '../screens/auth/LoginScreen';
import { RegisterScreen } from '../screens/auth/RegisterScreen';
import type { AuthScreenName, MainTabParamList } from './types';

const Tab = createBottomTabNavigator<MainTabParamList>();

const navigationTheme: Theme = {
  ...DarkTheme,
  colors: {
    ...DarkTheme.colors,
    background: colors.background,
    card: colors.surface,
    text: colors.text,
    border: colors.border,
    primary: colors.accent,
    notification: colors.accent,
  },
};

/**
 * Tab glyphs.
 *
 * Drawn as text so the app does not need an icon-font dependency for the
 * Phase 1 navigable shell. Swap for the branded icon set when the design system
 * assets are exported from Figma.
 */
const TAB_GLYPHS: Record<keyof MainTabParamList, string> = {
  Home: '⌂',
  Security: '⛨',
  Camera: '◉',
  Finder: '⌕',
  Account: '☰',
};

function TabGlyph({ route, focused }: { route: keyof MainTabParamList; focused: boolean }): React.JSX.Element {
  return (
    <Text
      style={[
        styles.tabGlyph,
        { color: focused ? colors.accent : colors.textMuted },
      ]}
    >
      {TAB_GLYPHS[route]}
    </Text>
  );
}

function MainTabs(): React.JSX.Element {
  return (
    <Tab.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: colors.surface },
        headerTitleStyle: { ...typography.subheading, color: colors.text },
        headerTintColor: colors.text,
        tabBarStyle: {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
          height: 62,
          paddingBottom: spacing.sm,
          paddingTop: spacing.xs,
        },
        tabBarActiveTintColor: colors.accent,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarLabelStyle: { fontSize: 11, fontWeight: '600' },
      }}
    >
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          title: 'Home',
          tabBarIcon: ({ focused }) => <TabGlyph route="Home" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Security"
        component={SecurityScreen}
        options={{
          title: 'Security',
          tabBarIcon: ({ focused }) => <TabGlyph route="Security" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Camera"
        component={CameraScreen}
        options={{
          title: 'Camera',
          tabBarIcon: ({ focused }) => <TabGlyph route="Camera" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Finder"
        component={FinderScreen}
        options={{
          title: 'Finder',
          tabBarIcon: ({ focused }) => <TabGlyph route="Finder" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Account"
        component={AccountScreen}
        options={{
          title: 'My AUTOSECURE',
          tabBarLabel: 'My AUTOSECURE',
          tabBarIcon: ({ focused }) => <TabGlyph route="Account" focused={focused} />,
        }}
      />
    </Tab.Navigator>
  );
}

function Splash(): React.JSX.Element {
  return (
    <View style={styles.splash}>
      <Text style={styles.splashMark}>AS</Text>
      <Text style={styles.splashName}>AUTOSECURE</Text>
      <ActivityIndicator color={colors.accent} style={styles.splashSpinner} />
    </View>
  );
}

function AuthFlow(): React.JSX.Element {
  const [screen, setScreen] = React.useState<AuthScreenName>('login');

  return screen === 'login' ? (
    <LoginScreen onSwitchToRegister={() => setScreen('register')} />
  ) : (
    <RegisterScreen onSwitchToLogin={() => setScreen('login')} />
  );
}

/**
 * Root of the app.
 *
 * Signed out: the auth flow (no navigator needed — it is a two-screen toggle).
 * Signed in: the five AUTOSECURE tabs.
 */
export function RootNavigator(): React.JSX.Element {
  const { isRestoring, isAuthenticated } = useAuth();

  if (isRestoring) {
    return <Splash />;
  }

  if (!isAuthenticated) {
    return <AuthFlow />;
  }

  return (
    <NavigationContainer theme={navigationTheme}>
      <MainTabs />
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  tabGlyph: { fontSize: 20, lineHeight: 24 },

  splash: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.background,
  },
  splashMark: {
    ...typography.title,
    color: colors.accent,
    backgroundColor: colors.accentSoft,
    borderRadius: radii.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    overflow: 'hidden',
  },
  splashName: { ...typography.subheading, color: colors.text, marginTop: spacing.md },
  splashSpinner: { marginTop: spacing.xl },
});
