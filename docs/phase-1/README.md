# AUTOSECURE 2.0 — Phase 1 documentation

Discovery & Architecture. Read in this order:

| Document | Contents |
| --- | --- |
| [ARCHITECTURE.md](ARCHITECTURE.md) | System shape, Laravel layering, module map, decisions and trade-offs |
| [DATABASE.md](DATABASE.md) | Every table, the `id` + `uuid` rule, relationships, MySQL setup |
| [API.md](API.md) | Mobile API surface under `/api/v1` — implemented vs pending |
| [ADMIN.md](ADMIN.md) | `/manage` portal modules, roles and the permission matrix |
| [FRONTEND-MAP.md](FRONTEND-MAP.md) | Figma/mobile screens mapped to AUTOSECURE 2.0 modules: existing, missing, nav structure |
| [DEVICE-INTEGRATION.md](DEVICE-INTEGRATION.md) | Tracker & dashcam integration design and the exact information still required |
| [SECURITY.md](SECURITY.md) | Auth, authorisation, sensitive-data access, device command safety |
| [PENDING-INFORMATION.md](PENDING-INFORMATION.md) | Everything blocked on external input, and who owns it |

Companion documents in the repository root:

- `AUTOSECURE_2.0_Product_Upgrade_Proposal.docx` — the approved product requirements
- `GPRS Communication Protocol for implementing features.pdf` — device protocol reference

---

## Status at a glance

| Area | State |
| --- | --- |
| Laravel backend (`/backend`) | Built — Laravel 13.31, MySQL, 36 tables, 50 passing tests |
| React Native app (`/frontend`) | Built — Expo SDK 54, 5-tab shell, type-checks and bundles |
| `/manage` admin portal | Built — separate admin guard, 14 module routes, 46 permissions |
| Tracker integration | **Pending** — no API documentation supplied |
| Dashcam integration | **Pending** — no SDK/API documentation supplied |
| AutoDoc integration | **Pending** — integration level not agreed |
| Payments / Finder commercials | **Pending** — figures awaiting management sign-off |
