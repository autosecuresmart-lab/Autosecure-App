# AUTOSECURE 2.0 — Architecture

## 1. Discovery: what actually existed

The workspace contained **no application code**. `/backend` and `/frontend` were
empty directories. The only artefacts present were:

| Artefact | Use |
| --- | --- |
| `AUTOSECURE_2.0_Product_Upgrade_Proposal.docx` | Product requirements — the source of truth for scope |
| `GPRS Communication Protocol for implementing features.pdf` | Device protocol reference (vendor: Shenzhen Sanjiutongchuang Electronic Co., Ltd.) |

Because there was nothing to reuse or preserve, "do not destroy working
functionality" translated into "do not delete the two reference documents and do
not restructure the empty folders". The Laravel application was created directly
inside the existing `/backend` folder and the React Native application inside
`/frontend`, exactly as the brief required.

> The brief described an existing React Native app, a Figma design, `/manage`
> routes, migrations and authentication. None of that was present on disk. Phase 1
> therefore built the foundation rather than auditing one.

## 2. System shape

```
                    ┌──────────────────────────────┐
   React Native     │  /frontend  (Expo SDK 54)    │
   (mobile)         │  Sanctum bearer token        │
                    └──────────────┬───────────────┘
                                   │  /api/v1/*
                                   ▼
┌──────────────────────────────────────────────────────────────────┐
│  /backend  — Laravel 13.31  (the single central backend)         │
│                                                                  │
│   routes/api.php    → /api/v1  (Sanctum)   mobile + future web   │
│   routes/manage.php → /manage  (admin)     AUTOSECURE portal     │
│   routes/web.php    → /                    landing page          │
│                                                                  │
│   app/Http/Controllers/Api/V1   API controllers                  │
│   app/Http/Controllers/Manage   admin portal controllers         │
│   app/Http/Middleware           auth + entitlement + vehicle ACL │
│   app/Models                    Eloquent models (uuid routing)   │
│   app/Services                  Audit, Subscriptions             │
│   app/Contracts (planned)       provider drivers                 │
│   config/autosecure.php         commercial + integration config   │
└───────────────┬──────────────────────────┬───────────────────────┘
                │                          │
          MySQL 8 (36 tables)      External providers — ALL PENDING
                                   tracker · dashcam · AutoDoc ·
                                   payment gateway
```

One backend, two clients (mobile now, web later), plus the `/manage` portal.
No second backend project was created and none is needed.

## 3. Laravel layering

Laravel's own conventions are used; no custom framework was invented.

| Concern | Where |
| --- | --- |
| HTTP entry | `routes/*.php`, `bootstrap/app.php` |
| Request validation | Controller `$request->validate()` (FormRequests when modules grow) |
| Business rules | `app/Services/*` |
| Persistence | `app/Models/*` (Eloquent) |
| Cross-cutting gates | `app/Http/Middleware/*` |
| Configuration | `config/autosecure.php` + `app_settings` table |
| API shape | `app/Http/Resources/Api/V1/*` |

**Only three service classes exist so far** — `AuditLogger`,
`EntitlementService`, and the (unwritten but contracted) provider drivers. This is
deliberate: services are added when a module needs one, not up front.

## 4. Module map

| Proposal module | Backend | Frontend tab | Phase |
| --- | --- | --- | --- |
| Authentication | `admins` + `users`, two guards | Login / Register | ✅ Phase 1 |
| My Vehicle | `Vehicle` model, 5 endpoints | Home + My AUTOSECURE | ✅ Phase 1 |
| Devices | `Device`, `DeviceCommand`, `DevicePosition` | — | Schema done, endpoints Phase 2 |
| Security / Tracker | `TheftEvent`, command lifecycle | Security | ⏳ Phase 2 (provider) |
| Dashcam | `devices.type = dashcam` | Camera | ⏳ Phase 2 (provider) |
| Vehicle Care | `MaintenanceRecord`, `MaintenanceReminder`, `FuelRecord` | — | Schema done, Phase 3 |
| AutoDoc | `vehicles.autodoc_vehicle_ref` | My AUTOSECURE | ⏳ Phase 4 |
| Finder | `Vendor`, `VendorCategory`, `VendorService` | Finder | Schema done, Phase 5 |
| Bookings / Orders | `Booking`, `BookingItem` | Finder | Schema done, Phase 5 |
| Payments | `Payment` | — | Schema done, Phase 5 |
| Subscriptions | `SubscriptionPlan`, `Subscription`, plan features | My AUTOSECURE | ✅ Resolution built, Phase 3 for purchase |
| Coins | `CoinWallet`, `CoinTransaction` | My AUTOSECURE | ✅ Ledger built, Phase 6 for earn/redeem |
| Notifications | `Notification`, `NotificationTemplate`, `DeviceToken` | API ready | Phase 2 for screens |
| Admin / Manage | `Admin`, `Role`, `Permission` | `/manage` | ✅ Shell + auth |

## 5. Key decisions

### 5.1 Admin identity is a separate table, not a role on `users`
Requested explicitly, and correct. Admins get their own table, guard, provider,
password broker and session. A customer session cannot reach `/manage` and an
admin cannot authenticate on the customer guard — both are covered by tests.

### 5.2 `id` + `uuid` on every table, uuid is the route key
Every migration creates `id` (internal, foreign keys) and `uuid` (public, unique).
`App\Models\Concerns\HasUuid` makes uuid the route key, so
`/api/v1/vehicles/{vehicle}` resolves by uuid and never exposes a sequential id.

The uuid is generated by Laravel's `HasUniqueIds` mechanism (which runs inside
`performInsert()`) rather than a `creating` model event. This matters: the
seeder uses `WithoutModelEvents`, and an event-based hook silently produced null
uuids. Discovered and fixed during Phase 1.

Link tables (`role_permission`, `admin_role`) use dedicated models plus
`syncPermissions()` / `syncRoles()` helpers, because Laravel's `attach()` inserts
through the query builder and would skip uuid generation.

**Deliberate exception:** Laravel's own infrastructure tables (`cache`,
`cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`,
`password_reset_tokens`) are framework-owned and are left untouched. Sanctum's
`personal_access_tokens` *was* extended with a uuid (and a custom
`PersonalAccessToken` model registered via `Sanctum::usePersonalAccessTokenModel()`)
so signed-in devices can be revoked by uuid.

### 5.3 Server-side entitlements, core security is never removed
`EntitlementService` resolves features from the subscription on every request and
the `entitlement` middleware guards premium routes. The free tier's feature list
lives in `config/autosecure.php` and includes live location, playback, remote
shutdown, call vehicle, theft trigger, dashcam access and Finder. A lapsed
subscription removes premium modules only — there is a test asserting exactly
that.

### 5.4 Device commands can never be optimistic
`device_commands` records the full lifecycle, and `DeviceCommand::isConfirmed()`
requires `status = acknowledged` **and** a non-null `acknowledged_at` set from a
device response. A command that was sent, timed out or failed is never confirmed.
Remote Shutdown cannot be reported as done without the device saying so. Six unit
tests cover the states.

### 5.5 Pending integrations are honest
The API registers `security`, `dashcam`, `autodoc`, `care`, `finder`, `bookings`
and `payments` as explicit routes that answer **HTTP 501** with a `blocked_by`
list of the exact information still outstanding. The mobile app's
`PendingIntegration` component calls those routes and displays the live answer.

This was a deliberate reading of "do not invent an API": the architecture accepts
a provider cleanly, and nothing in the product pretends the integration exists.

### 5.6 No unnecessary dependencies
- Backend added exactly one package: `laravel/sanctum` (first-party API auth).
- Frontend used `npx expo install` so every package matches SDK 54; no HTTP
  client, state manager or icon-font library was added. Tab glyphs are text.
- `php artisan install:api` failed silently because Composer was not on `PATH`;
  a workspace-local `.tools/composer.phar` shim was created instead of installing
  Composer machine-wide.

## 6. Trade-offs taken consciously

| Decision | Why, and what it costs |
| --- | --- |
| `PendingController` wildcard routes registered last | Gives the mobile app a complete, documented surface map. Real routes are registered first so they always win. Removing them once a module ships is a one-line change. |
| MySQL for the app, in-memory SQLite for tests | Laravel's default and fast. Schema avoids MySQL-only features so both pass. Switch `phpunit.xml` to MySQL if dialect drift ever becomes a concern. |
| `/manage` modules render scope, not fake screens | The permission boundary and navigation are real from day one. Feature screens arrive with their phase. |
| Numeric `id` retained alongside `uuid` | InnoDB clusters on the primary key; uuid joins would be slower and larger. The id simply never appears in a URL or payload. |
| SQLite for tests uses the same migrations | Ensures migrations stay dialect-neutral. |

## 7. What Phase 2 will need

1. Tracker API documentation (see [DEVICE-INTEGRATION.md](DEVICE-INTEGRATION.md)).
2. Dashcam SDK/API documentation.
3. Provider sandbox credentials and a test device.
4. Sign-off on the ten commercial decisions listed in
   [PENDING-INFORMATION.md](PENDING-INFORMATION.md).

The schema, the command lifecycle, the entitlement gate, the audit trail and the
vehicle access control are already in place and tested, so a provider driver is
the main piece of work.
