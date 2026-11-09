import { useEffect, useRef, useState } from "react";

/**
 * Whether to show a spinner for something that is `pending`, such as an image loading. Nothing
 * shows for the first `delay` ms, so a quick load never flashes a spinner. Once it shows, it stays
 * at least `minDuration` ms, even when loading ends right after, so it never just blinks. Show
 * the content once it is neither pending nor showing the spinner.
 */
export function useDelayedSpinner(
  pending: boolean,
  { delay = 200, minDuration = 400 }: { delay?: number; minDuration?: number } = {},
): boolean {
  const [shown, setShown] = useState(false);
  const shownAt = useRef(0);

  useEffect(() => {
    if (pending && !shown) {
      const timer = setTimeout(() => {
        shownAt.current = Date.now();
        setShown(true);
      }, delay);
      return () => clearTimeout(timer);
    }
    if (!pending && shown) {
      const left = minDuration - (Date.now() - shownAt.current);
      const timer = setTimeout(() => setShown(false), Math.max(0, left));
      return () => clearTimeout(timer);
    }
  }, [pending, shown, delay, minDuration]);

  return shown;
}
