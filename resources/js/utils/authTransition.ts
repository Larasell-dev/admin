/** The visit's `viewTransition` option for signing in: zooms through the login page into the panel. */
export function signInTransition() {
  return authTransition("sign-in");
}

/** The visit's `viewTransition` option for signing out: the sign-in zoom, played backwards. */
export function signOutTransition() {
  return authTransition("sign-out");
}

/** Marks <html> with the direction while the page changes, for the styles in app.css. */
function authTransition(
  direction: "sign-in" | "sign-out",
): false | ((viewTransition: ViewTransition) => void) {
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    return false;
  }

  return (viewTransition) => {
    const root = document.documentElement;

    // Called before the browser captures the old page, so the styles apply to both pages.
    root.dataset.authTransition = direction;

    const cleanUp = () => delete root.dataset.authTransition;
    viewTransition.finished.then(cleanUp, cleanUp);
  };
}
