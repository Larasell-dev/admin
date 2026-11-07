import { router } from "@inertiajs/react";
import { Select } from "@larasell-dev/ui";
import { useUnsavedChanges } from "../../Panel/Components/UnsavedChangesToast";
import { fbs } from "fbtee";

type ContentLocale = Larasell.Admin.Platform.ContentLocale.Data.ContentLocaleData;

/**
 * Picks the language the page's content is edited in, not the panel's own, and reloads the page
 * in it. It is disabled while a form on the page has unsaved changes, which switching would drop,
 * wherever the select sits. Left out while there is only one language.
 */
export default function ContentLocaleSelect({
  contentLocale,
}: {
  contentLocale: ContentLocale | null;
}) {
  const unsavedChanges = useUnsavedChanges();

  if (!contentLocale || contentLocale.options.length < 2) return null;

  const current = contentLocale.options.find((option) => option.value === contentLocale.current);

  return (
    <div className="w-40">
      <Select.Root
        items={contentLocale.options}
        itemToKey={(option) => option.value}
        itemToLabel={(option) => option.label}
        value={current ?? null}
        onValueChange={(option) => {
          if (!option || option.value === contentLocale.current) return;

          router.put(contentLocale.switchUrl, { locale: option.value }, { preserveScroll: true });
        }}
        filter={{
          placeholder: fbs(
            "Search languages",
            "Product and category pages: placeholder of the search field in the content language select's list",
          ),
          empty: (
            <fbt desc="Product and category pages: shown in the content language select's list when the search matches no language">
              No languages found
            </fbt>
          ),
        }}
        disabled={unsavedChanges}
      >
        <Select.Trigger
          aria-label={fbs(
            "Content language",
            "Product and category pages: accessible name of the select that picks the language the content is edited in, not the panel's own language",
          )}
          DANGEROUS_className="w-full"
        />
        <Select.Popup>
          {(option: (typeof contentLocale.options)[number]) => (
            <Select.Item key={option.value} item={option} />
          )}
        </Select.Popup>
      </Select.Root>
    </div>
  );
}
