import { api } from './client';
import type {
  AppNotification,
  AuthSession,
  CoinRules,
  CoinTransaction,
  CoinWallet,
  Entitlements,
  Paginated,
  SubscriptionPlan,
  User,
  Vehicle,
} from './types';

/**
 * Typed API surface consumed by the app.
 *
 * Only endpoints that exist on the Laravel backend are declared here. Areas
 * blocked on provider documentation (security, dashcam, autodoc, care, finder,
 * bookings, payments) are intentionally absent — calling them would return 501,
 * and screens surface that honestly instead.
 */

interface DevicePayload {
  device_name?: string;
  platform?: 'ios' | 'android' | 'web';
  push_token?: string;
}

export const authApi = {
  register: (payload: {
    name: string;
    email: string;
    phone?: string;
    password: string;
    password_confirmation: string;
  } & DevicePayload) => api.post<AuthSession>('/auth/register', payload),

  login: (payload: { email: string; password: string } & DevicePayload) =>
    api.post<AuthSession>('/auth/login', payload),

  me: () => api.get<{ user: User; entitlements: Entitlements }>('/auth/me'),

  logout: () => api.post<{ message: string }>('/auth/logout'),

  logoutAll: () => api.post<{ message: string }>('/auth/logout-all'),
};

export const vehicleApi = {
  list: () => api.get<{ data: Vehicle[] }>('/vehicles'),

  create: (payload: Partial<Vehicle> & { plate_number: string }) =>
    api.post<{ message: string; vehicle: Vehicle }>('/vehicles', payload),

  show: (uuid: string) => api.get<{ vehicle: Vehicle }>(`/vehicles/${uuid}`),

  update: (uuid: string, payload: Partial<Vehicle>) =>
    api.patch<{ message: string; vehicle: Vehicle }>(`/vehicles/${uuid}`, payload),

  remove: (uuid: string) => api.delete<{ message: string }>(`/vehicles/${uuid}`),
};

export const subscriptionApi = {
  plans: () => api.get<{ plans: SubscriptionPlan[] }>('/subscriptions/plans'),

  me: () => api.get<{ subscription: Entitlements }>('/subscriptions/me'),
};

export const coinApi = {
  wallet: () =>
    api.get<{
      wallet: CoinWallet;
      redemption_rules: CoinRules;
      transactions: CoinTransaction[];
    }>('/coins/wallet'),
};

export const notificationApi = {
  list: (params?: { unread_only?: boolean; per_page?: number }) => {
    const query = new URLSearchParams();

    if (params?.unread_only) {
      query.set('unread_only', '1');
    }

    if (params?.per_page) {
      query.set('per_page', String(params.per_page));
    }

    const suffix = query.toString();

    return api.get<Paginated<AppNotification>>(`/notifications${suffix ? `?${suffix}` : ''}`);
  },

  markAsRead: (uuid: string) => api.post<{ message: string }>(`/notifications/${uuid}/read`),

  markAllAsRead: () => api.post<{ message: string; updated: number }>('/notifications/read-all'),
};
