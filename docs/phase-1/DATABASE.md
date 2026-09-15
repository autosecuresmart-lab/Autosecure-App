# AUTOSECURE 2.0 — Database

## 1. Conventions

Every AUTOSECURE table has:

| Column | Purpose |
| --- | --- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` primary key. Internal only — foreign keys and joins. **Never exposed in a URL or API payload.** |
| `uuid` | `CHAR(36)` unique. The public identifier. Route model binding resolves by uuid. |
| `created_at` / `updated_at` | Timestamps. |

Additional conventions:

- Money is `DECIMAL(12,2)` with an explicit `currency` column (`NGN` default).
- Status fields are database `ENUM`s so an invalid state cannot be written.
- Variable structures (device capabilities, audit changes, opening hours) are `JSON`.
- Soft deletes (`deleted_at`) on customer-facing records: users, admins, vehicles,
  devices, vendors, vendor services, maintenance records, fuel records, bookings.
- **Append-only** tables: `audit_logs`, `coin_transactions`, `device_positions`.
  Corrections are compensating rows, never edits.

Link tables (`role_permission`, `admin_role`) also carry `id` + `uuid`. They are
written through dedicated models (`RolePermission`, `AdminRole`) and the
`syncPermissions()` / `syncRoles()` helpers, because Laravel's `attach()` bypasses
models and would leave `uuid` empty.

**Framework tables are the one exception.** `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs`, `sessions` and `password_reset_tokens` ship with
Laravel and are left as-is; they are internal infrastructure and are not routed
to. `personal_access_tokens` (Sanctum) *was* extended with a uuid.

## 2. Configuration

`.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autosecure
DB_USERNAME=root
DB_PASSWORD=            # <-- set this before running migrations
```

Create the database once:

```sql
CREATE DATABASE autosecure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then:

```bash
cd backend
php artisan migrate --seed
```

Phase 1 was verified with `migrate --seed` against an in-memory database
(36 tables, 4 seeders, 0 errors) because the local MySQL server refused the
credentials available. **The MySQL password still needs to be set in `.env` and
the migrations run once against MySQL to confirm** — this is the single
outstanding verification step (see [PENDING-INFORMATION.md](PENDING-INFORMATION.md)).

## 3. Tables by domain

### 3.1 Identity & access

| Table | Purpose | Key columns |
| --- | --- | --- |
| `users` | AUTOSECURE **customers** | `name`, `email` (unique), `phone` (unique), `password`, `status`, `two_factor_enabled`, `last_login_at` |
| `admins` | AUTOSECURE **staff** — separate table, separate guard | `name`, `email` (unique), `password`, `status`, `is_super_admin`, `two_factor_secret`, `last_login_at` |
| `roles` | Role definitions | `name`, `slug` (unique), `guard` (`admin`), `is_system` |
| `permissions` | Permission definitions, grouped by module | `name`, `slug` (unique), `group` |
| `role_permission` | Role ↔ permission link | `role_id`, `permission_id`, unique pair |
| `admin_role` | Admin ↔ role link | `admin_id`, `role_id`, `assigned_by_admin_id` |
| `personal_access_tokens` | Sanctum tokens (published + uuid) | `uuid`, `tokenable_type/id`, `expires_at` |

A customer simply cannot be an admin: the two tables have no relationship.

### 3.2 Vehicles & devices

| Table | Purpose | Key columns |
| --- | --- | --- |
| `vehicles` | Customer vehicles | `user_id`, `plate_number`, `vin` (unique), `make`, `model`, `year`, `odometer_km`, `odometer_source` (`manual`/`tracker`), `is_primary`, `autodoc_vehicle_ref` |
| `vehicle_access_grants` | Sharing, enforced server side | `vehicle_id`, `user_id`, `role` (`owner`/`driver`/`viewer`), `can_view_location`, `can_view_video`, `can_send_commands`, `can_manage_care_records`, `expires_at`, `revoked_at` |
| `devices` | Trackers and dashcams | `type`, `vehicle_id`, `user_id`, `provider`, `external_id`, `serial_number` (unique), `label`, `imei`, `status`, `is_online`, `last_known_latitude/longitude`, `capabilities` (JSON) |
| `device_commands` | Full command lifecycle | `type`, `status`, `payload`, `response`, `idempotency_key` (unique), `attempts`, `confirmed_by_user`, `sent_at`, `acknowledged_at`, `theft_event_id` |
| `device_positions` | Location history (highest volume) | `device_id`, `latitude`, `longitude`, `speed_kph`, `heading`, `ignition`, `source` (`gps`/`lbs`/`wifi`/`gps_lbs`), `recorded_at`, indexed `(device_id, recorded_at)` |
| `theft_events` | Theft Trigger workflow | `vehicle_id`, `user_id`, `status`, `pin_verified`, `biometric_verified`, `triggered_at`, `resolved_at`, `resolution_note` |

`odometer_source` matters: the proposal requires tracker-supplied mileage where
available, with manual entry as a fallback that records who entered it and when.

### 3.3 Vehicle Care Memory

| Table | Purpose |
| --- | --- |
| `maintenance_records` | Oil, brakes, tyres, battery, service, repair. Carries `performed_at`, `odometer_km`, `cost`, `attachments`, `next_due_at`, `next_due_odometer_km`, `reminder_status` |
| `maintenance_reminders` | Schedulable/dismissible reminders, separate from the work record |
| `fuel_records` | Fill-ups: `litres`, `price_per_litre`, `total_amount`, `odometer_km`, `is_full_tank` |

Reminders support **both** time and mileage rules, and every record is scoped to
one vehicle so a customer with several vehicles sees separate histories.

### 3.4 Finder marketplace

| Table | Purpose |
| --- | --- |
| `vendor_categories` | Auto Parts Sellers, Car Washes, Mechanics (+ per-category commission override) |
| `vendors` | Business profile, location, service radius, opening hours, verification status, vendor subscription, rating, settlement details |
| `vendor_verifications` | One row per pipeline stage (application → identity → business → location → payout → approval → monitoring) with reviewer, notes and documents |
| `vendor_services` | Services and products with price, duration, stock, bookability |

`Vendor::scopePubliclyListed()` enforces the trust rule: only `verified`, publicly
visible vendors whose subscription has not lapsed can be listed or booked.

### 3.5 Bookings, payments, subscriptions, coins

| Table | Purpose |
| --- | --- |
| `bookings` | Services (`booking`) and parts (`order`). Carries `subtotal`, `discount`, `coins_redeemed`, `commission_percent`, `commission_amount`, `total`, `payment_status` |
| `booking_items` | Line items with **copied** unit price so a later price change never rewrites history |
| `payments` | Subscriptions, bookings, vendor subscriptions. `idempotency_key` unique so retries and duplicate webhooks cannot double-charge |
| `subscription_plans` | Free + Monthly ₦4,200 + Half-Year ₦23,940 + Yearly ₦45,360 (all pending final sign-off) |
| `subscription_plan_features` | Plan → entitlement keys |
| `subscriptions` | Status (`active`/`grace`/`expired`/…), period, `grace_ends_at`, `failed_payment_attempts`, `source` |
| `coin_wallets` | One per customer: `balance`, `pending_balance`, lifetime totals |
| `coin_transactions` | Append-only ledger: `type` (earn/pending/redeem/reverse/expire/adjustment), signed `coins`, `balance_after`, `expires_at`, `idempotency_key` |

The ledger is the source of truth; `coin_wallets.balance` is a cached total that
must reconcile with it. `balance_after` on every row makes drift detectable.

### 3.6 Platform

| Table | Purpose |
| --- | --- |
| `notifications` | Customer notification centre + delivery state per channel |
| `notification_templates` | Admin-editable templates; rendered by whitelisted token substitution, never evaluated |
| `device_tokens` | Push tokens per signed-in handset |
| `audit_logs` | Append-only. Actor (polymorphic), auditable (polymorphic), action, changes, IP, user agent, severity |
| `app_settings` | Runtime-editable commercial rules and toggles |
| `support_access_grants` | Time-boxed, justified support access to location / video / voice / documents, with an access count |

`notifications` is AUTOSECURE's own table (id + uuid), not Laravel's default
schema, so the framework's database notification channel is not used.

## 4. Relationship summary

```
users ─┬─< vehicles ─┬─< devices ─┬─< device_commands >── theft_events
       │             │            └─< device_positions
       │             ├─< vehicle_access_grants >── users (shared access)
       │             ├─< maintenance_records ─< maintenance_reminders
       │             ├─< fuel_records
       │             └─< bookings ─< booking_items
       ├─< bookings ──< payments
       ├─< subscriptions >── subscription_plans ─< subscription_plan_features
       ├─< coin_wallets ─< coin_transactions
       ├─< notifications
       ├─< device_tokens
       └─< vendors ─┬─< vendor_services ─< booking_items
                    ├─< vendor_verifications
                    └─< bookings

admins ─┬─< admin_role >── roles ─< role_permission >── permissions
        ├─< support_access_grants
        └─< audit_logs (actor, polymorphic)
```

## 5. Seeded data

`php artisan migrate --seed` writes:

| Seeder | Result |
| --- | --- |
| `RolePermissionSeeder` | 46 permissions across 16 module groups, 6 roles |
| `SubscriptionPlanSeeder` | Free, Monthly, Half-Year, Yearly + entitlements |
| `VendorCategorySeeder` | Auto Parts Sellers, Car Washes, Mechanics |
| `AppSettingSeeder` | 17 settings (Finder, Coins, subscriptions, devices, privacy, app) |
| `AdminUserSeeder` | One super admin from `ADMIN_EMAIL` / `ADMIN_PASSWORD` |

If `ADMIN_PASSWORD` is unset a random password is generated and printed once.

## 6. Not created yet, on purpose

Deferred until the phase that needs them, to avoid speculative schema:

refunds · vendor settlements/payouts · reviews and ratings tables · geofences ·
trips · support cases · documents · promo codes · dispute records ·
`maintenance_reminders` delivery log.

The commercial rules each depends on are not yet approved
([PENDING-INFORMATION.md](PENDING-INFORMATION.md)), so building them now would
mean guessing at the shape and rebuilding later.

## 7. Changes made in Phase 2

Phase 2 added exactly **one** column, and no new tables:

| Migration | Change |
| --- | --- |
| `2026_09_14_003300_add_label_to_devices_table` | `devices.label` (nullable, 120) — a customer-facing device name |

See [../phase-2/FOUNDATION.md](../phase-2/FOUNDATION.md).
