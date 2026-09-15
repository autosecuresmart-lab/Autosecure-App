# AUTOSECURE 2.0 — Mobile / Figma screen map

## 1. Important caveat about the Figma source

The supplied design link
(`figma.com/design/9hFabFb2CEvo1KUbXcU5x3/Standalone-Autosecure-App-Tracker`)
**could not be read**. Figma design files require an authenticated session, and the
fetch returned no usable content.

What that means:

- No screen was redesigned, as instructed.
- The screen inventory below is derived from the **approved product proposal**,
  which describes the current dashcam screens (Appendix A), the recommended main
  navigation, the care-module data, and the theft-trigger flow.
- **A design-file inventory still needs to be done with an authenticated viewer.**
  Until then the mapping is "proposal-accurate", not "Figma-verified".

If a shared read-only link or an exported PDF/PNG set is provided, this document
should be re-verified screen by screen.

## 2. Navigation structure

The proposal's recommended main navigation is implemented verbatim as five tabs:

| Tab | Route name | Contents (proposal) | Phase 1 state |
| --- | --- | --- | --- |
| Home | `Home` | Vehicle status, urgent alerts, next maintenance items, shortcuts | ✅ built |
| Security | `Security` | Tracker controls, theft trigger, live location, trip playback | Shell + entitlement truth + workflow spec |
| Camera | `Camera` | Embedded dashcam live video, playback, emergencies, albums | Shell + function inventory |
| Finder | `Finder` | Verified nearby vendors, search, booking, payment, reviews | Shell + journey + rules |
| My AUTOSECURE | `Account` | Vehicle records, AutoDoc, coins, subscription, settings | ✅ built |

Signed-out state: Login → Register (two-screen toggle, no tab bar).

## 3. Screen inventory

### 3.1 Existing screens described by the proposal

| Screen | Source | Mapped to |
| --- | --- | --- |
| Live stream controls | Appendix A | Camera |
| Front camera + live map | Appendix A | Camera |
| Rear/cabin camera + live map | Appendix A | Camera |
| Front/rear playback library | Appendix A | Camera |
| Emergency recordings | Appendix A | Camera |
| Album categories | Appendix A | Camera |
| Device settings | Appendix A | Camera |
| QR/barcode pairing | Appendix A | Camera |
| Account controls | Appendix A | My AUTOSECURE |
| Theft Trigger UI (work already in progress) | Delivery status table | Security |
| Tracker controls | Tracker row of status table | Security |

### 3.2 Screens built in Phase 1

| Screen | File | Needs API data | Needs device | Needs payment |
| --- | --- | --- | --- | --- |
| Login | `screens/auth/LoginScreen.tsx` | ✅ | — | — |
| Register | `screens/auth/RegisterScreen.tsx` | ✅ | — | — |
| Home (vehicle status, alerts, plan) | `screens/HomeScreen.tsx` | ✅ | — | — |
| Security (entitlements + workflow) | `screens/SecurityScreen.tsx` | ✅ | ✅ pending | — |
| Camera (function inventory) | `screens/CameraScreen.tsx` | ✅ | ✅ pending | — |
| Finder (journey + vendor rules) | `screens/FinderScreen.tsx` | ✅ | — | ✅ pending |
| My AUTOSECURE (vehicles, plan, coins) | `screens/AccountScreen.tsx` | ✅ | — | ✅ pending |

### 3.3 Missing screens, by module

**Security / Tracker** (needs the tracker API)

- Vehicle selector for security context
- Live location map with timestamp, speed, status
- Trip playback: route list → route detail → map replay
- Remote shutdown: PIN/biometric confirm → pending → confirmed/failed result
- Call vehicle: initiation + in-progress state
- Theft trigger: confirm → live observe → act → record → resolve
- Theft event detail with command log and resolution note
- Security settings: sharing permissions, notifications

**Dashcam** (needs the dashcam SDK/API)

- Live view with front/rear-cabin switch + map overlay
- Playback browser by date/time, per camera
- Emergency recordings list and detail
- Snapshot capture + share/save
- Album: loop / event / snapshot / clip categories
- Find my car
- Geofence and trajectory shortcuts
- Device settings: name, network, sharing, SD card, info
- Restart and unbind flows with confirmation
- Pairing: QR scan, barcode scan, manual entry
- Offline / no-file / permission-denied / stream-failed states

**Vehicle Care** (Phase 3 — schema ready)

- Care dashboard with Due Soon / Due Now / Overdue states
- Add/edit care record per category (oil, brakes, tyres, battery, service, repair)
- Attachments: receipts, photos, booking references
- Service history timeline, searchable
- Mileage entry and trend
- Fuel log + consumption and spending summary
- Reminder list, reschedule, dismiss
- Multi-vehicle care switcher

**AutoDoc** (Phase 4 — needs an agreed integration level)

- AutoDoc entry point on the vehicle screen
- Renewal summary card
- Deep-link handoff and store fallback
- Expired-session and unmatched-vehicle recovery states
- Consent / data-sharing explanation

**Finder** (Phase 5 — needs commercial sign-off)

- Category browse → search with filters (location, rating, price, availability)
- Vendor profile: verification badge, services, prices, policies, reviews
- Service/product detail and slot selection
- Booking confirmation and order summary
- Checkout and payment method selection
- Booking/order list with status updates
- Receipt, ratings and problem reporting

**Subscriptions & Coins** (Phase 3 / 6)

- Plan comparison and purchase
- Payment method and confirmation
- Renewal reminder, grace-period and downgrade messaging
- Coins balance, pending/available breakdown, ledger history
- Redemption at checkout

**Account & platform**

- Notifications centre (API client already written)
- Vehicle detail and edit
- Vehicle sharing: invite, roles, revoke
- Profile edit, password change, 2FA/biometric toggle
- Device/session list with revoke-by-uuid
- Support contact and consent/privacy pages

## 4. Which screens need what

| Dependency | Screens |
| --- | --- |
| API data only (buildable now) | Auth, Home, My AUTOSECURE, notifications, vehicle CRUD, care records |
| Device integration | All Security and Dashcam screens |
| Payment / subscription | Plan purchase, checkout, Coins redemption, receipts |
| External app | AutoDoc entry and renewal summary |
| Push | Theft alerts, care reminders, booking updates |

## 5. Constraints carried into the UI

1. **Do not redesign the app.** The five-tab structure and existing dashcam
   function set are preserved; the Phase 1 screens use the same dark visual
   language so the port is a reskin, not a rewrite.
2. **Security stays available on Free.** The Security and Camera tabs read the
   server's entitlement list and always show core features as included.
3. **No optimistic device state.** Shutdown and trigger screens must show
   pending → confirmed/failed, never an instant success.
4. **States are first-class.** Loading, offline, empty, permission-denied and
   stream-failed are part of the design, not an afterthought.
5. **No provider secrets on device.** Streaming uses short-lived tokens issued by
   the API; nothing permanent is stored in the app.
6. **Design tokens are centralised** in `src/theme/index.ts`, so matching the
   Figma file exactly is a token update rather than a screen-by-screen edit.
