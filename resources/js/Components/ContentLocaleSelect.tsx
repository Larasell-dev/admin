import { router, useFormContext } from "@inertiajs/react";
import { Input } from "@larasell-dev/ui";

type ContentLocale = Larasell.Admin.Data.ViewModel.ContentLocale.ContentLocaleData;

/**
 * Picks the language the page's content is edited in, not the panel's own, and reloads the page
 * in it. Inside an Inertia `<Form>` it is disabled while the form has unsaved changes, which
 * switching would drop. Left out while there is only one language.
 */
export default function ContentLocaleSelect({ contentLocale }: { contentLocale: ContentLocale }) {
  const form = useFormContext();

  if (contentLocale.options.length < 2) return null;

  return (
    <div className="w-40">
      <Input
        aria-label="Content language"
        defaultValue={contentLocale.current}
        disabled={form?.isDirty || form?.processing}
        onChange={(event) =>
          router.put(
            contentLocale.switchUrl,
            { locale: (event.currentTarget as unknown as HTMLSelectElement).value },
            { preserveScroll: true },
          )
        }
        render={
          <select>
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
