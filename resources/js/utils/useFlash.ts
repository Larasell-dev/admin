import { usePage } from "@inertiajs/react";

/**
 * The value flashed under `key` for this visit, e.g. by `Inertia::flash()`, or undefined.
 * Flash data stays out of the browser history, so it is gone on the next visit.
 */
export function useFlash<T>(key: string): T | undefined {
  // Flash data is untyped on the client; the caller names the type the server sends.
  return (usePage().flash as Record<string, unknown>)[key] as T | undefined;
}
