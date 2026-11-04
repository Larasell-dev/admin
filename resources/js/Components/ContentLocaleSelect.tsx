import { router } from "@inertiajs/react";
import { Input } from "@larasell-dev/ui";
import { useUnsavedChanges } from "./UnsavedChangesToast";

type ContentLocale = Larasell.Admin.Data.ViewModel.ContentLocale.ContentLocaleData;

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

  return (
    <div className="w-40">
      <Input
        aria-label="Content language"
        defaultValue={contentLocale.current}
        disabled={unsavedChanges}
        render={
          // On the select, which types its own change events; Input types them for an input.
          <select
            onChange={(event) =>
              router.put(
                contentLocale.switchUrl,
                { locale: event.currentTarget.value },
                { preserveScroll: true },
              )
            }
          >
            {contentLocale.options.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        }
      />
    </div>
  );
}
