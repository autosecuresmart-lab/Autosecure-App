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
  brand: string | null;
  model: string | null;
  serial_number: string;
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
  status: 'active' | 'inactive' | 'archived';
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
