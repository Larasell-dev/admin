/** Calls `fn` once calls stopped for `ms`, with the last call's arguments. `cancel` drops a pending call. */
export function debounce<A extends unknown[]>(fn: (...args: A) => void, ms: number) {
  let timer: ReturnType<typeof setTimeout> | undefined;

  return Object.assign(
    (...args: A) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), ms);
    },
    { cancel: () => clearTimeout(timer) },
  );
}
