/**
 * Navigation types.
 *
 * The five tabs follow the recommended main navigation in the AUTOSECURE 2.0
 * proposal: Home, Security, Camera, Finder, My AUTOSECURE.
 */

export type MainTabParamList = {
  Home: undefined;
  Security: undefined;
  Finder: undefined;
  Analytics: undefined;
  Camera?: undefined;
  /** "My AUTOSECURE" — user profile, settings, preferences. */
  Account: undefined;
};

export type RootStackParamList = {
  MainTabs: undefined;
  VehicleDetail: {
    vehicleName?: string;
    vehicleUuid?: string;
    location?: string;
    recentUpdate?: string;
    odometerKm?: number;
    plateNumber?: string;
  };
  EventReport: undefined;
  ViewEventReport: {
    eventId?: string;
    title?: string;
    timestamp?: string;
    vehicleName?: string;
    location?: string;
  } | undefined;
  Notifications: undefined;
  ViewNotification: {
    id?: string;
    title?: string;
    subtitle?: string;
    time?: string;
    type?: 'online' | 'offline' | 'alert' | 'event';
    read?: boolean;
    vehicleName?: string;
  } | undefined;
  NotificationSettings: undefined;
  Settings: undefined;
  PrivacyPolicy: undefined;
  HelpCentre: undefined;
  AutoDoc: {
    vehicleName?: string;
    vehicleUuid?: string;
  } | undefined;
  CoinsWallet: undefined;
  DashcamLive: {
    vehicleName?: string;
    vehicleUuid?: string;
    deviceUuid?: string;
  } | undefined;
  FavoriteCars: undefined;
  BookingCheckout: {
    vendorName?: string;
    vendorUuid?: string;
    serviceTitle?: string;
    serviceUuid?: string;
    price?: number;
  } | undefined;
  ActiveTheftIncident: {
    incidentId?: string;
    vehicleName?: string;
    reportedTime?: string;
    location?: string;
  } | undefined;
  ShareIncident: {
    incidentId?: string;
    vehicleName?: string;
  } | undefined;
  IncidentAlerts: undefined;
  IncidentHistory: undefined;
  CameraModal: undefined;
  TripPlayback: {
    vehicleUuid?: string;
    vehicleName?: string;
    plateNumber?: string;
  } | undefined;
  SubscriptionPlans: undefined;
  PaymentHistory: undefined;
};

export type AuthScreenName =
  | 'login'
  | 'register'
  | 'forgot_password'
  | 'verify_otp'
  | 'set_new_password';

