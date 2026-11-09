import type { ReactNode } from "react";
import { cn } from "cn";
import { useDelayedSpinner } from "../../Support/useDelayedSpinner";

/**
 * Shows `fallback` while `pending`, like Suspense, but only after `delay` ms and then for at least
 * `minDuration` ms (see useDelayedSpinner). The children stay mounted and hidden meanwhile, so an
 * image among them still loads and can say when it's done.
 */
export default function DelayedFallback({
  pending,
  fallback,
  delay,
  minDuration,
  children,
}: {
  pending: boolean;
  fallback: ReactNode;
  delay?: number;
  minDuration?: number;
  children: ReactNode;
}) {
  const spinner = useDelayedSpinner(pending, { delay, minDuration });

  return (
    <>
      {spinner && fallback}
      <div className={cn("contents", (pending || spinner) && "invisible")}>{children}</div>
    </>
  );
}
