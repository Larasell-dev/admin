import { type ReactNode, useEffect, useRef } from "react";

/**
 * Keeps rendering its children as they were when `frozen` turned on, so content leaving
 * with an exit animation does not change mid-animation. Returning the same element lets
 * React skip the subtree; only their own state or context updates still get through.
 */
export default function Freeze({ frozen, children }: { frozen: boolean; children: ReactNode }) {
  const last = useRef(children);
  useEffect(() => {
    if (!frozen) last.current = children;
  });
  return frozen ? last.current : children;
}
