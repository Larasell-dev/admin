import { type ReactNode, useState } from "react";

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
  // Kept in state rather than a ref, so render never reads a value that changes outside React.
  const [kept, setKept] = useState(value);
  if (!frozen && kept !== value) setKept(value);
  return frozen ? kept : value;
}
