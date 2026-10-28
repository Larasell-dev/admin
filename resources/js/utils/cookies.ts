import { createContext, use } from "react";

/** Reads and writes cookies by name, for the whole site (path `/`). */
export interface Cookies {
  /** The decoded value, or undefined when the cookie isn't set. */
  get(name: string): string | undefined;
  /** Without `maxAge`, the cookie lasts until the browser closes. */
  set(name: string, value: string, options?: { maxAge?: number }): void;
  delete(name: string): void;
}

/** The browser's cookies, through `document.cookie`. */
export const documentCookies: Cookies = {
  get(name) {
    for (const cookie of document.cookie.split("; ")) {
      const separator = cookie.indexOf("=");
      if (decodeURIComponent(cookie.slice(0, separator)) === name) {
        return decodeURIComponent(cookie.slice(separator + 1));
      }
    }
    return undefined;
  },
  set(name, value, { maxAge } = {}) {
    const lifetime = maxAge === undefined ? "" : `; max-age=${maxAge}`;
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; path=/${lifetime}`;
  },
  delete(name) {
    documentCookies.set(name, "", { maxAge: 0 });
  },
};

/** Cookies kept in memory, e.g. to stub the browser's in tests. */
export function memoryCookies(initial: Record<string, string> = {}): Cookies {
  const values = new Map(Object.entries(initial));
  return {
    get: (name) => values.get(name),
    set: (name, value, { maxAge } = {}) => {
      if (maxAge !== undefined && maxAge <= 0) values.delete(name);
      else values.set(name, value);
    },
    delete: (name) => values.delete(name),
  };
}

/** The browser's cookies by default; render `<CookiesContext value={...}>` to swap them. */
export const CookiesContext = createContext<Cookies>(documentCookies);

export function useCookies(): Cookies {
  return use(CookiesContext);
}
