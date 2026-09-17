import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { NavigationContainer, useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp, NativeStackScreenProps } from '@react-navigation/native-stack';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React, { useEffect, useState } from 'react';
import { Platform, StyleSheet } from 'react-native';

import { useAuth } from '../auth/AuthContext';
import {
  readOnboardingCompleted,
  saveOnboardingCompleted,
} from '../auth/sessionStore';
import {
  TabChartIcon,
  TabHomeIcon,
  TabMapIcon,
  TabPieIcon,
  TabProfileIcon,
} from '../components/HomeIcons';
import { AccountScreen } from '../screens/AccountScreen';
import { ActiveTheftIncidentScreen } from '../screens/ActiveTheftIncidentScreen';
import { AnalyticsScreen } from '../screens/AnalyticsScreen';
import { AutoDocScreen } from '../screens/AutoDocScreen';
import { BookingCheckoutScreen } from '../screens/BookingCheckoutScreen';
import { CameraScreen } from '../screens/CameraScreen';
import { CoinsWalletScreen } from '../screens/CoinsWalletScreen';
import { DashcamLiveScreen } from '../screens/DashcamLiveScreen';
import { EventReportScreen } from '../screens/EventReportScreen';
import { FavoriteCarsScreen } from '../screens/FavoriteCarsScreen';
import { FinderScreen } from '../screens/FinderScreen';
import { HelpCentreScreen } from '../screens/HelpCentreScreen';
import { HomeScreen } from '../screens/HomeScreen';
import { IncidentAlertsScreen } from '../screens/IncidentAlertsScreen';
import { IncidentHistoryScreen } from '../screens/IncidentHistoryScreen';
import { NotificationsScreen } from '../screens/NotificationsScreen';
import { NotificationSettingsScreen } from '../screens/NotificationSettingsScreen';
import { PaymentHistoryScreen } from '../screens/PaymentHistoryScreen';
import { PrivacyPolicyScreen } from '../screens/PrivacyPolicyScreen';
import { SecurityScreen } from '../screens/SecurityScreen';
import { SettingsScreen } from '../screens/SettingsScreen';
import { ShareIncidentScreen } from '../screens/ShareIncidentScreen';
import { SubscriptionPlansScreen } from '../screens/SubscriptionPlansScreen';
import { TripPlaybackScreen } from '../screens/TripPlaybackScreen';
import { VehicleDetailScreen } from '../screens/VehicleDetailScreen';
import { ViewEventReportScreen } from '../screens/ViewEventReportScreen';
import { ViewNotificationScreen } from '../screens/ViewNotificationScreen';
import { ForgotPasswordScreen } from '../screens/auth/ForgotPasswordScreen';
import { LoginScreen } from '../screens/auth/LoginScreen';
import { RegisterScreen } from '../screens/auth/RegisterScreen';
import { SetNewPasswordScreen } from '../screens/auth/SetNewPasswordScreen';
import { VerifyOtpScreen } from '../screens/auth/VerifyOtpScreen';
import { OnboardingScreen } from '../screens/onboarding/OnboardingScreen';
import { SplashScreen } from '../screens/onboarding/SplashScreen';
import type { AuthScreenName, MainTabParamList, RootStackParamList } from './types';

const Tab = createBottomTabNavigator<MainTabParamList>();
const Stack = createNativeStackNavigator<RootStackParamList>();

type RootNavProp = NativeStackNavigationProp<RootStackParamList>;

function MainTabs(): React.JSX.Element {
  const rootNavigation = useNavigation<RootNavProp>();

  return (
    <Tab.Navigator
      safeAreaInsets={{ bottom: 0, top: 0, left: 0, right: 0 }}
      screenOptions={{
        headerShown: false,
        tabBarShowLabel: false,
        tabBarStyle: styles.floatingTabBar,
        tabBarItemStyle: styles.tabBarItem,
        tabBarIconStyle: styles.tabBarIcon,
      }}
    >
      <Tab.Screen
        name="Home"
        children={({ navigation }) => (
          <HomeScreen
            onNavigateToCamera={() => rootNavigation.navigate('CameraModal')}
            onNavigateToMap={() => navigation.navigate('Security')}
            onNavigateToAccount={() => navigation.navigate('Account')}
            onNavigateToVehicleDetail={(v) =>
              rootNavigation.navigate('VehicleDetail', {
                vehicleName: v.name,
                vehicleUuid: v.id,
                location: v.location,
                recentUpdate: v.recentUpdate,
                odometerKm: v.odometerKm,
                plateNumber: v.plateNumber,
              })
            }
            onNavigateToEventReport={() => rootNavigation.navigate('EventReport')}
            onNavigateToNotifications={() => rootNavigation.navigate('Notifications')}
          />
        )}
        options={{
          tabBarIcon: ({ focused }) => <TabHomeIcon active={focused} size={22} />,
        }}
      />
      <Tab.Screen
        name="Security"
        component={SecurityScreen}
        options={{
          tabBarIcon: ({ focused }) => <TabMapIcon active={focused} size={22} />,
        }}
      />
      <Tab.Screen
        name="Finder"
        children={({ navigation }) => (
          <FinderScreen
            onBack={() => navigation.navigate('Home')}
            onNavigateToBooking={(params) =>
              rootNavigation.navigate('BookingCheckout', params)
            }
          />
        )}
        options={{
          tabBarIcon: ({ focused }) => <TabChartIcon active={focused} size={22} />,
        }}
      />
      <Tab.Screen
        name="Analytics"
        children={({ navigation }) => (
          <AnalyticsScreen
            onBack={() => navigation.navigate('Home')}
            onNavigateToVehicles={() => navigation.navigate('Home')}
            onNavigateToMaintenance={() =>
              rootNavigation.navigate('AutoDoc', { vehicleName: 'Toyota Corolla (ABC-123DE)' })
            }
          />
        )}
        options={{
          tabBarIcon: ({ focused }) => <TabPieIcon active={focused} size={22} />,
        }}
      />
      <Tab.Screen
        name="Account"
        children={({ navigation }) => (
          <AccountScreen
            onBack={() => navigation.navigate('Home')}
            onNavigateToSettings={() => rootNavigation.navigate('Settings')}
            onNavigateToFavoriteCars={() => rootNavigation.navigate('FavoriteCars')}
            onNavigateToCoins={() => rootNavigation.navigate('CoinsWallet')}
            onNavigateToSubscriptions={() => rootNavigation.navigate('SubscriptionPlans')}
            onNavigateToPaymentHistory={() => rootNavigation.navigate('PaymentHistory')}
            onNavigateToPrivacyPolicy={() => rootNavigation.navigate('PrivacyPolicy')}
            onNavigateToHelpCentre={() => rootNavigation.navigate('HelpCentre')}
            onNavigateToNotificationSettings={() => rootNavigation.navigate('NotificationSettings')}
            onNavigateToNotifications={() => rootNavigation.navigate('Notifications')}
          />
        )}
        options={{
          tabBarIcon: ({ focused }) => <TabProfileIcon active={focused} size={22} />,
        }}
      />
    </Tab.Navigator>
  );
}

function VehicleDetailScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'VehicleDetail'>): React.JSX.Element {
  return (
    <VehicleDetailScreen
      vehicleName={route.params?.vehicleName}
      vehicleUuid={route.params?.vehicleUuid}
      location={route.params?.location}
      recentUpdate={route.params?.recentUpdate}
      odometerKm={route.params?.odometerKm}
      plateNumber={route.params?.plateNumber}
      onBack={() => navigation.goBack()}
      onNavigateToActiveTheftIncident={(incidentId) =>
        navigation.navigate('ActiveTheftIncident', {
          incidentId,
          vehicleName: route.params?.vehicleName,
          location: route.params?.location,
        })
      }
      onNavigateToPlayback={() =>
        navigation.navigate('TripPlayback', {
          vehicleUuid: route.params?.vehicleUuid,
          vehicleName: route.params?.vehicleName,
          plateNumber: route.params?.plateNumber,
        })
      }
      onNavigateToLiveTrack={() => navigation.navigate('MainTabs', { screen: 'Security' } as any)}
      onNavigateTab={(tab) => navigation.navigate('MainTabs', { screen: tab } as any)}
    />
  );
}

function TripPlaybackScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'TripPlayback'>): React.JSX.Element {
  return (
    <TripPlaybackScreen
      vehicleUuid={route.params?.vehicleUuid}
      vehicleName={route.params?.vehicleName}
      plateNumber={route.params?.plateNumber}
      onBack={() => navigation.goBack()}
    />
  );
}

function EventReportScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'EventReport'>): React.JSX.Element {
  return (
    <EventReportScreen
      onBack={() => navigation.goBack()}
      onSelectEvent={(eventId) =>
        navigation.navigate('ViewEventReport', { eventId })
      }
    />
  );
}

function ViewEventReportScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'ViewEventReport'>): React.JSX.Element {
  return (
    <ViewEventReportScreen
      eventId={route.params?.eventId}
      title={route.params?.title}
      timestamp={route.params?.timestamp}
      vehicleName={route.params?.vehicleName}
      location={route.params?.location}
      onBack={() => navigation.goBack()}
    />
  );
}

function NotificationsScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'Notifications'>): React.JSX.Element {
  return (
    <NotificationsScreen
      onBack={() => navigation.goBack()}
      onSelectNotification={(item) =>
        navigation.navigate('ViewNotification', {
          id: item.id,
          title: item.title,
          subtitle: item.subtitle,
          time: item.time,
          type: item.type,
          read: item.read,
          vehicleName: item.vehicleName,
        })
      }
    />
  );
}

function ViewNotificationScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'ViewNotification'>): React.JSX.Element {
  return (
    <ViewNotificationScreen
      id={route.params?.id}
      title={route.params?.title}
      subtitle={route.params?.subtitle}
      time={route.params?.time}
      type={route.params?.type}
      read={route.params?.read}
      vehicleName={route.params?.vehicleName}
      onBack={() => navigation.goBack()}
      onNavigateToMap={() => navigation.navigate('MainTabs', { screen: 'Security' } as any)}
    />
  );
}

function NotificationSettingsScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'NotificationSettings'>): React.JSX.Element {
  return <NotificationSettingsScreen onBack={() => navigation.goBack()} />;
}

function SettingsScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'Settings'>): React.JSX.Element {
  return (
    <SettingsScreen
      onBack={() => navigation.goBack()}
      onNavigateToNotificationSettings={() => navigation.navigate('NotificationSettings')}
      onNavigateToPrivacyPolicy={() => navigation.navigate('PrivacyPolicy')}
    />
  );
}

function PrivacyPolicyScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'PrivacyPolicy'>): React.JSX.Element {
  return <PrivacyPolicyScreen onBack={() => navigation.goBack()} />;
}

function HelpCentreScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'HelpCentre'>): React.JSX.Element {
  return <HelpCentreScreen onBack={() => navigation.goBack()} />;
}

function ActiveTheftIncidentScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'ActiveTheftIncident'>): React.JSX.Element {
  return (
    <ActiveTheftIncidentScreen
      incidentId={route.params?.incidentId}
      vehicleName={route.params?.vehicleName}
      reportedTime={route.params?.reportedTime}
      location={route.params?.location}
      onBack={() => navigation.goBack()}
      onNavigateToShare={() =>
        navigation.navigate('ShareIncident', {
          incidentId: route.params?.incidentId,
          vehicleName: route.params?.vehicleName,
        })
      }
      onNavigateToAlerts={() => navigation.navigate('IncidentAlerts')}
      onNavigateToHistory={() => navigation.navigate('IncidentHistory')}
    />
  );
}

function ShareIncidentScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'ShareIncident'>): React.JSX.Element {
  return (
    <ShareIncidentScreen
      incidentId={route.params?.incidentId}
      vehicleName={route.params?.vehicleName}
      onBack={() => navigation.goBack()}
      onSendSuccess={() => navigation.goBack()}
    />
  );
}

function IncidentAlertsScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'IncidentAlerts'>): React.JSX.Element {
  return (
    <IncidentAlertsScreen
      onBack={() => navigation.goBack()}
      onSelectAlert={() => navigation.navigate('ActiveTheftIncident')}
    />
  );
}

function IncidentHistoryScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'IncidentHistory'>): React.JSX.Element {
  return (
    <IncidentHistoryScreen
      onBack={() => navigation.goBack()}
      onSelectIncident={(incidentId) =>
        navigation.navigate('ActiveTheftIncident', { incidentId })
      }
    />
  );
}

function CameraModalScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'CameraModal'>): React.JSX.Element {
  return <CameraScreen onClose={() => navigation.goBack()} />;
}

function AutoDocScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'AutoDoc'>): React.JSX.Element {
  return (
    <AutoDocScreen
      vehicleName={route.params?.vehicleName}
      vehicleUuid={route.params?.vehicleUuid}
      onBack={() => navigation.goBack()}
      onNavigateToFinder={() => navigation.navigate('MainTabs')}
    />
  );
}

function CoinsWalletScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'CoinsWallet'>): React.JSX.Element {
  return (
    <CoinsWalletScreen
      onBack={() => navigation.goBack()}
      onNavigateToFinder={() => navigation.navigate('MainTabs')}
    />
  );
}

function DashcamLiveScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'DashcamLive'>): React.JSX.Element {
  return (
    <DashcamLiveScreen
      vehicleName={route.params?.vehicleName}
      vehicleUuid={route.params?.vehicleUuid}
      deviceUuid={route.params?.deviceUuid}
      onBack={() => navigation.goBack()}
    />
  );
}

function FavoriteCarsScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'FavoriteCars'>): React.JSX.Element {
  return (
    <FavoriteCarsScreen
      onBack={() => navigation.goBack()}
      onNavigateToMap={() => navigation.navigate('MainTabs', { screen: 'Security' } as any)}
      onNavigateToVehicleDetail={(vehicleName) =>
        navigation.navigate('VehicleDetail', { vehicleName })
      }
    />
  );
}

function BookingCheckoutScreenWrapper({
  route,
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'BookingCheckout'>): React.JSX.Element {
  return (
    <BookingCheckoutScreen
      vendorName={route.params?.vendorName}
      vendorUuid={route.params?.vendorUuid}
      serviceTitle={route.params?.serviceTitle}
      serviceUuid={route.params?.serviceUuid}
      price={route.params?.price}
      onBack={() => navigation.goBack()}
      onBookingComplete={() => navigation.navigate('MainTabs')}
    />
  );
}

function SubscriptionPlansScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'SubscriptionPlans'>): React.JSX.Element {
  return (
    <SubscriptionPlansScreen
      onBack={() => navigation.goBack()}
      onNavigateToPaymentHistory={() => navigation.navigate('PaymentHistory')}
    />
  );
}

function PaymentHistoryScreenWrapper({
  navigation,
}: NativeStackScreenProps<RootStackParamList, 'PaymentHistory'>): React.JSX.Element {
  return (
    <PaymentHistoryScreen
      onBack={() => navigation.goBack()}
      onNavigateToSubscription={() => navigation.navigate('SubscriptionPlans')}
    />
  );
}

function AppStack(): React.JSX.Element {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name="MainTabs" component={MainTabs} />
      <Stack.Screen name="VehicleDetail" component={VehicleDetailScreenWrapper} />
      <Stack.Screen name="EventReport" component={EventReportScreenWrapper} />
      <Stack.Screen name="ViewEventReport" component={ViewEventReportScreenWrapper} />
      <Stack.Screen name="Notifications" component={NotificationsScreenWrapper} />
      <Stack.Screen name="ViewNotification" component={ViewNotificationScreenWrapper} />
      <Stack.Screen name="NotificationSettings" component={NotificationSettingsScreenWrapper} />
      <Stack.Screen name="Settings" component={SettingsScreenWrapper} />
      <Stack.Screen name="PrivacyPolicy" component={PrivacyPolicyScreenWrapper} />
      <Stack.Screen name="HelpCentre" component={HelpCentreScreenWrapper} />
      <Stack.Screen name="AutoDoc" component={AutoDocScreenWrapper} />
      <Stack.Screen name="CoinsWallet" component={CoinsWalletScreenWrapper} />
      <Stack.Screen name="DashcamLive" component={DashcamLiveScreenWrapper} />
      <Stack.Screen name="FavoriteCars" component={FavoriteCarsScreenWrapper} />
      <Stack.Screen name="BookingCheckout" component={BookingCheckoutScreenWrapper} />
      <Stack.Screen name="SubscriptionPlans" component={SubscriptionPlansScreenWrapper} />
      <Stack.Screen name="PaymentHistory" component={PaymentHistoryScreenWrapper} />
      <Stack.Screen name="ActiveTheftIncident" component={ActiveTheftIncidentScreenWrapper} />
      <Stack.Screen name="ShareIncident" component={ShareIncidentScreenWrapper} />
      <Stack.Screen name="TripPlayback" component={TripPlaybackScreenWrapper} />
      <Stack.Screen name="IncidentAlerts" component={IncidentAlertsScreenWrapper} />
      <Stack.Screen name="IncidentHistory" component={IncidentHistoryScreenWrapper} />
      <Stack.Screen name="CameraModal" component={CameraModalScreenWrapper} />
    </Stack.Navigator>
  );
}

function AuthFlow(): React.JSX.Element {
  const [screen, setScreen] = React.useState<AuthScreenName>('login');
  const [resetIdentifier, setResetIdentifier] = React.useState('+234******00');

  switch (screen) {
    case 'login':
      return (
        <LoginScreen
          onSwitchToRegister={() => setScreen('register')}
          onForgotPassword={() => setScreen('forgot_password')}
        />
      );

    case 'register':
      return <RegisterScreen onSwitchToLogin={() => setScreen('login')} />;

    case 'forgot_password':
      return (
        <ForgotPasswordScreen
          onContinue={(identifier) => {
            setResetIdentifier(identifier);
            setScreen('verify_otp');
          }}
          onBackToLogin={() => setScreen('login')}
        />
      );

    case 'verify_otp':
      return (
        <VerifyOtpScreen
          phoneOrEmail={resetIdentifier}
          onVerified={() => setScreen('set_new_password')}
          onBackToLogin={() => setScreen('login')}
        />
      );

    case 'set_new_password':
      return (
        <SetNewPasswordScreen
          onPasswordResetSuccess={() => setScreen('login')}
          onBackToLogin={() => setScreen('login')}
        />
      );

    default:
      return (
        <LoginScreen
          onSwitchToRegister={() => setScreen('register')}
          onForgotPassword={() => setScreen('forgot_password')}
        />
      );
  }
}

/**
 * Root of the app.
 *
 * 1. Splash screen: White background with centered AutoSecure shield logo.
 * 2. Onboarding: 2 interactive slides for new users.
 * 3. Signed out: Login / Register flow.
 * 4. Signed in: Main AUTOSECURE interface with root stack & floating-pill bottom tabs.
 */
export function RootNavigator(): React.JSX.Element {
  const { isRestoring, isAuthenticated } = useAuth();
  const [showSplash, setShowSplash] = useState(true);
  const [hasCompletedOnboarding, setHasCompletedOnboarding] = useState<boolean | null>(null);

  useEffect(() => {
    let cancelled = false;
    const checkOnboarding = async () => {
      const completed = await readOnboardingCompleted();
      if (!cancelled) {
        setHasCompletedOnboarding(completed);
      }
    };
    void checkOnboarding();
    return () => {
      cancelled = true;
    };
  }, []);

  const handleFinishSplash = () => {
    setShowSplash(false);
  };

  const handleCompleteOnboarding = async () => {
    await saveOnboardingCompleted();
    setHasCompletedOnboarding(true);
  };

  if (isRestoring || showSplash || hasCompletedOnboarding === null) {
    return <SplashScreen onFinish={handleFinishSplash} duration={2000} />;
  }

  if (!isAuthenticated) {
    if (!hasCompletedOnboarding) {
      return <OnboardingScreen onComplete={handleCompleteOnboarding} />;
    }
    return <AuthFlow />;
  }

  return (
    <NavigationContainer>
      <AppStack />
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  floatingTabBar: {
    position: 'absolute',
    bottom: Platform.OS === 'ios' ? 32 : 24,
    left: 20,
    right: 20,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#1C222B',
    borderTopWidth: 0,
    elevation: 10,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 10,
    paddingHorizontal: 8,
    paddingTop: 10,
    paddingBottom: 0,
  },
  tabBarItem: {
    height: 60,
    padding: 0,
    margin: 0,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabBarIcon: {
    width: 24,
    height: 24,
    alignItems: 'center',
    justifyContent: 'center',
    margin: 0,
    padding: 0,
  },
});
