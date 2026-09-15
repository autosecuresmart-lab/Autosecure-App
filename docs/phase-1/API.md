# AUTOSECURE 2.0 — Mobile API

Base URL: `{APP_URL}/api/v1` — versioned as required, and the version segment
comes from `config('autosecure.api.version')` so `/api/v2` is a config change.

Every record is addressed by **uuid**. A numeric id in a URL returns 404.

---

## 1. Implemented endpoints

### Authentication

| Method | Path | Auth | Notes |
| --- | --- | --- | --- |
| `POST` | `/auth/register` | — | Creates the customer, a Coins wallet, and returns a token. `throttle:auth` (10/min per IP) |
| `POST` | `/auth/login` | — | Rejects suspended accounts with `403 account_inactive` |
| `POST` | `/auth/forgot-password` | — | Sends an in-app deep link + reset code. Response is identical whether or not the address exists. `throttle:password-reset` |
| `POST` | `/auth/reset-password` | — | Exchanges a code for a new password and revokes **every** token |
| `GET` | `/auth/me` | Bearer | Profile + server-resolved entitlements |
| `GET` | `/auth/sessions` | Bearer | Signed-in devices (by token uuid) |
| `DELETE` | `/auth/sessions/{session}` | Bearer | Revoke one device, addressed by token uuid |
| `POST` | `/auth/logout` | Bearer | Revokes **only** the current token |
| `POST` | `/auth/logout-all` | Bearer | Revokes every token |

`register` / `login` accept optional `device_name`, `platform` (`ios`/`android`/`web`)
and `push_token`, which registers the handset for notifications.

### Profile (`/users`)

Scoped to the signed-in customer only. There is deliberately no endpoint to list or
read other customers — that is an admin capability living in `/manage`.

| Method | Path | Notes |
| --- | --- | --- |
| `GET` | `/users/me` | Profile + entitlements |
| `PATCH` | `/users/me` | `name`, `phone`, `locale`, `timezone`. Changing the phone resets its verified state. Email change is not permitted here (needs verification). |
| `PUT` | `/users/me/password` | Requires `current_password`; rejects reuse; revokes all other tokens |

### Devices (`/devices`)

Device **lifecycle** is platform-owned and implemented. Device **data** (location,
video, commands) comes from the provider and is still pending.

| Method | Path | Middleware | Notes |
| --- | --- | --- | --- |
| `GET` | `/devices` | auth | Every device the customer can reach, with `?type=` and `?vehicle=` filters |
| `GET` | `/devices/{device}` | `device.access` | |
| `PATCH` | `/devices/{device}` | `device.access` | Label only. Brand/model/serial/IMEI/SIM are provisioning facts and are ignored. |
| `POST` | `/devices/{device}/bind` | `device.access` | Links a device AUTOSECURE reserved for this customer to one of their vehicles |
| `DELETE` | `/devices/{device}/unbind` | `device.access` | Owner only. Records that provider-side release is still outstanding. |

There is **no public bind-by-serial endpoint**. Manual QR/barcode pairing proves
physical possession and therefore needs the provider's pairing contract; a
guessable-identifier claim flow would be a security hole.

### My Vehicle

| Method | Path | Middleware |
| --- | --- | --- |
| `GET` | `/vehicles` | Owned **or** shared vehicles, devices included |
| `POST` | `/vehicles` | First vehicle becomes primary |
| `GET` | `/vehicles/{vehicle}` | `vehicle.access` |
| `PATCH` | `/vehicles/{vehicle}` | `vehicle.access`, owner only |
| `DELETE` | `/vehicles/{vehicle}` | `vehicle.access`, owner only |
| `GET` | `/vehicles/{vehicle}/devices` | `vehicle.access` |

Creating a vehicle also creates an explicit `owner` grant so downstream permission
checks have one shape to reason about.

### Subscriptions & Coins

| Method | Path | Auth |
| --- | --- | --- |
| `GET` | `/subscriptions/plans` | public |
| `GET` | `/subscriptions/me` | Bearer |
| `GET` | `/coins/wallet` | Bearer — wallet, redemption rules, last 50 ledger entries |

### Notifications

| Method | Path |
| --- | --- |
| `GET` | `/notifications` (paginated, `?unread_only=1`) |
| `POST` | `/notifications/{notification}/read` |
| `POST` | `/notifications/read-all` |
| `POST` | `/notifications/push-tokens` |
| `DELETE` | `/notifications/push-tokens` |

---

## 2. Response conventions

Success:

```json
{ "vehicle": { "uuid": "…", "display_name": "Toyota Corolla", "…": "…" } }
```

Errors are consistent and machine-readable:

```json
{
  "message": "Human readable explanation.",
  "code": "premium_required",
  "required_features": ["care.oil_change"]
}
```

| HTTP | `code` | Meaning |
| --- | --- | --- |
| 401 | `unauthenticated` | Token missing/expired. The app clears its session. |
| 402 | `premium_required` | Feature needs an active Premium subscription. |
| 403 | `account_inactive` | Customer suspended. |
| 403 | `forbidden` | Generic authorisation failure from `abort(403)`. |
| 403 | `vehicle_forbidden` | No ownership or active grant on this vehicle. |
| 403 | `vehicle_capability_denied` | Grant exists but not for this capability. |
| 403 | `device_forbidden` | No access to this device. |
| 403 | `device_capability_denied` | Vehicle grant does not cover this device capability. |
| 403 | `device_not_reserved` | Device belongs to another customer. |
| 404 | `not_found` | Unknown uuid. |
| 409 | `device_already_bound` | Device is already linked to a vehicle. |
| 422 | `validation_error` | `errors` maps field → messages. |
| 429 | `rate_limited` | Slow down. |
| 501 | `integration_pending` | Depends on provider documentation that has not been supplied. |

---

## 3. Pending modules (HTTP 501)

These areas are registered so the contract is discoverable and stable, and they
answer `501` with the **exact** information still outstanding rather than
pretending to work:

| Prefix | Blocked on |
| --- | --- |
| `/security/*` | Tracker API: auth, device identification, GPS/history endpoints, command endpoints, shutdown safety rules, acknowledgement format, status endpoint |
| `/dashcam/*` | Dashcam SDK/API: streaming method, playback access, pairing contract, device controls, token exchange, platform support matrix |
| `/autodoc/*` | Agreed integration level, shared identity, vehicle matching key, deep-link scheme, renewal summary contract |
| `/care/*` | Tracker odometer availability, reminder channel configuration |
| `/finder/*`, `/bookings/*`, `/payments/*` | Commercial sign-off: vendor fee, commission, cancellation policy, gateway selection |

Example:

```http
GET /api/v1/security
Authorization: Bearer …

501 Not Implemented
{
  "code": "integration_pending",
  "module": "security",
  "title": "Tracker / Security",
  "message": "This part of the AUTOSECURE 2.0 API is not implemented yet because the required AUTOSECURE/provider documentation has not been supplied.",
  "blocked_by": [
    "Tracker API documentation and base URL",
    "Tracker authentication method and credential lifecycle",
    "Vehicle/device identification scheme",
    "GPS location endpoint and update cadence",
    "Playback/history endpoint and retention period",
    "Command endpoints (remote shutdown, restore, call vehicle)",
    "Remote shutdown safety rules and interlock behaviour",
    "Command acknowledgement payload and status codes",
    "Device status endpoint and webhook/callback contract"
  ]
}
```

The mobile app calls these and renders the live `blocked_by` list, so the answer is
never out of date with the backend.

---

## 4. Planned surface for Phase 2 onward

Listed so the shape is agreed before implementation. Nothing here is built yet.

```
/api/v1
  security/
    vehicles/{vehicle}/location            live position
    vehicles/{vehicle}/positions           history for playback (?from=&to=)
    vehicles/{vehicle}/commands            POST { type, pin? } -> command uuid
    commands/{command}                     poll status (pending|sent|acknowledged|failed|timeout)
    vehicles/{vehicle}/theft-events        POST trigger, GET list
    theft-events/{theftEvent}              GET, POST resolve
    vehicles/{vehicle}/call                POST (voice monitoring)
  dashcam/
    devices/{device}/stream                issues a SHORT-LIVED token, never a permanent one
    devices/{device}/recordings            ?date=&camera=
    devices/{device}/emergencies
    devices/{device}/capture               POST snapshot
    devices/{device}/pair                  POST QR/barcode payload
    devices/{device}/unbind                DELETE
    devices/{device}/settings              PATCH name, sharing, SD card
  care/
    vehicles/{vehicle}/maintenance         CRUD + attachments
    vehicles/{vehicle}/reminders
    vehicles/{vehicle}/fuel
    vehicles/{vehicle}/timeline
  autodoc/
    vehicles/{vehicle}/link                deep link + shared sign-in handoff
    vehicles/{vehicle}/renewals            summary (level 3, if approved)
  finder/
    categories, vendors, vendors/{uuid}/services, search
  bookings/                                POST create, GET list, PATCH cancel
  payments/                                POST initiate, GET status, webhook
  subscriptions/                           POST purchase, PATCH cancel/auto-renew
  coins/                                   POST redeem, GET ledger
```

---

## 5. Design rules the API follows

1. **Versioned from day one.** `/api/v1` is a route group; `/api/v2` is added
   alongside rather than mutating v1.
2. **UUID-only addressing.** No endpoint takes a numeric id.
3. **Server-side entitlements.** The client never decides what it may use; the
   `entitlement` middleware checks every gated route.
4. **Server-side vehicle authorisation.** `vehicle.access` runs on every vehicle
   route, so the app cannot reach a vehicle it has no grant for.
5. **Idempotency where money or a physical action is involved.** `payments` and
   `device_commands` carry unique `idempotency_key` columns.
6. **Provider secrets never reach the client.** Streaming and command credentials
   are exchanged server-side; the dashcam stream endpoint issues short-lived
   tokens.
7. **No optimistic success.** A device command is `pending` until the device
   confirms it.

## 6. Rate limiting

| Limiter | Rule | Applies to |
| --- | --- | --- |
| `auth` | 10/min per IP | login, register, staff login |
| `api` | 120/min per customer, 30/min per IP when anonymous | general API |
| `device-commands` | 10/min per customer | planned for security commands |

Defined in `AppServiceProvider::configureRateLimiting()`.
