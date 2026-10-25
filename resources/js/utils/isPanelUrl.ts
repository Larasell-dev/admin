/**
 * Whether the URL is a page of the admin panel, so Inertia can visit it without a full page load.
 * Other URLs, even on this site, such as stored files or the storefront, are not Inertia pages.
 */
export function isPanelUrl(url: string, panelUrl: string): boolean {
  const target = new URL(url, window.location.href);
  const panel = new URL(panelUrl, window.location.href);
  const base = panel.pathname.replace(/\/$/, "");

  return (
    target.origin === panel.origin &&
    (target.pathname === base || target.pathname.startsWith(`${base}/`))
  );
}
