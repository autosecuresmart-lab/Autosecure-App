import { API_BASE_URL, API_TIMEOUT_MS } from '../config/env';
import type { ApiErrorBody, ApiErrorCode } from './types';

/**
 * Typed error raised for any non-2xx response or transport failure.
 *
 * Screens branch on `code` rather than on HTTP status strings, e.g.
 * `premium_required` shows the upgrade prompt and `integration_pending` shows
 * the "waiting on provider documentation" notice.
 */
export class ApiError extends Error {
  public readonly status: number;

  public readonly code: ApiErrorCode;

  public readonly body: ApiErrorBody;

  constructor(status: number, body: ApiErrorBody, code: ApiErrorCode) {
    super(body.message ?? 'Something went wrong. Please try again.');
    this.name = 'ApiError';
    this.status = status;
    this.body = body;
    this.code = code;
  }

  /** The customer needs AUTOSECURE Premium for this action. */
  get isPremiumRequired(): boolean {
    return this.code === 'premium_required';
  }

  /** The feature depends on a provider integration that does not exist yet. */
  get isIntegrationPending(): boolean {
    return this.code === 'integration_pending';
  }

  /** The session is no longer valid and must be cleared. */
  get isUnauthenticated(): boolean {
    return this.status === 401;
  }

  /** Field-level validation errors keyed by field name. */
  get validationErrors(): Record<string, string[]> {
    return this.body.errors ?? {};
  }
}

type TokenProvider = () => string | null;

type UnauthorizedHandler = () => void;

const CODE_BY_STATUS: Record<number, ApiErrorCode> = {
  401: 'unauthenticated',
  402: 'premium_required',
  403: 'forbidden',
  404: 'forbidden',
  422: 'validation_error',
  429: 'rate_limited',
  501: 'integration_pending',
};

/**
 * Small fetch wrapper.
 *
 * Deliberately dependency-free: the AUTOSECURE API is small, versioned and
 * JSON-only, so an HTTP library would add weight without benefit.
 */
class ApiClient {
  private baseUrl: string;

  private tokenProvider: TokenProvider = () => null;

  private onUnauthorized: UnauthorizedHandler | null = null;

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  setTokenProvider(provider: TokenProvider): void {
    this.tokenProvider = provider;
  }

  /** Called when the API rejects the current token, so the app can sign out. */
  setUnauthorizedHandler(handler: UnauthorizedHandler | null): void {
    this.onUnauthorized = handler;
  }

  get<T>(path: string, init?: RequestInit): Promise<T> {
    return this.request<T>('GET', path, undefined, init);
  }

  post<T>(path: string, body?: unknown, init?: RequestInit): Promise<T> {
    return this.request<T>('POST', path, body, init);
  }

  put<T>(path: string, body?: unknown, init?: RequestInit): Promise<T> {
    return this.request<T>('PUT', path, body, init);
  }

  patch<T>(path: string, body?: unknown, init?: RequestInit): Promise<T> {
    return this.request<T>('PATCH', path, body, init);
  }

  delete<T>(path: string, init?: RequestInit): Promise<T> {
    return this.request<T>('DELETE', path, undefined, init);
  }

  private async request<T>(
    method: string,
    path: string,
    body?: unknown,
    init?: RequestInit,
  ): Promise<T> {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), API_TIMEOUT_MS);

    const token = this.tokenProvider();

    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...((init?.headers as Record<string, string> | undefined) ?? {}),
    };

    let response: Response;

    try {
      response = await fetch(`${this.baseUrl}${path}`, {
        ...init,
        method,
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
        signal: controller.signal,
      });
    } catch (error) {
      clearTimeout(timeout);

      const aborted = error instanceof Error && error.name === 'AbortError';

      throw new ApiError(
        0,
        {
          message: aborted
            ? 'The request timed out. Check your connection and try again.'
            : 'Cannot reach AUTOSECURE. Check your connection and try again.',
          code: 'network_error',
        },
        'network_error',
      );
    } finally {
      clearTimeout(timeout);
    }

    const payload = await this.parseBody(response);

    if (response.ok) {
      return payload as T;
    }

    const code = (payload as ApiErrorBody).code
      ?? CODE_BY_STATUS[response.status]
      ?? (response.status >= 500 ? 'server_error' : 'forbidden');

    if (response.status === 401) {
      this.onUnauthorized?.();
    }

    throw new ApiError(response.status, payload as ApiErrorBody, code);
  }

  private async parseBody(response: Response): Promise<unknown> {
    if (response.status === 204) {
      return {};
    }

    const text = await response.text();

    if (text === '') {
      return {};
    }

    try {
      return JSON.parse(text);
    } catch {
      return {
        message: 'AUTOSECURE returned an unexpected response.',
        code: 'server_error',
      } satisfies ApiErrorBody;
    }
  }
}

export const api = new ApiClient(API_BASE_URL);
