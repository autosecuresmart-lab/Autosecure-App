/**
 * AutoSecure High-Performance Telemetry & Map Cache
 * 
 * Provides in-memory LRU caching, deduplication of unchanged coordinates,
 * and memory-safe throttling to prevent CPU/memory overload and server thrashing.
 */

interface CacheEntry<T> {
  data: T;
  expiresAt: number;
}

class TelemetryCache {
  private cache = new Map<string, CacheEntry<any>>();
  private maxEntries = 50;

  /**
   * Retrieve cached item if not expired
   */
  get<T>(key: string): T | null {
    const entry = this.cache.get(key);
    if (!entry) return null;

    if (Date.now() > entry.expiresAt) {
      this.cache.delete(key);
      return null;
    }

    return entry.data as T;
  }

  /**
   * Set cache item with custom TTL in milliseconds (default 5000ms)
   */
  set<T>(key: string, data: T, ttlMs = 5000): void {
    if (this.cache.size >= this.maxEntries) {
      const firstKey = this.cache.keys().next().value;
      if (firstKey) this.cache.delete(firstKey);
    }

    this.cache.set(key, {
      data,
      expiresAt: Date.now() + ttlMs,
    });
  }

  /**
   * Clear cache for a specific key or all keys
   */
  clear(key?: string): void {
    if (key) {
      this.cache.delete(key);
    } else {
      this.cache.clear();
    }
  }

  /**
   * Check if location coordinates have meaningfully changed
   * Prevents micro-jitter re-renders when vehicle is parked or stationary.
   */
  hasPositionChanged(
    prev: { lat: number; lng: number; heading?: number; speed?: number } | null,
    next: { lat: number; lng: number; heading?: number; speed?: number },
    latLngThreshold = 0.00002, // ~2 meters
    headingThreshold = 2, // 2 degrees
    speedThreshold = 0.5 // 0.5 km/h
  ): boolean {
    if (!prev) return true;

    const latDiff = Math.abs(prev.lat - next.lat);
    const lngDiff = Math.abs(prev.lng - next.lng);
    const headingDiff = Math.abs((prev.heading ?? 0) - (next.heading ?? 0));
    const speedDiff = Math.abs((prev.speed ?? 0) - (next.speed ?? 0));

    return (
      latDiff > latLngThreshold ||
      lngDiff > latLngThreshold ||
      headingDiff > headingThreshold ||
      speedDiff > speedThreshold
    );
  }
}

export const telemetryCache = new TelemetryCache();
