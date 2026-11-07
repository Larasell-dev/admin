import { useCallback, useEffect, useEffectEvent, useRef } from "react";

/**
 * Calls `callback` every `ms` milliseconds while mounted, like https://usehooks.com/useinterval.
 * Always calls the latest callback, so passing a new one doesn't restart the timer; changing `ms`
 * does. Returns a function that stops the interval early.
 */
export function useInterval(callback: () => void, ms: number): () => void {
  const id = useRef<number | undefined>(undefined);
  const onInterval = useEffectEvent(callback);
  const clear = useCallback(() => window.clearInterval(id.current), []);

  useEffect(() => {
    id.current = window.setInterval(() => onInterval(), ms);
    return clear;
  }, [ms, clear]);

  return clear;
}
