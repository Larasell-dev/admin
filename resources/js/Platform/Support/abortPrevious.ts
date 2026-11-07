/**
 * Passes `fn` a fresh AbortSignal on every call and aborts the previous call's signal, so only
 * the latest call runs to the end. `abort` aborts the latest call too.
 */
export function abortPrevious<A extends unknown[], R>(
  fn: (signal: AbortSignal, ...args: A) => Promise<R>,
) {
  let controller: AbortController | undefined;

  return Object.assign(
    (...args: A) => {
      controller?.abort();
      controller = new AbortController();
      return fn(controller.signal, ...args);
    },
    { abort: () => controller?.abort() },
  );
}
