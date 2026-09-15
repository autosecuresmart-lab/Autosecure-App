# AUTOSECURE 2.0 — Admin portal (`/manage`)

The `/manage` route group is the AUTOSECURE management portal. It is part of the
same Laravel application — no second backend was created.

## 1. Separation from customers

| | Customer | Staff |
| --- | --- | --- |
| Table | `users` | `admins` |
| Guard | `web` / `sanctum` | `admin` |
| Provider | `users` | `admins` |
| Password broker | `users` | `admins` (30-minute tokens) |
| Login route | `login` (placeholder) | `manage.login` |
| Session | `sessions` | `sessions` (own cookie name) |

Authentication is completely separate: a customer session cannot reach `/manage`,
an admin cannot authenticate on the customer guard, and a suspended admin is
logged out immediately by `EnsureAdminIsActive`. All three are covered by tests.

## 2. Authorisation model

```
Admin ─< admin_role >─ Role ─< role_permission >─ Permission
```

- An admin may hold several roles; effective permissions are the **union**.
- `admins.is_super_admin` is a break-glass flag that bypasses checks and is
  intended for a handful of accounts. Every use is audited.
- Each module route is guarded by `<module>.view`, derived by
  `ModuleController::permissionFor()`. The sidebar and the server-side check read
  from the same source, so they cannot drift.
- A module the admin cannot access is rendered **locked** in the sidebar with the
  required permission in its tooltip.

## 3. Roles seeded

| Role | Scope |
| --- | --- |
| **Super Admin** | Every permission |
| **Operations Manager** | Customers, vehicles, devices, security/theft events, support + access grants, audit log read, reports |
| **Support Agent** | Customer/vehicle/device read, security read, support, `sensitive_access.location` only |
| **Vendor Officer** | Vendors, verification queue, Finder content, reports read |
| **Finance Officer** | Payments, reconciliations, refunds, subscriptions, plans, Coins, bookings refunds, reports + export, audit read |
| **Content Manager** | Finder categories, help content, notification templates, bookings read, reports read |

46 permissions across 16 groups: `customers`, `vehicles`, `devices`, `security`,
`sensitive_access`, `vendors`, `vendor_verifications`, `bookings`, `payments`,
`subscriptions`, `coins`, `reports`, `content`, `support`, `audit_logs`,
`settings`.

## 4. Modules

Phase 1 registers every module's route, permission boundary and navigation entry.
Modules whose features arrive in a later phase render their scope and required
permission instead of a fabricated screen.

| Module | Route | Permission | Phase |
| --- | --- | --- | --- |
| Dashboard | `/manage` | any active admin | ✅ built (live counts) |
| Customers & Vehicles | `/manage/customers` | `customers.view` | 2 |
| Vehicles | `/manage/vehicles` | `vehicles.view` | 2 |
| Devices | `/manage/devices` | `devices.view` | 2 (provider) |
| Vendors | `/manage/vendors` | `vendors.view` | 5 |
| Vendor verification | `/manage/vendor-verifications` | `vendor_verifications.view` | 5 |
| Bookings & orders | `/manage/bookings` | `bookings.view` | 5 |
| Payments & settlements | `/manage/payments` | `payments.view` | 5 (gateway) |
| Subscriptions | `/manage/subscriptions` | `subscriptions.view` | 3 |
| Coins | `/manage/coins` | `coins.view` | 6 |
| Reports | `/manage/reports` | `reports.view` | 6 |
| Content | `/manage/content` | `content.view` | 5 |
| Support | `/manage/support` | `support.view` | 2 |
| Audit logs | `/manage/audit-logs` | `audit_logs.view` | 2 |
| Settings | `/manage/settings` | `settings.view` | 3 |

## 5. What the dashboard shows today

Real counts from the database — customers, vehicles, devices, vendors, vendors
awaiting verification, bookings, successful payments — plus the signed-in admin's
roles and permissions, and a Phase 1 status table.

## 6. Sensitive-data access

Support agents do not get blanket access to customer location, video, voice or
documents. `support_access_grants` requires:

- a stated `reason` (mandatory),
- an explicit `scopes` list (`location`, `video`, `voice`, `documents`, `commands`),
- an expiry (`support.grant_minutes`, default 60),
- an approver,
- an incrementing `access_count` and `last_accessed_at` on every use,
- an audit entry at `notice` severity.

## 7. Runtime-configurable settings

`app_settings` holds 17 seeded values so the business can change commercial
behaviour without a deployment. Anything not yet approved is labelled as such in
the setting's description.

| Group | Examples |
| --- | --- |
| `finder` | default commission %, vendor annual fee |
| `coins` | earn rate, Naira per coin, minimum redemption, max % payable, expiry, transferable |
| `subscriptions` | grace period days, payment retry attempts |
| `devices` | command acknowledgement timeout, max attempts |
| `support` | default grant duration |
| `privacy` | location retention days, video retention days |
| `app` | minimum mobile version, theft trigger requires PIN (public) |

## 8. Signing in

```bash
cd backend
ADMIN_EMAIL=you@autosecure.ng ADMIN_PASSWORD='a-strong-password' php artisan db:seed --class=AdminUserSeeder
```

Then visit `/manage/login`. Login is rate-limited to 5 attempts per email+IP.

> The portal's Blade views use inline CSS deliberately, so `/manage` works without
> running a Vite build. Swap to the Vite pipeline when the design system assets
> land.
