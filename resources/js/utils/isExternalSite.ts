import { isSameSite } from "./isSameSite";

/** Whether the URL points at another site, so it needs a full page load instead of an Inertia visit. */
export function isExternalSite(url: string): boolean {
  return !isSameSite(url);
}
