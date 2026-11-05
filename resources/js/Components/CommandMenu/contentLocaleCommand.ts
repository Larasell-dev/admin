import { Translate2 } from "@larasell-dev/ui/icons";
import { router, usePage } from "@inertiajs/react";
import { fbs } from "fbtee";
import type { SharedProps } from "../../types";
import { useUnsavedChanges } from "../UnsavedChangesToast";
import { defineCommand } from "./commands";

/**
 * Switches the language content is edited in, on every page, like the select on product and
 * category pages. Left out while the store has only one language.
 */
export const contentLocaleCommand = defineCommand({
  id: "content-locale",
  use() {
    const { contentLocale } = usePage<SharedProps>().props;
    const unsavedChanges = useUnsavedChanges();

    if (!contentLocale || contentLocale.options.length < 2) return null;

    return {
      label: fbs(
        "Change content language",
        "Command menu: switches the language products and categories are edited in, not the language of the panel itself",
      ),
      icon: Translate2,
      keywords: [
        fbs(
          "locale",
          "Command menu: search keyword for the command that switches the content language",
        ),
        fbs(
          "translation",
          "Command menu: search keyword for the command that switches the content language",
        ),
      ],
      placeholder: fbs(
        "Search languages",
        "Command menu: search field placeholder in the list of content languages",
      ),
      // Switching reloads the page, which would drop the changes.
      disabled:
        unsavedChanges &&
        fbs(
          "Save or discard your changes first",
          "Command menu: why switching the content language is disabled while a form has unsaved changes",
        ),
      items: contentLocale.options.map((option) => ({
        id: option.value,
        label: option.label,
        checked: option.value === contentLocale.current,
        run: () =>
          router.put(contentLocale.switchUrl, { locale: option.value }, { preserveScroll: true }),
      })),
    };
  },
});
