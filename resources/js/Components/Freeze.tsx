import { type ReactNode, useEffect, useRef } from "react";

/**
 * Keeps rendering its children as they were when `frozen` turned on, so content leaving
 * with an exit animation does not change mid-animation. Returning the same element lets
 * React skip the subtree; only their own state or context updates still get through.
 */
export default function Freeze({ frozen, children }: { frozen: boolean; children: ReactNode }) {
  return useFrozen(children, frozen);
}

/** The value as it was when `frozen` turned on, for content that reads it from context. */
export function useFrozen<T>(value: T, frozen: boolean): T {
  const last = useRef(value);
  useEffect(() => {
    if (!frozen) last.current = value;
  });
  return frozen ? last.current : value;
}
