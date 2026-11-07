import { type Ref, type RefCallback, useCallback } from "react";

type PossibleRef<T> = Ref<T> | undefined;

/** Gives the value to the ref, returning the cleanup a callback ref may return. */
function assignRef<T>(ref: PossibleRef<T>, value: T | null): void | (() => void) {
  if (typeof ref === "function") return ref(value);
  if (ref) ref.current = value;
}

/**
 * One callback ref that gives the element to every ref, e.g. a component's own and a library's.
 * Taking the element back runs each callback ref's cleanup, or gives null to those without one.
 */
export function mergeRefs<T>(...refs: PossibleRef<T>[]): RefCallback<T> {
  return (value) => {
    const cleanups = refs.map((ref) => assignRef(ref, value));
    return () =>
      refs.forEach((ref, index) => {
        const cleanup = cleanups[index];
        if (cleanup) cleanup();
        else assignRef(ref, null);
      });
  };
}

/** `mergeRefs` that keeps the same callback while the refs stay the same, so React doesn't reattach it. */
export function useMergedRef<T>(...refs: PossibleRef<T>[]): RefCallback<T> {
  // The refs themselves are the dependencies, however many there are.
  // oxlint-disable-next-line react/use-memo, react-hooks/exhaustive-deps
  return useCallback(mergeRefs(...refs), refs);
}
