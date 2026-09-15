import * as SecureStore from 'expo-secure-store';

import type { Entitlements, User } from '../api/types';

/**
 * Session storage.
 *
 * The API token lives in the platform keychain/keystore via expo-secure-store,
 * never in plain AsyncStorage. Non-sensitive profile data is cached alongside it
 * so the app can render immediately on cold start.
 */

const TOKEN_KEY = 'autosecure.api_token';
const PROFILE_KEY = 'autosecure.profile';

export interface StoredProfile {
  user: User;
  entitlements: Entitlements;
}

export async function saveToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function readToken(): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(TOKEN_KEY);
  } catch {
    return null;
  }
}

export async function clearToken(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
  } catch {
    // A missing keychain entry is not an error worth surfacing.
  }
}

export async function saveProfile(profile: StoredProfile): Promise<void> {
  try {
    await SecureStore.setItemAsync(PROFILE_KEY, JSON.stringify(profile));
  } catch {
    // Caching is best-effort; a failure must never block sign-in.
  }
}

export async function readProfile(): Promise<StoredProfile | null> {
  try {
    const raw = await SecureStore.getItemAsync(PROFILE_KEY);

    return raw === null ? null : (JSON.parse(raw) as StoredProfile);
  } catch {
    return null;
  }
}

export async function clearProfile(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(PROFILE_KEY);
  } catch {
    // Ignore.
  }
}

export async function clearSession(): Promise<void> {
  await Promise.all([clearToken(), clearProfile()]);
}
