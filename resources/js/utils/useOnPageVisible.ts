import { useEffect, useEffectEvent } from "react";

/**
 * Calls `callback` whenever the page becomes visible again, e.g. when the user switches back to
 * its tab or restores the window. Always calls the latest callback.
 */
export function useOnPageVisible(callback: () => void): void {
  const onVisible = useEffectEvent(callback);

  useEffect(() => {
    const listener = () => {
      if (document.visibilityState === "visible") onVisible();
    };
    document.addEventListener("visibilitychange", listener);
    return () => document.removeEventListener("visibilitychange", listener);
  }, []);
}
