import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { Platform } from 'react-native';

import { ApiError } from '../api/client';
import { api } from '../api/client';
import { authApi } from '../api/endpoints';
import type { Entitlements, User } from '../api/types';
import {
  clearSession,
  readProfile,
  readToken,
  saveProfile,
  saveToken,
} from './sessionStore';

interface SignInPayload {
  email: string;
  password: string;
}

interface SignUpPayload {
  name: string;
  first_name?: string;
  last_name?: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  account_type?: 'individual' | 'business';
  company_name?: string;
}

interface AuthContextValue {
  /** True until the stored session has been restored. */
  isRestoring: boolean;
  user: User | null;
  entitlements: Entitlements | null;
  isAuthenticated: boolean;
  /** Convenience wrapper around the server-side entitlement list. */
  can: (feature: string) => boolean;
  signIn: (payload: SignInPayload) => Promise<void>;
  signUp: (payload: SignUpPayload) => Promise<void>;
  signOut: () => Promise<void>;
  refreshProfile: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

/**
 * Session state for the mobile app.
 *
 * The token is held in memory and in the device keychain only. Entitlements are
 * always the server's answer — the app never decides for itself what a customer
 * is allowed to use.
 */
export function AuthProvider({ children }: { children: React.ReactNode }): React.JSX.Element {
  const [isRestoring, setIsRestoring] = useState(true);
  const [user, setUser] = useState<User | null>(null);
  const [entitlements, setEntitlements] = useState<Entitlements | null>(null);

  const tokenRef = useRef<string | null>(null);

  // The HTTP client asks for the current token instead of capturing it, so a
  // rotation never leaves stale credentials in a closure.
  useEffect(() => {
    api.setTokenProvider(() => tokenRef.current);
  }, []);

  const applySession = useCallback(
    async (token: string, nextUser: User, nextEntitlements: Entitlements) => {
      tokenRef.current = token;
      setUser(nextUser);
      setEntitlements(nextEntitlements);

      await saveToken(token);
      await saveProfile({ user: nextUser, entitlements: nextEntitlements });
    },
    [],
  );

  const clearLocalSession = useCallback(async () => {
    tokenRef.current = null;
    setUser(null);
    setEntitlements(null);

    await clearSession();
  }, []);

  // A rejected token must never leave the app in a signed-in looking state.
  useEffect(() => {
    api.setUnauthorizedHandler(() => {
      void clearLocalSession();
    });

    return () => api.setUnauthorizedHandler(null);
  }, [clearLocalSession]);

  // Restore a previous session on cold start.
  useEffect(() => {
    let cancelled = false;

    const restore = async () => {
      try {
        const token = await readToken();

        if (token === null) {
          return;
        }

        tokenRef.current = token;

        const cached = await readProfile();

        if (!cancelled && cached !== null) {
          setUser(cached.user);
          setEntitlements(cached.entitlements);
        }

        // Confirm the token is still valid and pick up entitlement changes.
        const fresh = await authApi.me();

        if (!cancelled) {
          setUser(fresh.user);
          setEntitlements(fresh.entitlements);
          await saveProfile({ user: fresh.user, entitlements: fresh.entitlements });
        }
      } catch (error) {
        if (error instanceof ApiError && error.isUnauthenticated) {
          await clearLocalSession();
        }
        // Any other failure (offline, server down) keeps the cached session so
        // the customer still sees their app.
      } finally {
        if (!cancelled) {
          setIsRestoring(false);
        }
      }
    };

    void restore();

    return () => {
      cancelled = true;
    };
  }, [clearLocalSession]);

  const devicePayload = useCallback(
    () => ({
      device_name: `${Platform.OS} device`,
      platform: Platform.OS as 'ios' | 'android' | 'web',
    }),
    [],
  );

  const signIn = useCallback(
    async ({ email, password }: SignInPayload) => {
      const session = await authApi.login({ email, password, ...devicePayload() });

      await applySession(session.token, session.user, session.entitlements);
    },
    [applySession, devicePayload],
  );

  const signUp = useCallback(
    async (payload: SignUpPayload) => {
      const session = await authApi.register({ ...payload, ...devicePayload() });

      await applySession(session.token, session.user, session.entitlements);
    },
    [applySession, devicePayload],
  );

  const signOut = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      // Even if the server call fails the local session must be cleared.
    }

    await clearLocalSession();
  }, [clearLocalSession]);

  const refreshProfile = useCallback(async () => {
    if (tokenRef.current === null) {
      return;
    }

    const fresh = await authApi.me();

    setUser(fresh.user);
    setEntitlements(fresh.entitlements);

    await saveProfile({ user: fresh.user, entitlements: fresh.entitlements });
  }, []);

  const can = useCallback(
    (feature: string) => entitlements?.features.includes(feature) ?? false,
    [entitlements],
  );

  const value = useMemo<AuthContextValue>(
    () => ({
      isRestoring,
      user,
      entitlements,
      isAuthenticated: user !== null,
      can,
      signIn,
      signUp,
      signOut,
      refreshProfile,
    }),
    [isRestoring, user, entitlements, can, signIn, signUp, signOut, refreshProfile],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (context === null) {
    throw new Error('useAuth must be used inside <AuthProvider>.');
  }

  return context;
}
