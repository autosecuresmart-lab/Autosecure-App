# AUTOSECURE 2.0 — Phase 2: Backend Foundation

Phase 2 completes the backend foundation the mobile app and `/manage` build on.
It **reused** the Phase 1 schema, models and middleware rather than duplicating
them — exactly one schema change was needed.

Status: **Completed.**

---

## 1. What Phase 2 added

| Area | Delivered |
| --- | --- |
| Authentication | Password recovery (request + reset), signed-in device listing, per-device revocation |
| Profile | `GET/PATCH /users/me`, `PUT /users/me/password` |
| Devices | Full `/api/v1/devices` area: list, read, label, bind, unbind |
| Device integration readiness | Provider contracts, DTOs, null drivers, a driver manager and a registration point |
| Authorisation | `device.access` middleware with per-capability grants |
| Error contract | Every API failure now carries a machine-readable `code`, including framework-raised ones |
| Rate limiting | `password-reset` limiter (per IP **and** per target address) |
| Tools | `forgetGuards()` note, device factory states, 79 new tests |

Phase 1 already delivered auth, vehicles, ownership enforcement, notifications,
audit logging, the `/manage` shell and entitlements. None of that was rewritten.

## 2. Database

**One migration was required:** `2026_09_14_003300_add_label_to_devices_table.php`
adds a nullable `devices.label`.

Everything else already existed. `brand`, `model` and `serial_number` are
provisioning facts; a customer running a tracker *and* a dashcam on one vehicle
needs to tell them apart, and the dashcam device-settings experience in the
proposal includes "device name".

No table was duplicated and no existing column was changed. All new tables
continue the project convention: internal `id` + public `uuid`, with uuid as the
route key.

## 3. Device provider contracts (the Phase 3/4 seam)

This was the real gap. Controllers, migrations and models must not change when a
provider arrives, so the integration surface is now defined and tested.

```
app/Contracts/Devices/TrackerProvider.php     status, latestLocation,
                                              locationHistory, sendCommand
app/Contracts/Devices/DashcamProvider.php     createStreamSession, recordings,
                                              emergencies, captureSnapshot, unbind

app/Support/Devices/DeviceStatus.php          normalised online/firmware/battery/signal
app/Support/Devices/DeviceLocation.php        normalised fix + position attributes
app/Support/Devices/CommandResult.php         accepted vs acknowledged
app/Support/Devices/StreamSession.php         short-lived, time-boxed
app/Support/Devices/Recording.php             loop / event / emergency / snapshot

app/Services/Devices/DeviceProviderManager.php  driver resolution + extend()
app/Services/Devices/Providers/Null{Tracker,Dashcam}Provider.php
app/Services/Devices/DeviceBindingService.php   vehicle ↔ device lifecycle
```

### Connecting a real provider is four steps

1. Implement `TrackerProvider` (or `DashcamProvider`) in one class.
2. Register it in `AppServiceProvider::registerDeviceProviders()`:
   ```php
   $manager->extend('tracker', 'acme', fn () => new AcmeTrackerProvider(
       config('autosecure.devices.tracker.base_url'),
       config('autosecure.devices.tracker.api_key'),
   ));
   ```
3. Set `TRACKER_DRIVER=acme` in `.env`.
4. Remove `security` from `App\Support\PendingIntegrations`.

No controller, route, model or migration changes. There is a test that proves a
registered driver replaces the null one, and another that proves an unknown
driver fails loudly instead of silently.

### The null drivers never invent data

Each null driver throws `IntegrationPendingException`, which the HTTP layer renders
as the standard `501 integration_pending` body. A fabricated stream URL or device
status would look like a working feature to the customer; a fabricated command
acknowledgement would be dangerous.

`CommandResult` makes the safety rule structural:

```php
public function isConfirmed(): bool
{
    return $this->accepted && $this->acknowledged && $this->acknowledgedAt !== null;
}
```

A provider cannot report a successful remote shutdown without device evidence.

### One blocker list, two consumers

`App\Support\PendingIntegrations` is now the single registry. The 501 response and
the driver exception both read from it, so the answer the mobile app displays can
never drift from the error a driver raises. A test asserts they are identical.

## 4. Device lifecycle vs device data

Deliberately separated:

| Concern | Owner | State |
| --- | --- | --- |
| Which vehicle a device is on | AUTOSECURE platform | ✅ implemented |
| What a device is called | AUTOSECURE platform | ✅ implemented |
| Who may see it (sharing, capabilities) | AUTOSECURE platform | ✅ implemented |
| Location, video, status, commands | Provider | ⏳ pending |

**Binding is not a claim-by-serial flow.** Manual QR/barcode pairing proves
physical possession of hardware and therefore depends on the provider's pairing
contract. Until then, a customer may only bind a device AUTOSECURE has *reserved*
for them (`devices.user_id`), which is how a shipped order is provisioned. This
closes the obvious holes:

- a guessed/leaked uuid cannot claim hardware (`device_forbidden` / `device_not_reserved`),
- a device cannot be attached to somebody else's vehicle (`vehicle_forbidden`),
- an unbound device is invisible to everyone until it is reserved.

Unbinding succeeds locally and records `metadata.provider_unbind_pending = true`
rather than pretending the vendor platform has released the device.

## 5. Authorisation added

`EnsureDeviceAccess` (`device.access`) mirrors the vehicle middleware:

- a device is reachable via the vehicle it is bound to — owner, or an active grant;
- an **unbound** device is reachable only by the user it is reserved for;
- an optional capability argument (`location`, `video`, `commands`, `care`) checks
  the corresponding grant flag, so a "viewer" cannot open a camera feed.

Revoked and expired grants are ignored. Tested, including the capability denial.

## 6. Consistent API errors

`bootstrap/app.php` now renders a `code` for framework-raised failures as well as
controller-raised ones:

| HTTP | `code` |
| --- | --- |
| 401 | `unauthenticated` |
| 403 | `forbidden` |
| 404 | `not_found` |
| 422 | `validation_error` (+ `errors`) |
| 429 | `rate_limited` (+ `retry_after`) |
| 501 | `integration_pending` (+ `blocked_by`) |

Non-API requests fall through to Laravel's default handling, so `/manage` and the
landing page keep their redirects and Blade error pages.

## 7. Testing

79 new tests were added; the suite is now **129 tests / 367 assertions**, all
passing, with no regressions in the Phase 1 suite.

| File | Covers |
| --- | --- |
| `Feature/Api/UserProfileTest.php` | profile read/update, phone re-verification, status escalation attempt, password change rules, other-token revocation |
| `Feature/Api/PasswordResetTest.php` | no account enumeration, app deep link + token in the email, suspended accounts get nothing, single-use codes, token revocation, throttling |
| `Feature/Api/DeviceTest.php` | scoping, uuid routing, capability denial, revoked grants, binding rules, unbinding, provider reporting |
| `Feature/Api/SessionRevocationTest.php` | session listing, per-device revocation, cross-customer refusal |
| `Unit/DeviceProviderTest.php` | driver resolution, registered drivers, unknown drivers, null drivers refusing, `CommandResult` safety, DTO behaviour, registry consistency |

### Two real bugs found and fixed while testing

1. **`TRACKER_DRIVER=null` in `.env` resolves to a PHP null**, leaving the driver as
   an empty string and throwing `Unknown tracker driver []`. Fixed with `?:` in
   `config/autosecure.php` plus an empty-string guard in the manager.
2. **Laravel's `RequestGuard` caches the resolved user across requests within one
   test**, so a second request appeared authenticated by the first token. This is a
   test-environment artefact (production resolves a fresh guard per request); the
   test now calls `Auth::forgetGuards()` and says why.

## 8. Not built (correctly out of scope)

Tracker/dashcam endpoints, Finder, bookings, payments, Coins earning/redemption,
Vehicle Care endpoints, subscription purchase, and the full admin portal. They
belong to Phase 3 onward, and several are blocked on the commercial and provider
decisions listed in [../phase-1/PENDING-INFORMATION.md](../phase-1/PENDING-INFORMATION.md).

The mobile app was not changed.

## 9. Verification

```bash
cd backend
php artisan test                                  # 129 passed, 367 assertions
php artisan migrate:fresh --seed                  # 37 migrations + 5 seeders
php artisan route:list                            # 57 routes
```

`php -l` across all 146 PHP files: clean.

**Outstanding:** `.env` still needs `DB_PASSWORD` set so migrations can be run
against MySQL rather than the in-memory database used for verification.
