import { router, usePage } from "@inertiajs/react";
import { GlobalLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { SharedProps } from "../../../../types";
import { useUnsavedChanges } from "../UnsavedChangesToast";
import { defineCommand } from "./commands";

/**
 * Switches the language of the panel itself, its buttons, menus and messages. Not the content
 * language, which products and categories are edited in. The server offers the languages, the
 * pseudo-languages only while it runs locally.
 */
export const interfaceLocaleCommand = defineCommand({
  id: "interface-locale",
  use() {
    const { interfaceLocale } = usePage<SharedProps>().props;
    const unsavedChanges = useUnsavedChanges();

    if (!interfaceLocale || interfaceLocale.options.length < 2) return null;

    const names = new Intl.DisplayNames([interfaceLocale.current], { type: "language" });

    return {
      label: fbs(
        "Change interface language",
        "Command menu: switches the language the admin panel's own buttons, menus and messages are shown in",
      ),
      icon: GlobalLine,
      keywords: [
        fbs(
          "language",
          "Command menu: search keyword for the command that switches the interface language",
        ),
        fbs(
          "translation",
          "Command menu: search keyword for the command that switches the interface language",
        ),
      ],
      placeholder: fbs(
        "Search languages",
        "Command menu: search field placeholder in the list of interface languages",
      ),
      // Switching reloads the page, which would drop the changes.
      disabled:
        unsavedChanges &&
        fbs(
          "Save or discard your changes first",
          "Command menu: why switching the language is disabled while a form has unsaved changes",
        ),
      items: interfaceLocale.options.map((option) => ({
        id: option.value,
        label: option.label,
        // Also findable by its name in the language the panel is in, e.g. "German" for Deutsch.
        keywords: [names.of(option.value) ?? option.value],
        checked: option.value === interfaceLocale.current,
        // The server answers with a full reload, so the page comes back in the language.
        run: () => router.put(interfaceLocale.switchUrl, { locale: option.value }),
      })),
    };
  },
});
