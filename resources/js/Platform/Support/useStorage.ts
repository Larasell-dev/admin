import { useState, type Dispatch, type SetStateAction } from "react";

/** Where useStorage keeps its value: `localStorage`, `sessionStorage`, or anything shaped like them. */
export type StorageDriver = Pick<Storage, "getItem" | "setItem" | "removeItem">;

function restore<T>(driver: StorageDriver, key: string, initial: T): T {
  try {
    const stored = driver.getItem(key);
    return stored === null ? initial : (JSON.parse(stored) as T);
  } catch {
    return initial;
  }
}

/**
 * Like useState, but the value is kept in the driver under the key and restored from it on the
 * next page view. The initial value isn't stored, so changing the default later reaches everyone
 * who never changed it. Stored values are trusted as `T`; check them where a wrong one would hurt.
 */
export function useStorage<T>(
  key: string,
  initial: T,
  driver: StorageDriver = localStorage,
): [T, Dispatch<SetStateAction<T>>] {
  const [value, setValue] = useState(() => restore(driver, key, initial));

  const set: Dispatch<SetStateAction<T>> = (action) =>
    setValue((current) => {
      const next = action instanceof Function ? action(current) : action;
      const json = JSON.stringify(next);
      if (json === JSON.stringify(initial)) driver.removeItem(key);
      else driver.setItem(key, json);
      return next;
    });

  return [value, set];
}
