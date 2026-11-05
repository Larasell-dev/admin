import { Translate2 } from "@larasell-dev/ui/icons";
import { router, usePage } from "@inertiajs/react";
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
      label: "Change content language",
      icon: Translate2,
      keywords: ["locale", "translation"],
      placeholder: "Search languages",
      // Switching reloads the page, which would drop the changes.
      disabled: unsavedChanges && "Save or discard your changes first",
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
