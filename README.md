# AUTOSECURE 2.0

The complete vehicle security, care and service platform.

| Folder | Contents |
| --- | --- |
| `backend/` | Laravel 13 central backend — mobile API (`/api/v1`), landing page, `/manage` admin portal |
| `frontend/` | React Native mobile app (Expo SDK 54) |
| `docs/phase-1/` | Discovery & architecture documentation |
| `docs/phase-2/` | Backend foundation: auth, profile, devices, provider contracts |

There is **one backend**. The landing page, mobile API, future web application and
the `/manage` portal all live in `backend/`. No separate backend project exists or
is needed.

---

## Quick start

### Prerequisites

- PHP 8.3+ with `pdo_mysql`
- MySQL 8
- Node.js 20+
- Composer (not on `PATH` here — a workspace shim is provided at `.tools/composer.bat`)

### 1. Database

```sql
CREATE DATABASE autosecure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set `DB_PASSWORD` in `backend/.env`.

### 2. Backend

```bash
cd backend

# Composer is not on PATH in this workspace; use the local shim:
..\.tools\composer.bat install

php artisan key:generate
php artisan migrate --seed
php artisan serve                      # http://localhost:8000
```

Create the admin account with a password you choose:

```bash
ADMIN_EMAIL=you@autosecure.ng ADMIN_PASSWORD='a-strong-password' \
  php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder
```

Then sign in at `/manage/login`.

### 3. Frontend

```bash
cd frontend
npm install
cp .env.example .env                   # point EXPO_PUBLIC_API_URL at the backend
npm start                              # press "a" for Android, "i" for iOS
```

Android emulator reaches the host on `10.0.2.2`; the iOS simulator uses
`localhost`. Both defaults are already handled in `src/config/env.ts`.

---

## Verify

```bash
cd backend  && php artisan test        # 129 tests, 367 assertions
cd frontend && npm run typecheck       # tsc --noEmit
cd frontend && npx expo export --platform android   # proves the bundle builds
```

---

## What works today

| Area | State |
| --- | --- |
| Customer auth (register, login, session restore, logout, logout-all) | ✅ |
| Password recovery (app deep link) and per-device session revocation | ✅ |
| Customer profile and password change | ✅ |
| Staff auth on a separate table, guard, provider and session | ✅ |
| Roles & permissions (46 permissions, 6 roles) | ✅ |
| My Vehicle — CRUD with server-enforced ownership and sharing | ✅ |
| Devices — list, label, bind, unbind with capability-based access control | ✅ |
| Device provider contracts + driver manager (integration-ready) | ✅ structure, ⏳ provider |
| Subscription plans and server-side entitlements | ✅ |
| Coins wallet and append-only ledger | ✅ read |
| Notifications API + push token registration | ✅ (screen in Phase 3) |
| `/manage` portal — dashboard and 14 guarded module routes | ✅ |
| Audit trail | ✅ |
| Tracker / Security | ⏳ needs the tracker API |
| Dashcam | ⏳ needs the dashcam SDK/API |
| AutoDoc | ⏳ needs an agreed integration level |
| Vehicle Care | Schema ready — Phase 3 |
| Finder / bookings / payments | Schema ready — Phase 5, awaiting sign-off |

---

## Things worth knowing

**UUID routing.** Every table has `id` (internal) + `uuid` (public). Routes resolve
by uuid; a numeric id returns 404. See `docs/phase-1/DATABASE.md` for the one
documented exception (Laravel's own infrastructure tables).

**Admins are not users.** Staff live in `admins` on the `admin` guard; customers in
`users` on `web`/`sanctum`. Neither can reach the other's surface — there are tests
for both directions.

**Nothing fakes an integration.** Areas blocked on provider documentation answer
`501 integration_pending` with the exact list of outstanding items, and the mobile
app displays that list live. See `docs/phase-1/PENDING-INFORMATION.md`.

**Remote shutdown can never report false success.** A command is only confirmed
when the device acknowledges it, and `acknowledged` without a device response
timestamp is treated as unconfirmed. See `docs/phase-1/SECURITY.md` §3.

---

## Documentation

Start at [`docs/phase-1/README.md`](docs/phase-1/README.md), then
[`docs/phase-2/FOUNDATION.md`](docs/phase-2/FOUNDATION.md).

Reference material in the repository root:

- `AUTOSECURE_2.0_Product_Upgrade_Proposal.docx` — approved product requirements
- `GPRS Communication Protocol for implementing features.pdf` — device protocol reference
