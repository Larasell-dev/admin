import { expect, it } from "vitest";
import { setUpInterfaceLocale } from "./interfaceLocale";
import { Theme, themeLabel } from "./theme";

it("shows the panel in the page's language", async () => {
  await setUpInterfaceLocale("de-DE");

  expect(themeLabel(Theme.Dark)).toBe("Dunkel");
});

it("runs the pseudo-language's text right to left", async () => {
  await setUpInterfaceLocale("ar-XB");

  expect(themeLabel(Theme.Dark)).toBe("\u202EDark\u202C");
});

it("stays in English in a language it has no translations for", async () => {
  await setUpInterfaceLocale("xx-XX");

  expect(themeLabel(Theme.Dark)).toBe("Dark");
});
