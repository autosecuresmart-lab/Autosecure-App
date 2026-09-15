# AUTOSECURE 2.0 — Pending information

Everything below is genuinely blocked. Nothing in the codebase pretends otherwise:
the API answers `501 integration_pending` and the app shows the live blocker list.

---

## A. Infrastructure / operational

| # | Item | Owner | Blocks |
| --- | --- | --- | --- |
| A1 | Local MySQL password for `.env` (`DB_PASSWORD`) | Whoever administers MySQL | Running the 36 migrations against MySQL for real. The schema was verified against an in-memory database; MySQL itself is the one outstanding verification step. |
| A2 | Hosting target, domains, TLS | AUTOSECURE | Deployment, real webhook URLs, `APP_URL` |
| A3 | Transactional email/SMS provider | AUTOSECURE | Care reminders, subscription notices, theft alerts |

---

## B. Device integration — the largest blocker

### B1. Tracker
| # | Item |
| --- | --- |
| B1.1 | **Integration shape**: vendor cloud API, or direct GPRS/device protocol? |
| B1.2 | Base URL(s) for production and sandbox |
| B1.3 | Authentication method and credential lifecycle |
| B1.4 | Device identification scheme and how it maps to an AUTOSECURE vehicle/customer |
| B1.5 | Live location endpoint and update cadence |
| B1.6 | History/playback endpoint, parameters, maximum range, retention |
| B1.7 | Command endpoint and the command set the fleet's firmware supports |
| B1.8 | Remote shutdown: exact command, safety interlocks, restore behaviour, operational policy |
| B1.9 | Command acknowledgement payload and status codes |
| B1.10 | Offline command behaviour (queued or dropped) and idempotency guarantees |
| B1.11 | Device status endpoint: online/offline, firmware, signal, SIM |
| B1.12 | Alarm/event stream: SOS, low battery, fence in/out, tamper |
| B1.13 | Webhook/callback contract, including signature verification |
| B1.14 | Whether tracker-supplied odometer is available (Vehicle Care depends on it) |
| B1.15 | Whether call vehicle / voice monitoring is supported, and consent rules |
| B1.16 | Which provider(s) and firmware versions are in the active fleet |

*Context:* the supplied `GPRS Communication Protocol` PDF documents the
**device-level** protocol (command syntax, reply envelope, alarm flags, positioning
fields) for Shenzhen Sanjiutongchuang Electronic hardware. It is not a hosted
platform API, so B1.1 must be answered before any code is written. See
[DEVICE-INTEGRATION.md](DEVICE-INTEGRATION.md) §2.

### B2. Dashcam
| # | Item |
| --- | --- |
| B2.1 | SDK or API documentation, **and the legal right to embed it** |
| B2.2 | Live streaming method — vendor SDK player, HLS, WebRTC, or P2P tunnel |
| B2.3 | Playback/recording listing and download access |
| B2.4 | Protected/emergency recording listing |
| B2.5 | Snapshot capture from a live stream |
| B2.6 | Device pairing contract (QR/barcode payload, manual entry, provisioning) |
| B2.7 | Device control API (rename, network, sharing, SD card, restart, unbind) |
| B2.8 | Token exchange so streaming credentials never reach the app |
| B2.9 | iOS/Android support matrix, minimum OS versions, background behaviour |
| B2.10 | Bandwidth and data-usage characteristics on Nigerian mobile networks |

---

## C. Commercial decisions (proposal section 12)

| # | Decision | Blocks |
| --- | --- | --- |
| C1 | Final vendor annual fee: ₦12,000, ₦15,000, or defined tiers | Vendor onboarding, vendor billing, renewal logic |
| C2 | Booking commission: percentage or flat fee, by category | Checkout, settlement, reporting |
| C3 | Coin earn rate, Naira value per Coin, minimum redemption, maximum % payable, expiry period, refund treatment, transferability | The entire Coins ledger behaviour (Phase 6) |
| C4 | Half-year and yearly discounts; grace period; payment retry policy | Subscription pricing and downgrade behaviour |
| C5 | Payment gateway, settlement schedule, refund ownership | Payments, payouts, reconciliation |
| C6 | Vendor verification document list and operating policy | Verification workflow and trust badge rules |
| C7 | Cancellation and dispute policy per vendor category | Booking lifecycle |
| C8 | Fulfilment rules (in-store, mobile, delivery, pickup) | Order flow |

Defaults exist in `config/autosecure.php` and `app_settings` for all of these, but
they are **placeholders** and are labelled as awaiting approval in the admin
settings descriptions. They must not be treated as approved.

---

## D. AutoDoc

| # | Item |
| --- | --- |
| D1 | Agreed integration level: deep link only, shared sign-in, or renewal summary API |
| D2 | Shared identity contract and the stable customer id |
| D3 | Vehicle matching key (internal vehicle id — plate number alone is insufficient) |
| D4 | Deep-link URL scheme and store fallback URLs |
| D5 | Renewal summary API contract (if level 3 is chosen) |
| D6 | Consent and data-sharing wording |

---

## E. Policy, privacy, operational

| # | Item |
| --- | --- |
| E1 | Privacy, data retention and consent policy (location, video, voice, documents) |
| E2 | Support-access policy: who can approve a grant, and for how long |
| E3 | Remote shutdown operational rules and any legal constraints |
| E4 | Payout account verification provider |
| E5 | Target iOS and Android versions to support |
| E6 | Standard/custom support workflow for sensitive-data requests |

---

## F. Discovery gaps on our side

| # | Item | Note |
| --- | --- | --- |
| F1 | **Figma design file could not be read** — it requires an authenticated session | The screen inventory in [FRONTEND-MAP.md](FRONTEND-MAP.md) is derived from the proposal document (which describes the dashcam screens and the recommended navigation), not verified against Figma. A read-only link or an exported PDF/PNG set would let us verify it screen by screen. |
| F2 | Verification that the "existing React Native app" and "existing `/manage` routes" described in the brief were expected to exist | Neither was present on disk, so Phase 1 built them. If a previous codebase exists elsewhere (another repo, a branch, a different machine), it should be reconciled before Phase 2 to avoid building the same thing twice. |
| F3 | Confirmation of the MySQL user/database strategy for staging and production | Phase 1 assumed `root` on a local server and a database named `autosecure`. |

---

## Summary: what unblocks what

| Unblock | Enables |
| --- | --- |
| A1 (MySQL password) | Confirming migrations on MySQL; otherwise nothing further — the code is complete |
| B1.1 (integration shape) | The entire Phase 2 tracker build |
| B1.4–B1.9 | Live location, playback, remote shutdown, theft response |
| B2.2 (streaming method) | The entire Camera tab architecture |
| C1–C2 | Finder, bookings, commission, settlement |
| C3 | Coins earning and redemption |
| C5 | Any payment collection at all |
| D1 | AutoDoc entry point |
| F1 (Figma access) | Verifying the screen map and matching the design exactly |

Phase 2 (Security + Dashcam foundation) is **blocked** until B1.1–B1.9 and
B2.1–B2.2 are resolved. Phase 3 (Vehicle Care, which needs no provider) could begin
immediately in parallel — its schema, models and entitlement gate already exist.
