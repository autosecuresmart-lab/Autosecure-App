# AUTOSECURE 2.0 — Security

## 1. Authentication

### Customers
- Sanctum personal access tokens issued from `POST /api/v1/auth/register` and
  `POST /api/v1/auth/login`.
- Passwords hashed with bcrypt (`BCRYPT_ROUNDS=12`; 4 in tests).
- Token lifetime configurable (`SANCTUM_TOKEN_LIFETIME`, default 30 days).
- Logout revokes **only** the current token; `logout-all` revokes every token.
- Suspended accounts are refused at login (`403 account_inactive`).
- The app stores the token in the device keychain via `expo-secure-store` —
  never in plain storage.

### Staff
- Separate `admins` table, `admin` guard, `admins` provider and password broker.
- Session-based, with its own cookie name (`MANAGE_SESSION_COOKIE`) so an admin
  and a customer can be signed in in the same browser without collision.
- Suspended or disabled staff are logged out mid-session by `EnsureAdminIsActive`.
- Login throttled to 5 attempts per email+IP.

**Cross-surface attacks are structurally impossible and tested**: a customer
session cannot authenticate `/manage`, and an admin's credentials are useless on
the customer guard.

## 2. Authorisation

| Layer | Mechanism |
| --- | --- |
| Customer → vehicle | `EnsureVehicleAccess` middleware. Owner passes; otherwise an active `vehicle_access_grants` row is required, optionally with a specific capability. |
| Customer → premium feature | `EnsureEntitlement` middleware, resolved server side by `EntitlementService`. |
| Staff → module | `EnsureAdminHasPermission` with `<module>.view`, derived from the same registry that renders the sidebar. |
| Staff → sensitive data | `support_access_grants`: mandatory reason, explicit scopes, expiry, approver, access counter. |

Vehicle ownership and sharing are enforced **server side on every vehicle route**.
The client cannot reach a vehicle it holds no grant for, and a "viewer" cannot use
a capability it was not granted (`vehicle_capability_denied`). Covered by tests.

## 3. Secure device commands — the critical rule

> The action should not be presented as successful until the platform receives a
> confirmed response.
> — AUTOSECURE 2.0 proposal, section 04

Implemented as a property of the data model, not a convention:

```php
public function isConfirmed(): bool
{
    return $this->status === self::STATUS_ACKNOWLEDGED
        && $this->acknowledged_at !== null;
}
```

- `acknowledged_at` is only ever set from a real device/provider response.
- A command that was merely sent is `sent` and still `isAwaitingDevice()`.
- A timeout is `timeout`, never success.
- `acknowledged` **without** an acknowledgement timestamp is explicitly treated as
  *not confirmed*.
- Destructive commands (`remote_shutdown`, `restore`, `theft_alert`) are flagged by
  `isDestructive()` and carry `confirmed_by_user` from the PIN/biometric step.
- `idempotency_key` is unique, so a retried mobile request cannot immobilise a
  vehicle twice.
- Six unit tests assert the state machine, including the "acknowledged but no
  timestamp" case.

The full command history (requested, sent, acknowledged timestamps, payload,
response, failure reason) is retained for every command, and each is linked to its
theft event where applicable.

## 4. Sensitive data: location, video, voice, documents

- Nothing is exposed implicitly. Support access needs a `support_access_grants`
  row with a stated reason, explicit scopes, an expiry and an approver.
- Every use increments `access_count` and updates `last_accessed_at`.
- `AuditLogger::sensitive()` records a `notice`-severity entry in
  `sensitive_access`.
- Streaming credentials and permanent device tokens are never sent to the mobile
  client; the dashcam stream endpoint will issue short-lived tokens
  (`DASHCAM_TOKEN_TTL`, default 300 seconds).
- Consent and retention are settings, not constants: `privacy.location_retention_days`
  and `privacy.video_retention_days` are admin-editable and currently marked
  awaiting approval.

## 5. Audit logging

`audit_logs` is append-only and records actor (polymorphic: customer, admin,
system), the affected record (polymorphic), action, before/after changes, IP, user
agent and severity.

Written today for: registration, login, logout, logout-all, vehicle created,
vehicle updated (with before/after), vehicle deleted, staff login/logout, and every
sensitive-data access.

Morph aliases are enforced (`Relation::enforceMorphMap`) so polymorphic columns
store stable names (`user`, `admin`, `vehicle`) instead of class names.

## 6. Rate limiting

| Limiter | Rule | Applies to |
| --- | --- | --- |
| `auth` | 10/min per IP | register, login, staff login |
| `api` | 120/min per customer; 30/min per IP anonymous | general API |
| `device-commands` | 10/min per customer | planned for security commands |
| staff login | 5 attempts per email+IP | `/manage/login` |

Defined in `AppServiceProvider::configureRateLimiting()`.

## 7. Webhooks (planned)

No webhook endpoint is live yet, because no provider is integrated. The design is
already decided and `DEVICE_WEBHOOK_ALLOWED_HOSTS` exists in `.env`:

1. Verify a signature (HMAC) or a shared secret before parsing the body.
2. Reject replay: a unique provider event id recorded once.
3. Be idempotent: act on `idempotency_key` / `provider_reference`, never double-apply.
4. Never trust a webhook body to set an entitlement or a balance directly —
   re-read the authoritative provider state.
5. Log every received webhook, accepted or rejected.

## 8. Server-side entitlements

`EntitlementService` is the single source of truth. The client's copy is display
only. A lapsed subscription:

- downgrades entitlements to the free tier, and
- **never** removes live location, playback, remote shutdown, call vehicle, theft
  trigger or dashcam access.

Asserted by test (`SubscriptionEntitlementTest::test_an_expired_subscription_removes_premium_but_keeps_security`).

## 9. Data protection measures in place

| Measure | Implementation |
| --- | --- |
| Mass-assignment protection | Explicit `$fillable`; `id` and `uuid` are guarded and cannot be set from a request |
| Password leakage | `password`, `remember_token`, `two_factor_secret` in `$hidden` on both `User` and `Admin` |
| Credential leakage | Device `external_id`, `imei`, `sim_number` and provider credentials are **not** serialised by `DeviceResource` |
| Sequential-id enumeration | Routes resolve by uuid only; a numeric id returns 404 |
| Token rows addressable | `personal_access_tokens.uuid` allows revoking one device without exposing ids |
| Template injection | `NotificationTemplate::render()` uses whitelisted `strtr` substitution; template bodies are never evaluated as code |
| Share-link scope creep | Grants carry per-capability flags and an optional expiry; expired grants are ignored by `isActive()` |

## 10. Still to do (not Phase 1)

- Two-factor enforcement for staff (`two_factor_secret` and
  `two_factor_confirmed_at` columns exist; enrolment flow not built).
- Biometric/PIN confirmation UI for theft trigger and shutdown (the API records
  `pin_verified` / `biometric_verified`; the screens come with Phase 2).
- Suspicious-login alerts and account recovery controls.
- Webhook signature verification for the real provider.
- Key rotation and secret-storage policy for provider credentials.
- Full consent capture and data-subject request handling.

Each depends on a policy decision listed in
[PENDING-INFORMATION.md](PENDING-INFORMATION.md), so building them now would mean
guessing.
