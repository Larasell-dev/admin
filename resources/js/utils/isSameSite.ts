/** Whether the URL points at this site, so Inertia can visit it without a full page load. */
export function isSameSite(url: string): boolean {
  return new URL(url, window.location.href).origin === window.location.origin;
}
