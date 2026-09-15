# AUTOSECURE 2.0 — Device integration discovery

## 1. What exists today

| Item | State |
| --- | --- |
| Tracker API / SDK documentation | **Not supplied** |
| Dashcam SDK / API documentation | **Not supplied** |
| Provider sandbox credentials | **Not supplied** |
| Test device | **Not supplied** |
| Device protocol reference | `GPRS Communication Protocol for implementing features.pdf` — a **device-level GPRS/3G protocol** document (header `CS*<IMEI>*<LEN>*<COMMAND>`, terminal replies, AT-style command set, ASCII positioning tables, LBS/WiFi fallback) |

### On the GPRS protocol document

It describes how a **device terminal** talks to a server over GPRS. It is useful
and it is *not* the same thing as a tracker platform API:

- It gives real command syntax the hardware understands (e.g. `FIND`, `POWEROFF`,
  `REMIND`, `SLEEPTIME`, `SILENCETIME`, `PHB`, `VERNO`, `LOWBAT`, `CR`) and the
  reply envelope `[CS*YYYYYYYYYY*LEN*…]`.
- It confirms the platform expects: interval/status reporting, alarm flags
  (SOS, low battery, out-of-fence, in-fence), positioning fields (date, time,
  latitude, longitude, speed, direction, altitude, GSM signal, satellites,
  power level), `Terminalstatus` bit flags, and audio escape rules.
- The vendor is Shenzhen Sanjiutongchuang Electronic Co., Ltd.

**It does not give us what Phase 2 needs**: a hosted platform API, its
authentication, or whether AUTOSECURE integrates at the *device protocol* level or
through a *vendor platform*. That distinction changes the design substantially, and
it is the first question to resolve.

## 2. Two possible integration shapes

### Shape A — vendor cloud platform API (most likely)
AUTOSECURE calls a hosted REST/HTTP API belonging to the tracker vendor.

```
Mobile app ─► AUTOSECURE backend ─► vendor platform API ─► device (GPRS)
                    ▲                                          │
                    └──────── webhook / polling callback ◄──────┘
```

The GPRS protocol stays the vendor's problem. We need their REST docs.

### Shape B — direct device connection
AUTOSECURE operates its own GPRS/TCP server speaking the protocol in the PDF.

```
Device (GPRS/TCP) ─► AUTOSECURE socket server ─► DB ─► API ─► Mobile app
```

This is a much larger build: a persistent socket server, device session registry,
command queue with retry, protocol codec (including the documented escape rules),
and its own acknowledgement tracking. It also means owning device uptime and
carrier behaviour.

**Recommendation:** Shape A unless AUTOSECURE already operates GPRS
infrastructure. This needs a decision before Phase 2 starts.

## 3. Information still required — tracker

### 3.1 Access & authentication
- [ ] Base URL(s) — production and sandbox
- [ ] Authentication method: API key, signed request (HMAC), OAuth2, per-account
      login, IP allow-list?
- [ ] Credential lifecycle: expiry, rotation, environment separation
- [ ] Rate limits and throttling behaviour
- [ ] Error codes and their meanings

### 3.2 Device & vehicle identification
- [ ] Is the device addressed by IMEI, serial, or a vendor platform id?
- [ ] How is a device mapped to an AUTOSECURE vehicle and customer on the vendor side?
- [ ] Is there a binding/unbinding API, or is it configured in their portal?
- [ ] Does the device hold a SIM we must know about (`sim_number`, `phone_number`)?

### 3.3 Location & history
- [ ] Live/last-known position endpoint, and its freshness/update interval
- [ ] Position fields available (speed, heading, altitude, accuracy, ignition)
- [ ] Positioning sources reported (GPS only, or the GPS/LBS/WiFi mix in the PDF?)
- [ ] History/playback endpoint: parameters, maximum range, pagination
- [ ] **Retention period** — how far back can we query?
- [ ] Push (webhook/callback) vs polling, and the expected cadence
- [ ] Trip/segment endpoints, or must trips be derived from positions?

### 3.4 Commands (once location works)
- [ ] Command endpoint shape and accepted command set
- [ ] Which commands the fleet's firmware actually supports
- [ ] Confirmation semantics: is there an acknowledgement, and what does it look like?
- [ ] Timeout behaviour when the device is offline — queued or dropped?
- [ ] **Remote shutdown / immobiliser**: exact command, safety interlocks,
      legal/operational restrictions, and whether the vehicle can be restored
- [ ] Call vehicle / voice monitoring: how is audio established, and what are the
      consent requirements?
- [ ] Idempotency — can a resent command immobilise a vehicle twice?

### 3.5 Device status
- [ ] Online/offline determination and its reliability
- [ ] Firmware version, battery, GSM/GPS signal, SIM status
- [ ] Alarm/event stream: SOS, low battery, fence in/out, vibration, tamper
- [ ] Geofence configuration API (the PDF `Terminalstatus` flags imply fence support)

## 4. Information still required — dashcam

The proposal lists the functions already demonstrated in the supplied dashcam
screens. To embed them:

- [ ] SDK or API, and legal right to embed it
- [ ] Platform support: iOS, Android, minimum OS versions
- [ ] **Live streaming method** — vendor SDK player, HLS, WebRTC, or P2P tunnel?
      (This single answer drives the whole Camera tab architecture.)
- [ ] Stream latency and quality adaptation on Nigerian mobile networks
- [ ] Playback/recording access: list by date, download, thumbnails?
- [ ] Protected/emergency recording listing
- [ ] Snapshot capture from a live stream
- [ ] Device pairing: QR/barcode payload format, manual entry, provisioning
- [ ] Device controls: rename, network config, sharing, SD card status/format,
      info, restart, unbind
- [ ] Credential/token exchange so **streaming credentials never live in the app**
- [ ] Token lifetime and revocation
- [ ] iOS/Android background behaviour and screen-lock handling
- [ ] Data usage characteristics (the app must not silently burn a customer's bundle)

## 5. How the backend is designed for this

This is the part already done in Phase 1 — the schema and rules do not change when
the provider does.

| Design element | Why it survives whichever provider arrives |
| --- | --- |
| `devices` table with `type`, `provider`, `external_id`, `capabilities` (JSON) | Provider-agnostic. No provider-specific columns. A second provider is another `provider` value, not a migration. |
| `device_commands` with `status`, `payload`, `response`, `attempts`, `idempotency_key`, `acknowledged_at` | The full lifecycle exists before any provider is wired. Acknowledgement semantics slot straight in. |
| `device_positions` with a `source` enum (`gps`, `lbs`, `wifi`, `gps_lbs`) | Matches the GPS/LBS/WiFi fallback the GPRS protocol document describes. |
| `EnsureVehicleAccess` middleware with per-capability grants | Sharing is enforced today; a provider just supplies data behind it. |
| `config/autosecure.php` `devices.tracker.driver` / `dashcam.driver` | Driver name selects the implementation; `null` means "not wired yet". |
| `config` `devices.commands.ack_timeout_seconds` / `max_attempts` | Tunable once the real protocol timing is known. |
| `PendingController` returning the blocker list | The outstanding-information list is served from one place, so it stays honest. |

### Planned provider contract

```
App\Contracts\Devices\TrackerProvider
    getDeviceStatus(Device): DeviceStatus
    getLatestPosition(Device): Position
    getPositions(Device, from, to): Collection<Position>
    sendCommand(Device, type, payload): ProviderCommandResult
    normalizeAcknowledgement(raw): CommandAcknowledgement

App\Contracts\Devices\DashcamProvider
    createStreamSession(Device): StreamSession      // short-lived token
    listRecordings(Device, date): Collection<Recording>
    listEmergencies(Device): Collection<Recording>
    captureSnapshot(Device): Snapshot
    pairDevice(payload): DeviceBinding
    unbindDevice(Device): bool
```

Each contract gets a `Null*Provider` (which raises a clear "not configured" error)
so the app runs without any provider, and a concrete implementation per vendor.

## 6. Decisions required before Phase 2

1. Shape A (vendor platform API) or Shape B (direct device connection)?
2. Which provider(s) and firmware versions are in the active fleet?
3. Remote shutdown: exact semantics, interlocks and operational policy.
4. Dashcam streaming method and SDK redistribution rights.
5. Whether tracker-supplied odometer is available — the Vehicle Care module uses it
   for mileage-based reminders, with manual entry as fallback.
6. Retention policy for location and video (privacy and cost implications).

## 7. What cannot be said yet

No claim is made in the code, the docs or the app that a tracker or dashcam
integration works. The API answers `501 integration_pending`, and the mobile app
displays the live list of outstanding items. When the documentation arrives, the
work is: implement one provider class per contract, register the driver in
`config/autosecure.php`, and replace the pending routes with real ones.
