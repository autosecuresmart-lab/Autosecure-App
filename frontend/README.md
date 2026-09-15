# AUTOSECURE mobile app (`/frontend`)

React Native application built with **Expo SDK 54** (React Native 0.81, React 19.1).

## Run it

```bash
cd frontend
npm install
cp .env.example .env      # point EXPO_PUBLIC_API_URL at your Laravel instance
npm start                 # then press "a" (Android) or "i" (iOS)
```

The Laravel API must be running (`php artisan serve` in `/backend`).

- iOS simulator reaches the host on `http://localhost:8000`
- Android emulator reaches the host on `http://10.0.2.2:8000`
- A physical device needs your machine's LAN IP

## Checks

```bash
npm run typecheck         # tsc --noEmit
npx expo export --platform android   # verifies the Metro bundle builds
```

## Structure

```
App.tsx                         providers + root navigator
index.ts                        Expo entry point
src/
  api/
    client.ts                   fetch wrapper, ApiError, 401 handling
    endpoints.ts                typed endpoint map
    types.ts                    payload types (mirrors Laravel resources)
  auth/
    AuthContext.tsx             session state, entitlements, sign in/out
    sessionStore.ts             token in the device keychain
  components/
    ui.tsx                      design-system primitives
    PendingIntegration.tsx      honest placeholder for blocked modules
  config/env.ts                 base URL + pending-integration registry
  navigation/
    RootNavigator.tsx           auth flow + five tabs
    types.ts
  screens/                      Home, Security, Camera, Finder, Account, auth
  theme/index.ts                colour, spacing, type tokens
```

## What is real today

| Area | State |
| --- | --- |
| Sign in / register / session restore / sign out | Working against `/api/v1/auth/*` |
| Vehicles (list, add, remove) | Working, resolved by `uuid` |
| Subscription + entitlements display | Working, server-resolved |
| Coins wallet | Working (read-only) |
| Notifications | API client ready, screen arrives with Phase 2 |
| Security / tracker | Pending tracker API |
| Dashcam | Pending dashcam SDK/API |
| AutoDoc | Pending agreed integration level |
| Finder / bookings / payments | Pending commercial sign-off |
| Vehicle Care | Schema ready, endpoints arrive in Phase 3 |

Pending modules render `PendingIntegration`, which asks the API what is
outstanding and shows the live answer. No screen fakes a working feature.

## Rules this app follows

- Records are addressed by `uuid`; the numeric id is never used or displayed.
- Entitlements are always the server's answer — the app never decides what the
  customer may use.
- No provider credential, streaming token or payment secret is ever stored on
  the device. The API token lives in the keychain via `expo-secure-store`.

## Dependency advisories

`npm audit` reports 23 advisories (14 moderate, 9 high). They are **all transitive**
— under Expo's build tooling (`metro`, `@expo/cli`, `@expo/metro-config`, `postcss`,
`image-size`, `xcode`) and React Navigation (`query-string`,
`decode-uri-component`). None is in application code.

**Do not run `npm audit fix --force`.** It resolves them by moving off the pinned
SDK 54 line, which the project deliberately targets. The correct action is to pick
up upstream patches within the SDK 54 range as they are released:

```bash
npx expo install --check     # reports SDK-54-compatible updates
```
