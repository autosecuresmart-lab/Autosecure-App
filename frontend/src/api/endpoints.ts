import { api } from './client';
import type {
  AppNotification,
  AuthSession,
  CoinRules,
  CoinTransaction,
  CoinWallet,
  Entitlements,
  Paginated,
  PaymentInitializePayload,
  PaymentInitializeResponse,
  PaymentRecord,
  SubscriptionHistoryResponse,
  SubscriptionPlan,
  User,
  Vehicle,
} from './types';

/**
 * Typed API surface consumed by the app.
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

  sessions: () => api.get<{ sessions: unknown[] }>('/auth/sessions'),

  revokeSession: (tokenUuid: string) => api.delete(`/auth/sessions/${tokenUuid}`),
};

export const vehicleApi = {
  list: () => api.get<{ data: Vehicle[] }>('/vehicles'),

  create: (payload: Partial<Vehicle> & { plate_number: string }) =>
    api.post<{ message: string; vehicle: Vehicle }>('/vehicles', payload),

  show: (uuid: string) => api.get<{ vehicle: Vehicle }>(`/vehicles/${uuid}`),

  update: (uuid: string, payload: Partial<Vehicle>) =>
    api.patch<{ message: string; vehicle: Vehicle }>(`/vehicles/${uuid}`, payload),

  remove: (uuid: string) => api.delete<{ message: string }>(`/vehicles/${uuid}`),

  devices: (uuid: string) => api.get<{ data: import('./types').Device[] }>(`/vehicles/${uuid}/devices`),
};

export const deviceApi = {
  list: (params?: { type?: 'tracker' | 'dashcam'; vehicle?: string }) => {
    const query = new URLSearchParams();
    if (params?.type) query.set('type', params.type);
    if (params?.vehicle) query.set('vehicle', params.vehicle);
    const suffix = query.toString();
    return api.get<{
      data: import('./types').Device[];
      meta: { total: number; trackers: number; dashcams: number };
    }>(`/devices${suffix ? `?${suffix}` : ''}`);
  },

  show: (uuid: string) => api.get<{ device: import('./types').Device }>(`/devices/${uuid}`),

  update: (uuid: string, payload: { label?: string }) =>
    api.patch<{ message: string; device: import('./types').Device }>(`/devices/${uuid}`, payload),

  bind: (deviceUuid: string, vehicleUuid: string) =>
    api.post<{ message: string; device: import('./types').Device }>(`/devices/${deviceUuid}/bind`, {
      vehicle_uuid: vehicleUuid,
    }),

  unbind: (deviceUuid: string, reason?: string) =>
    api.post<{ message: string; device: import('./types').Device }>(`/devices/${deviceUuid}/unbind`, {
      reason,
    }),
};

export const subscriptionApi = {
  plans: () => api.get<{ plans: SubscriptionPlan[] }>('/subscriptions/plans'),

  me: () => api.get<{ subscription: Entitlements }>('/subscriptions/me'),

  history: () => api.get<{ data: SubscriptionHistoryResponse }>('/subscriptions/history'),

  renew: (payload: { plan_uuid: string; channel?: string; idempotency_key?: string }) =>
    api.post<{ message: string; data: PaymentInitializeResponse }>('/subscriptions/renew', payload),

  cancel: (reason?: string) =>
    api.post<{
      message: string;
      data: { uuid: string; status: string; auto_renew: boolean; ends_at: string };
    }>('/subscriptions/cancel', { reason }),
};

export const paymentApi = {
  initialize: (payload: PaymentInitializePayload) =>
    api.post<{ message: string; data: PaymentInitializeResponse }>('/payments/initialize', payload),

  verify: (payload: { reference: string }) =>
    api.post<{ message: string; data: PaymentRecord }>('/payments/verify', payload),

  list: (params?: { status?: string; purpose?: string; page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.purpose) query.set('purpose', params.purpose);
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    const suffix = query.toString();

    return api.get<{ data: PaymentRecord[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }>(
      `/payments${suffix ? `?${suffix}` : ''}`
    );
  },

  get: (uuid: string) => api.get<{ data: PaymentRecord }>(`/payments/${uuid}`),

  refund: (uuid: string, reason?: string) =>
    api.post<{ message: string; data: PaymentRecord }>(`/payments/${uuid}/refund`, { reason }),
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

export const trackingApi = {
  location: (vehicleUuid: string) =>
    api.get<{ location: import('./types').VehicleLocation; device: import('./types').Device | null }>(
      `/vehicles/${vehicleUuid}/location`,
    ),

  status: (vehicleUuid: string) =>
    api.get<{ status: import('./types').VehicleStatusTelemetry; device: import('./types').Device | null }>(
      `/vehicles/${vehicleUuid}/status`,
    ),

  playback: (vehicleUuid: string, params?: { from?: string; to?: string }) => {
    const query = new URLSearchParams();
    if (params?.from) query.set('from', params.from);
    if (params?.to) query.set('to', params.to);
    const suffix = query.toString();
    return api.get<{
      from: string;
      to: string;
      count: number;
      points: import('./types').PlaybackPoint[];
    }>(`/vehicles/${vehicleUuid}/playback${suffix ? `?${suffix}` : ''}`);
  },

  trips: (vehicleUuid: string) =>
    api.get<{ vehicle_uuid: string; trips: import('./types').TripItem[] }>(
      `/vehicles/${vehicleUuid}/trips`,
    ),

  syncTelemetry: () =>
    api.get<{ status: string; message: string; updates: any[] }>('/cron/telemetry-sync'),
};

export const securityApi = {
  shutdown: (
    vehicleUuid: string,
    payload: { confirmation: boolean; password?: string; reason?: string },
  ) =>
    api.post<{
      message: string;
      command: import('./types').SecurityCommandRecord;
    }>(`/vehicles/${vehicleUuid}/shutdown`, payload),

  restoreEngine: (vehicleUuid: string, payload: { confirmation: boolean }) =>
    api.post<{
      message: string;
      command: import('./types').SecurityCommandRecord;
    }>(`/vehicles/${vehicleUuid}/restore-engine`, payload),

  callVehicle: (vehicleUuid: string) =>
    api.post<{
      message: string;
      phone_number?: string;
      command: import('./types').SecurityCommandRecord;
    }>(`/vehicles/${vehicleUuid}/call-vehicle`),

  voiceMonitor: (vehicleUuid: string) =>
    api.post<{
      message: string;
      command: import('./types').SecurityCommandRecord;
    }>(`/vehicles/${vehicleUuid}/voice-monitor`),

  sendGprsCommand: (vehicleUuid: string, commandType: string, parameters?: Record<string, any>) =>
    api.post<{
      message: string;
      command: import('./types').SecurityCommandRecord;
    }>(`/vehicles/${vehicleUuid}/gprs-command`, {
      command_type: commandType,
      parameters,
    }),

  securityEvents: (vehicleUuid: string) =>
    api.get<{
      vehicle_uuid: string;
      events: import('./types').SecurityCommandRecord[];
    }>(`/vehicles/${vehicleUuid}/security-events`),

  theftTrigger: (
    vehicleUuid: string,
    payload?: { pin?: string; password?: string; biometric_verified?: boolean },
  ) =>
    api.post<{
      message: string;
      theft_event: import('./types').TheftEvent;
    }>(`/vehicles/${vehicleUuid}/theft-trigger`, payload ?? {}),

  theftEvents: (vehicleUuid: string) =>
    api.get<{
      vehicle_uuid: string;
      theft_events: import('./types').TheftEvent[];
    }>(`/vehicles/${vehicleUuid}/theft-events`),

  theftEvent: (eventUuid: string) =>
    api.get<{
      theft_event: import('./types').TheftEvent;
    }>(`/theft-events/${eventUuid}`),

  resolveTheftEvent: (
    eventUuid: string,
    payload: { resolution_note: string; status?: 'resolved' | 'false_alarm' },
  ) =>
    api.post<{
      message: string;
      theft_event: import('./types').TheftEvent;
    }>(`/theft-events/${eventUuid}/resolve`, payload),

  commandStatus: (commandUuid: string) =>
    api.get<{
      command: import('./types').SecurityCommandRecord;
    }>(`/commands/${commandUuid}`),
};

export const dashcamApi = {
  pair: (payload: { serial_number: string; imei?: string; model?: string; vehicle_uuid?: string }) =>
    api.post<{
      message: string;
      device: import('./types').Device;
    }>('/dashcam/pair', payload),

  stream: (deviceUuid: string, camera: 'front' | 'cabin' | 'rear' = 'front') =>
    api.get<{
      stream: import('./types').DashcamStreamSession;
      device: { uuid: string; is_online: boolean; model: string };
    }>(`/dashcam/${deviceUuid}/stream?camera=${camera}`),

  recordings: (deviceUuid: string, params?: { date?: string; camera?: 'front' | 'cabin' | 'rear' }) => {
    const query = new URLSearchParams();
    if (params?.date) query.set('date', params.date);
    if (params?.camera) query.set('camera', params.camera);
    const suffix = query.toString();
    return api.get<{
      date: string;
      camera: string;
      count: number;
      recordings: import('./types').DashcamRecording[];
    }>(`/dashcam/${deviceUuid}/recordings${suffix ? `?${suffix}` : ''}`);
  },

  emergencies: (deviceUuid: string) =>
    api.get<{
      count: number;
      events: import('./types').DashcamRecording[];
    }>(`/dashcam/${deviceUuid}/emergencies`),

  snapshot: (deviceUuid: string, camera: 'front' | 'cabin' | 'rear' = 'front') =>
    api.post<{
      message: string;
      snapshot: import('./types').DashcamSnapshot;
    }>(`/dashcam/${deviceUuid}/snapshot`, { camera }),

  status: (deviceUuid: string) =>
    api.get<{
      device: import('./types').DashcamStatus;
    }>(`/dashcam/${deviceUuid}/status`),

  restart: (deviceUuid: string) =>
    api.post<{
      message: string;
      command: { uuid: string; status: string };
    }>(`/dashcam/${deviceUuid}/restart`),

  formatSdCard: (deviceUuid: string) =>
    api.post<{
      message: string;
      sd_card: { status: string; free_gb: number };
    }>(`/dashcam/${deviceUuid}/format-sd-card`),

  liveForVehicle: (vehicleUuid: string, camera: 'front' | 'cabin' | 'rear' = 'front') =>
    api.get<{
      stream: import('./types').DashcamStreamSession;
      device: { uuid: string; is_online: boolean; model: string };
    }>(`/vehicles/${vehicleUuid}/dashcam/live?camera=${camera}`),
};

export const careApi = {
  dashboard: (vehicleUuid: string) =>
    api.get<import('./types').VehicleCareDashboard>(`/vehicles/${vehicleUuid}/care/dashboard`),

  updateOdometer: (vehicleUuid: string, odometerKm: number, source: 'manual' | 'tracker' = 'manual') =>
    api.patch<{
      message: string;
      vehicle: { uuid: string; odometer_km: number; odometer_source: string; odometer_updated_at: string | null };
    }>(`/vehicles/${vehicleUuid}/odometer`, { odometer_km: odometerKm, source }),

  timeline: (vehicleUuid: string, limit: number = 25) =>
    api.get<{
      vehicle_uuid: string;
      count: number;
      timeline: import('./types').CareTimelineItem[];
    }>(`/vehicles/${vehicleUuid}/care/timeline?limit=${limit}`),

  maintenanceRecords: (vehicleUuid: string, params?: { category?: string; search?: string; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.category) query.set('category', params.category);
    if (params?.search) query.set('search', params.search);
    if (params?.per_page) query.set('per_page', String(params.per_page));
    const suffix = query.toString();
    return api.get<import('./types').Paginated<import('./types').MaintenanceRecord>>(
      `/vehicles/${vehicleUuid}/maintenance-records${suffix ? `?${suffix}` : ''}`,
    );
  },

  createMaintenanceRecord: (
    vehicleUuid: string,
    payload: {
      category: string;
      title: string;
      performed_at: string;
      odometer_km?: number;
      workshop_name?: string;
      cost?: number;
      currency?: string;
      description?: string;
      notes?: string;
      next_due_at?: string;
      next_due_odometer_km?: number;
    },
  ) =>
    api.post<{
      message: string;
      record: import('./types').MaintenanceRecord;
      reminder: import('./types').MaintenanceReminder | null;
    }>(`/vehicles/${vehicleUuid}/maintenance-records`, payload),

  updateMaintenanceRecord: (
    vehicleUuid: string,
    recordUuid: string,
    payload: Partial<{
      category: string;
      title: string;
      performed_at: string;
      odometer_km?: number;
      workshop_name?: string;
      cost?: number;
      currency?: string;
      description?: string;
      notes?: string;
      next_due_at?: string;
      next_due_odometer_km?: number;
    }>,
  ) =>
    api.patch<{
      message: string;
      record: import('./types').MaintenanceRecord;
    }>(`/vehicles/${vehicleUuid}/maintenance-records/${recordUuid}`, payload),

  deleteMaintenanceRecord: (vehicleUuid: string, recordUuid: string) =>
    api.delete<{ message: string }>(`/vehicles/${vehicleUuid}/maintenance-records/${recordUuid}`),

  reminders: (vehicleUuid: string, status?: string) => {
    const query = status ? `?status=${status}` : '';
    return api.get<{
      vehicle_uuid: string;
      count: number;
      reminders: import('./types').MaintenanceReminder[];
    }>(`/vehicles/${vehicleUuid}/reminders${query}`);
  },

  createReminder: (
    vehicleUuid: string,
    payload: {
      category: string;
      title: string;
      due_at?: string;
      due_odometer_km?: number;
    },
  ) =>
    api.post<{
      message: string;
      reminder: import('./types').MaintenanceReminder;
    }>(`/vehicles/${vehicleUuid}/reminders`, payload),

  completeReminder: (vehicleUuid: string, reminderUuid: string) =>
    api.patch<{
      message: string;
      reminder: import('./types').MaintenanceReminder;
    }>(`/vehicles/${vehicleUuid}/reminders/${reminderUuid}/complete`),

  dismissReminder: (vehicleUuid: string, reminderUuid: string) =>
    api.patch<{
      message: string;
      reminder: import('./types').MaintenanceReminder;
    }>(`/vehicles/${vehicleUuid}/reminders/${reminderUuid}/dismiss`),

  deleteReminder: (vehicleUuid: string, reminderUuid: string) =>
    api.delete<{ message: string }>(`/vehicles/${vehicleUuid}/reminders/${reminderUuid}`),

  fuelRecords: (vehicleUuid: string, page: number = 1) =>
    api.get<{
      summary: { total_litres: number; total_cost: number; average_price_per_litre: number };
      data: import('./types').FuelRecord[];
      meta: import('./types').Paginated<unknown>['meta'];
    }>(`/vehicles/${vehicleUuid}/fuel-records?page=${page}`),

  createFuelRecord: (
    vehicleUuid: string,
    payload: {
      filled_at: string;
      litres: number;
      price_per_litre?: number;
      total_amount?: number;
      odometer_km?: number;
      is_full_tank?: boolean;
      station?: string;
      notes?: string;
    },
  ) =>
    api.post<{
      message: string;
      record: import('./types').FuelRecord;
    }>(`/vehicles/${vehicleUuid}/fuel-records`, payload),

  deleteFuelRecord: (vehicleUuid: string, recordUuid: string) =>
    api.delete<{ message: string }>(`/vehicles/${vehicleUuid}/fuel-records/${recordUuid}`),
};

export const autoDocApi = {
  launch: (vehicleUuid: string) =>
    api.post<{
      message: string;
      session: import('./types').AutoDocLaunchSession;
    }>(`/vehicles/${vehicleUuid}/autodoc/launch`),

  documents: (vehicleUuid: string) =>
    api.get<import('./types').AutoDocDocumentSummary>(`/vehicles/${vehicleUuid}/autodoc/documents`),

  associate: (vehicleUuid: string, autodocVehicleRef: string) =>
    api.post<{
      message: string;
      vehicle: { uuid: string; autodoc_vehicle_ref: string };
    }>(`/vehicles/${vehicleUuid}/autodoc/associate`, { autodoc_vehicle_ref: autodocVehicleRef }),

  diagnostics: (vehicleUuid: string) =>
    api.get<import('./types').AutoDocDiagnosticsPayload>(`/vehicles/${vehicleUuid}/autodoc/diagnostics`),

  clearCodes: (vehicleUuid: string) =>
    api.post<{
      message: string;
      vehicle_uuid: string;
      health_score: number;
      fault_codes_count: number;
      fault_codes: import('./types').DiagnosticFaultCode[];
      cleared_at: string;
    }>(`/vehicles/${vehicleUuid}/autodoc/clear-codes`),
};

export const finderApi = {
  categories: () =>
    api.get<{
      data: import('./types').FinderCategory[];
    }>('/finder/categories'),

  vendors: (params?: {
    search?: string;
    category?: string;
    city?: string;
    state?: string;
    min_rating?: number;
    sort?: 'rating' | 'name' | 'newest' | 'reviews';
    page?: number;
  }) => {
    const query = new URLSearchParams();
    if (params?.search) query.set('search', params.search);
    if (params?.category) query.set('category', params.category);
    if (params?.city) query.set('city', params.city);
    if (params?.state) query.set('state', params.state);
    if (params?.min_rating) query.set('min_rating', String(params.min_rating));
    if (params?.sort) query.set('sort', params.sort);
    if (params?.page) query.set('page', String(params.page));
    const suffix = query.toString();
    return api.get<{
      data: import('./types').FinderVendor[];
      meta: { current_page: number; last_page: number; total: number };
    }>(`/finder/vendors${suffix ? `?${suffix}` : ''}`);
  },

  vendorDetails: (vendorUuid: string) =>
    api.get<{
      data: import('./types').FinderVendor;
    }>(`/finder/vendors/${vendorUuid}`),

  reviews: (vendorUuid: string, page = 1) =>
    api.get<{
      data: import('./types').VendorReview[];
      meta: { current_page: number; last_page: number; total: number };
    }>(`/finder/vendors/${vendorUuid}/reviews?page=${page}`),

  submitReview: (
    vendorUuid: string,
    payload: {
      rating: number;
      title?: string;
      comment: string;
      booking_uuid?: string;
    },
  ) =>
    api.post<{
      message: string;
      data: import('./types').VendorReview;
    }>(`/finder/vendors/${vendorUuid}/reviews`, payload),
};

export const bookingApi = {
  list: (params?: { status?: string; page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.page) query.set('page', String(params.page));
    const suffix = query.toString();
    return api.get<{
      data: import('./types').BookingRecord[];
      meta: { current_page: number; last_page: number; total: number };
    }>(`/bookings${suffix ? `?${suffix}` : ''}`);
  },

  create: (payload: import('./types').CreateBookingPayload) =>
    api.post<{
      message: string;
      data: import('./types').BookingRecord;
    }>('/bookings', payload),

  details: (bookingUuid: string) =>
    api.get<{
      data: import('./types').BookingRecord;
    }>(`/bookings/${bookingUuid}`),

  cancel: (bookingUuid: string, reason?: string) =>
    api.post<{
      message: string;
      data: { uuid: string; reference: string; status: string; cancelled_at: string };
    }>(`/bookings/${bookingUuid}/cancel`, { reason }),
};

export const vendorHubApi = {
  register: (payload: {
    business_name: string;
    trading_name?: string;
    category_id?: number;
    email?: string;
    phone?: string;
    whatsapp?: string;
    description?: string;
    address_line?: string;
    city?: string;
    state?: string;
  }) =>
    api.post<{
      message: string;
      data: { uuid: string; business_name: string; status: string };
    }>('/vendor-hub/register', payload),

  profile: () =>
    api.get<{
      data: import('./types').FinderVendor;
    }>('/vendor-hub/profile'),

  updateProfile: (payload: Partial<import('./types').FinderVendor>) =>
    api.put<{
      message: string;
      data: { uuid: string; business_name: string };
    }>('/vendor-hub/profile', payload),

  services: () =>
    api.get<{
      data: import('./types').FinderServiceItem[];
    }>('/vendor-hub/services'),

  bookings: (params?: { status?: string; page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.page) query.set('page', String(params.page));
    const suffix = query.toString();
    return api.get<{
      data: import('./types').BookingRecord[];
      meta: { current_page: number; last_page: number; total: number };
    }>(`/vendor-hub/bookings${suffix ? `?${suffix}` : ''}`);
  },

  updateBookingStatus: (bookingUuid: string, status: string, vendorNote?: string) =>
    api.patch<{
      message: string;
      data: { uuid: string; status: string };
    }>(`/vendor-hub/bookings/${bookingUuid}/status`, { status, vendor_note: vendorNote }),
};

export const adminApi = {
  users: {
    list: (params?: { search?: string; status?: string; page?: number; per_page?: number }) => {
      const query = new URLSearchParams();
      if (params?.search) query.set('search', params.search);
      if (params?.status) query.set('status', params.status);
      if (params?.page) query.set('page', String(params.page));
      if (params?.per_page) query.set('per_page', String(params.per_page));
      const suffix = query.toString();
      return api.get<{ data: User[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/users${suffix ? `?${suffix}` : ''}`
      );
    },
    create: (payload: { name: string; email: string; phone?: string; password: string; status?: string }) =>
      api.post<{ message: string; user: User }>('/admin/users', payload),
    show: (id: string) => api.get<{ user: User }>(`/admin/users/${id}`),
    update: (id: string, payload: Partial<User> & { password?: string }) =>
      api.patch<{ message: string; user: User }>(`/admin/users/${id}`, payload),
  },

  vehicles: {
    list: (params?: { user_id?: string; search?: string; status?: string; page?: number; per_page?: number }) => {
      const query = new URLSearchParams();
      if (params?.user_id) query.set('user_id', params.user_id);
      if (params?.search) query.set('search', params.search);
      if (params?.status) query.set('status', params.status);
      if (params?.page) query.set('page', String(params.page));
      if (params?.per_page) query.set('per_page', String(params.per_page));
      const suffix = query.toString();
      return api.get<{ data: Vehicle[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/vehicles${suffix ? `?${suffix}` : ''}`
      );
    },
    create: (payload: Partial<Vehicle> & { user_id: string; plate_number: string }) =>
      api.post<{ message: string; vehicle: Vehicle }>('/admin/vehicles', payload),
    show: (id: string) => api.get<{ vehicle: Vehicle }>(`/admin/vehicles/${id}`),
    update: (id: string, payload: Partial<Vehicle>) =>
      api.patch<{ message: string; vehicle: Vehicle }>(`/admin/vehicles/${id}`, payload),
    remove: (id: string) => api.delete<{ message: string }>(`/admin/vehicles/${id}`),
  },

  devices: {
    list: (params?: { type?: string; status?: string; search?: string; page?: number; per_page?: number }) => {
      const query = new URLSearchParams();
      if (params?.type) query.set('type', params.type);
      if (params?.status) query.set('status', params.status);
      if (params?.search) query.set('search', params.search);
      if (params?.page) query.set('page', String(params.page));
      if (params?.per_page) query.set('per_page', String(params.per_page));
      const suffix = query.toString();
      return api.get<{ data: import('./types').Device[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/devices${suffix ? `?${suffix}` : ''}`
      );
    },
    create: (payload: Record<string, unknown>) =>
      api.post<{ message: string; device: import('./types').Device }>('/admin/devices', payload),
    show: (id: string) => api.get<{ device: import('./types').Device }>(`/admin/devices/${id}`),
    bind: (id: string, vehicle_id: string) =>
      api.post<{ message: string; device: import('./types').Device }>(`/admin/devices/${id}/bind`, { vehicle_id }),
    unbind: (id: string) =>
      api.post<{ message: string; device: import('./types').Device }>(`/admin/devices/${id}/unbind`),
  },
};





