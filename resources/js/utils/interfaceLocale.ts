import { setupLocaleContext, type TranslationPromise } from "fbtee";

/**
 * The language the panel's own text, its buttons, menus and messages, is shown in. The server
 * picks it per request (see src/Platform/InterfaceLocale) and puts it in <html lang>, with its
 * direction in <html dir>. It has nothing to do with the content locale, the language products
 * and categories are edited in.
 */

/** The language the panel is written in, shown when there are no translations for another. */
export const sourceLocale = "en-US";

type Catalog = Record<string, Awaited<TranslationPromise>>;

// Written by `npm run translations:compile`, which `npm run dev`, `build` and `test` run first.
const catalogs = import.meta.glob<Catalog>("../translations/*.json", { import: "default" });

const translated = Object.keys(catalogs).map((file) =>
  file.slice("../translations/".length, -".json".length),
);

/** Loads the translations of the page's language before the panel renders, so it never shows in English first. */
export async function setUpInterfaceLocale(locale: string = document.documentElement.lang) {
  const { preloadLocale } = setupLocaleContext({
    availableLanguages: new Map([sourceLocale, ...translated].map((code) => [code, code])),
    clientLocales: [locale],
    sourceLocale,
    loadLocale: async (code) => (await catalogs[`../translations/${code}.json`]?.())?.[code] ?? {},
  });

  await preloadLocale();
}
