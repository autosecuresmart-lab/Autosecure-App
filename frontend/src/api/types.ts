/**
 * API payload types.
 *
 * These mirror the Laravel API resources. Note that every entity is addressed
 * by `uuid`; the numeric id is never sent to the client.
 */

export type ApiErrorCode =
  | 'validation_error'
  | 'unauthenticated'
  | 'account_inactive'
  | 'vehicle_forbidden'
  | 'vehicle_capability_denied'
  | 'forbidden'
  | 'premium_required'
  | 'integration_pending'
  | 'rate_limited'
  | 'network_error'
  | 'server_error';

export interface ApiErrorBody {
  message?: string;
  code?: ApiErrorCode;
  errors?: Record<string, string[]>;
  required_features?: string[];
  blocked_by?: string[];
  module?: string;
  [key: string]: unknown;
}

export interface User {
  uuid: string;
  name: string;
  email: string;
  phone: string | null;
  account_type: 'individual' | 'business';
  company_name: string | null;
  avatar_url: string | null;
  status: 'active' | 'pending' | 'suspended' | 'closed';
  email_verified: boolean;
  phone_verified: boolean;
  locale: string;
  timezone: string;
  created_at: string | null;
}

export type SubscriptionStatus =
  | 'pending'
  | 'active'
  | 'grace'
  | 'expired'
  | 'cancelled'
  | 'failed';

export interface Entitlements {
  is_premium: boolean;
  status: SubscriptionStatus;
  plan: {
    uuid: string;
    name: string;
    slug: string;
    interval: string;
    price: string | number;
    currency: string;
  } | null;
  starts_at: string | null;
  ends_at: string | null;
  grace_ends_at: string | null;
  auto_renew: boolean;
  features: string[];
}

export interface AuthSession {
  token: string;
  user: User;
  entitlements: Entitlements;
}

export interface Device {
  uuid: string;
  type: 'tracker' | 'dashcam';
  label?: string | null;
  brand: string | null;
  model: string | null;
  serial_number: string;
  imei?: string | null;
  firmware_version: string | null;
  status: 'pending' | 'active' | 'offline' | 'suspended' | 'faulty' | 'unbound';
  is_online: boolean;
  bound_at: string | null;
  last_seen_at: string | null;
  last_known_position: { latitude: number; longitude: number } | null;
  capabilities: Record<string, boolean | null> | null;
}

export interface Vehicle {
  uuid: string;
  nickname: string | null;
  display_name: string;
  plate_number: string;
  make: string | null;
  model: string | null;
  year: number | null;
  colour: string | null;
  vin: string | null;
  fuel_type: string | null;
  transmission: string | null;
  image_url: string | null;
  odometer_km: number | null;
  odometer_source: 'manual' | 'tracker';
  odometer_updated_at: string | null;
  is_primary: boolean;
  is_immobilized?: boolean;
  status: 'active' | 'inactive' | 'archived';
  telemetry?: {
    speed_kph: number;
    battery_level: number;
    address: string;
    latitude: number;
    longitude: number;
    heading?: number;
    satellites?: number;
    gsm_signal?: number;
    ignition: boolean;
    is_online: boolean;
    is_moving: boolean;
    last_heartbeat: string;
    recorded_at?: string | null;
  } | null;
  devices?: Device[];
  created_at: string | null;
}

export interface SubscriptionPlan {
  uuid: string;
  name: string;
  slug: string;
  description: string | null;
  price: number;
  base_price: number | null;
  currency: string;
  interval: 'monthly' | 'half_yearly' | 'yearly' | 'custom';
  duration_days: number;
  discount_percent: number;
  is_free: boolean;
  features: string[];
}

export interface CoinWallet {
  uuid: string;
  balance: number;
  pending_balance: number;
  lifetime_earned: number;
  lifetime_redeemed: number;
  lifetime_expired: number;
}

export interface CoinTransaction {
  uuid: string;
  type: 'earn' | 'pending' | 'redeem' | 'reverse' | 'expire' | 'adjustment';
  coins: number;
  balance_after: number;
  description: string | null;
  expires_at: string | null;
  created_at: string | null;
}

export interface CoinRules {
  naira_per_coin: number;
  earn_rate_percent: number;
  min_redemption: number;
  max_percent_payable: number;
  expiry_days: number;
  transferable: boolean;
}

export interface AppNotification {
  uuid: string;
  type: string;
  category: string | null;
  title: string;
  body: string | null;
  channel: 'in_app' | 'push' | 'email' | 'sms';
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string | null;
}

export interface Paginated<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    unread?: number;
  };
}

export interface VehicleLocation {
  latitude: number;
  longitude: number;
  speed_kph: number;
  heading: number;
  altitude_m?: number;
  accuracy_m?: number;
  ignition: boolean;
  moving: boolean;
  source: string;
  recorded_at: string;
  battery_level?: number;
  gsm_signal?: number;
  address?: string;
}

export interface VehicleStatusTelemetry {
  is_online: boolean;
  battery_percentage: number;
  voltage: number;
  ignition_on: boolean;
  relay_cut: boolean;
  gsm_signal: number;
  satellites: number;
  last_heartbeat: string | null;
}

export interface PlaybackPoint {
  latitude: number;
  longitude: number;
  speed_kph: number;
  heading: number;
  ignition: boolean;
  recorded_at: string;
}

export interface TripItem {
  id: string;
  start_time: string;
  end_time: string;
  distance_km: number;
  duration_minutes: number;
  max_speed_kph: number;
  start_address: string;
  end_address: string;
}

export interface TheftEvent {
  uuid: string;
  status: 'open' | 'acknowledged' | 'resolved' | 'false_alarm';
  severity: string;
  triggered_at: string;
  resolved_at?: string | null;
  resolution_note?: string | null;
  is_open: boolean;
  last_known_location?: {
    latitude: number;
    longitude: number;
  };
  live_telemetry?: VehicleLocation;
  available_actions?: Record<string, boolean>;
}

export interface SecurityCommandRecord {
  uuid: string;
  type: string;
  status: 'pending' | 'sent' | 'acknowledged' | 'failed' | 'timeout' | 'cancelled';
  is_confirmed: boolean;
  is_pending?: boolean;
  requested_at?: string;
  acknowledged_at?: string;
  failure_reason?: string | null;
}

export interface DashcamStreamSession {
  stream_url: string;
  protocol: string;
  camera: 'front' | 'cabin' | 'rear';
  expires_at: string;
  token?: string;
}

export interface DashcamRecording {
  id: string;
  url: string | null;
  recorded_at: string;
  duration_seconds: number | null;
  size_bytes: number | null;
  camera: 'front' | 'cabin' | 'rear';
  is_emergency: boolean;
  thumbnail_url: string | null;
  trigger_type?: string;
}

export interface DashcamSnapshot {
  id: string;
  photo_url: string | null;
  camera: 'front' | 'cabin' | 'rear';
  captured_at: string;
  size_bytes: number | null;
}

export interface DashcamStatus {
  uuid: string;
  serial_number: string;
  model: string;
  is_online: boolean;
  firmware_version?: string;
  network?: {
    type: string;
    operator: string;
    signal_bars: number;
    ip_address?: string;
  };
  sd_card?: {
    status: string;
    total_gb: number;
    used_gb: number;
    free_gb: number;
    write_speed_class?: string;
  };
  camera_channels?: Record<string, { status: string; resolution: string; fps: number }>;
  bound_vehicle?: {
    uuid: string;
    display_name: string;
    plate_number: string;
  } | null;
}

export interface MaintenanceRecord {
  id?: number;
  uuid: string;
  vehicle_id?: number;
  category: string;
  title: string;
  description: string | null;
  performed_at: string;
  odometer_km: number | null;
  workshop_name: string | null;
  cost: number;
  currency: string;
  details?: Record<string, unknown> | null;
  attachments?: string[] | null;
  notes?: string | null;
  next_due_at?: string | null;
  next_due_odometer_km?: number | null;
  reminder_status?: string | null;
  vendor?: {
    id: number;
    name: string;
  } | null;
  reminders?: MaintenanceReminder[];
}

export interface MaintenanceReminder {
  id?: number;
  uuid: string;
  category: string;
  title: string;
  due_at: string | null;
  due_odometer_km: number | null;
  status: 'pending' | 'due_soon' | 'due' | 'overdue' | 'dismissed' | 'completed';
  notified_at?: string | null;
  dismissed_at?: string | null;
  completed_at?: string | null;
  maintenance_record?: {
    id: number;
    title: string;
    category: string;
  } | null;
}

export interface FuelRecord {
  id?: number;
  uuid: string;
  filled_at: string;
  litres: number;
  price_per_litre: number;
  total_amount: number;
  odometer_km: number | null;
  is_full_tank: boolean;
  station: string | null;
  notes?: string | null;
}

export interface CareTimelineItem {
  id: string;
  type: 'maintenance' | 'fuel' | 'reminder';
  category: string;
  title: string;
  description: string;
  cost: number;
  currency: string;
  litres?: number;
  odometer_km?: number | null;
  workshop?: string | null;
  status?: string;
  timestamp: string;
  date: string;
}

export interface VehicleCareDashboard {
  vehicle: {
    uuid: string;
    display_name: string;
    plate_number: string;
    odometer_km: number | null;
    odometer_source: 'manual' | 'tracker';
    odometer_updated_at: string | null;
  };
  metrics: {
    total_maintenance_cost: number;
    total_services_count: number;
    total_fuel_litres: number;
    total_fuel_cost: number;
    average_cost_per_litre: number;
  };
  reminders_summary: {
    total_outstanding: number;
    overdue_count: number;
    due_count: number;
    due_soon_count: number;
    pending_count: number;
  };
  active_reminders: Array<{
    uuid: string;
    category: string;
    title: string;
    due_at: string | null;
    due_odometer_km: number | null;
    status: 'pending' | 'due_soon' | 'due' | 'overdue' | 'dismissed' | 'completed';
  }>;
  recent_maintenance: Array<{
    uuid: string;
    category: string;
    title: string;
    performed_at: string;
    odometer_km: number | null;
    cost: number;
    currency: string;
    workshop_name: string | null;
  }>;
  recent_fuel: Array<{
    uuid: string;
    filled_at: string;
    litres: number;
    total_amount: number;
    price_per_litre: number;
    station: string | null;
    odometer_km: number | null;
  }>;
}

export interface AutoDocLaunchSession {
  launch_token: string;
  deep_link: string;
  web_url: string;
  vehicle_ref: string;
  expires_at: string;
  expires_in_seconds: number;
  app_store_urls: {
    ios: string;
    android: string;
  };
}

export interface AutoDocDocument {
  id: string;
  type: string;
  title: string;
  issuer: string;
  document_number: string;
  issued_at: string;
  expires_at: string | null;
  status: 'valid' | 'expiring_soon' | 'expired';
  days_remaining: number | null;
  renewal_available: boolean;
}

export interface AutoDocDocumentSummary {
  vehicle: {
    uuid: string;
    display_name: string;
    plate_number: string;
    autodoc_vehicle_ref: string;
  };
  summary: {
    total_documents: number;
    valid_count: number;
    expiring_soon_count: number;
    expired_count: number;
    overall_status: 'compliant' | 'renewal_due_soon' | 'action_required';
  };
  documents: AutoDocDocument[];
}

export interface DiagnosticFaultCode {
  id: string;
  code: string;
  title: string;
  severity: 'low' | 'medium' | 'high';
  system: string;
  description: string;
  recommendation: string;
}

export interface AutoDocDiagnosticsPayload {
  vehicle_uuid: string;
  health_score: number;
  health_summary: string;
  protocol: string;
  ecu_status: string;
  last_scanned_at: string;
  telemetry: {
    battery_voltage: { value: number; unit: string; status: string };
    coolant_temp: { value: number; unit: string; status: string };
    oil_life_percent: { value: number; unit: string; status: string };
    fuel_trim_st: { value: number; unit: string; status: string };
  };
  fault_codes_count: number;
  fault_codes: DiagnosticFaultCode[];
}

export interface FinderCategory {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  description: string | null;
  icon: string | null;
  vendors_count: number;
}

export interface FinderServiceItem {
  uuid: string;
  name: string;
  description?: string | null;
  type?: string;
  price: number;
  currency: string;
  duration_minutes?: number;
}

export interface VendorReview {
  uuid: string;
  rating: number;
  title: string | null;
  comment: string;
  user_name: string;
  is_verified_booking: boolean;
  created_at: string;
  vendor_reply?: string | null;
  vendor_replied_at?: string | null;
}

export interface FinderVendor {
  id: number;
  uuid: string;
  business_name: string;
  trading_name?: string | null;
  slug: string;
  category?: {
    id: number;
    uuid: string;
    name: string;
    slug: string;
  } | null;
  description?: string | null;
  logo_path?: string | null;
  address_line?: string | null;
  city?: string | null;
  state?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  rating_average: number;
  rating_count: number;
  is_verified: boolean;
  opening_hours?: Record<string, unknown> | null;
  services_preview?: FinderServiceItem[];
  starting_price?: number | null;
  services?: FinderServiceItem[];
  recent_reviews?: VendorReview[];
}

export interface BookingRecord {
  id: number;
  uuid: string;
  reference: string;
  type: string;
  status: 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled';
  payment_status: 'unpaid' | 'pending' | 'paid' | 'refunded';
  fulfilment: string;
  scheduled_at: string;
  completed_at?: string | null;
  cancelled_at?: string | null;
  cancellation_reason?: string | null;
  subtotal: number;
  discount: number;
  total: number;
  currency: string;
  customer_note?: string | null;
  vendor_note?: string | null;
  vendor?: {
    uuid: string;
    business_name: string;
    trading_name?: string | null;
    logo_path?: string | null;
    phone?: string | null;
    city?: string | null;
    state?: string | null;
    address_line?: string | null;
    rating_average?: number;
  } | null;
  vehicle?: {
    uuid: string;
    make?: string | null;
    model?: string | null;
    year?: number | null;
    plate_number: string;
    color?: string | null;
  } | null;
  items_count?: number;
  items?: Array<{
    uuid?: string;
    name: string;
    description?: string | null;
    quantity: number;
    unit_price: number;
    line_total: number;
  }>;
  review?: VendorReview | null;
  has_reviewed?: boolean;
  created_at: string;
}

export interface CreateBookingPayload {
  vendor_uuid: string;
  vehicle_uuid?: string;
  scheduled_at: string;
  fulfilment?: 'in_store' | 'mobile' | 'delivery' | 'pickup';
  customer_note?: string;
  items: Array<{
    service_uuid: string;
    quantity?: number;
  }>;
}

export type PaymentStatus =
  | 'pending'
  | 'processing'
  | 'successful'
  | 'failed'
  | 'reversed'
  | 'refunded';

export type PaymentPurpose =
  | 'subscription'
  | 'booking'
  | 'order'
  | 'vendor_subscription';

export interface PaymentRecord {
  id?: number;
  uuid: string;
  reference: string;
  purpose: PaymentPurpose;
  amount: number;
  currency: string;
  status: PaymentStatus;
  channel?: string | null;
  gateway?: string | null;
  gateway_reference?: string | null;
  paid_at?: string | null;
  failed_at?: string | null;
  failure_reason?: string | null;
  booking?: {
    uuid: string;
    reference: string;
    status: string;
    vendor_name?: string | null;
  } | null;
  subscription?: {
    uuid: string;
    status: string;
    plan_name?: string | null;
  } | null;
  created_at: string;
}

export interface PaymentInitializePayload {
  purpose: PaymentPurpose;
  plan_uuid?: string;
  booking_uuid?: string;
  channel?: 'card' | 'bank_transfer' | 'ussd' | 'qr' | 'wallet';
  idempotency_key?: string;
  callback_url?: string;
}

export interface PaymentInitializeResponse {
  status: string;
  payment: PaymentRecord;
  checkout?: {
    reference: string;
    gateway_reference?: string;
    checkout_url?: string | null;
    access_code?: string | null;
    channel?: string;
    instructions?: string;
  };
}

export interface SubscriptionHistoryItem {
  uuid: string;
  reference: string;
  amount: number;
  currency: string;
  status: PaymentStatus;
  channel?: string | null;
  paid_at?: string | null;
  plan_name: string;
  duration_days: number;
}

export interface SubscriptionHistoryResponse {
  current?: {
    uuid: string;
    status: string;
    plan_name?: string | null;
    plan_slug?: string | null;
    price: number;
    starts_at?: string | null;
    ends_at?: string | null;
    grace_ends_at?: string | null;
    auto_renew: boolean;
    has_access: boolean;
  } | null;
  history: SubscriptionHistoryItem[];
}






