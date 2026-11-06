import { useFormContext } from "@inertiajs/react";
import { extensions } from "../extensions";

type Section = Larasell.Admin.Platform.Sections.Data.SectionData;

/**
 * The sections that modules and plugins add to a page, in the server's order. Rendered inside
 * the page's Inertia `<Form>`, so their fields are saved, reset and marked unsaved with the
 * page's own. Sections whose component no extension registered are left out.
 */
export default function ExtensionSections({ sections }: { sections: Section[] }) {
  const errors: Record<string, string | undefined> = useFormContext()?.errors ?? {};

  return sections.map(({ page, key, props }) => {
    const Component = extensions.section(page, key);

    return Component ? (
      <Component
        key={`${page}/${key}`}
        props={props}
        // "items.0.title" is "sections[key][items][0][title]", which the form posts nested.
        name={(field) =>
          `sections[${key}]${field
            .split(".")
            .map((part) => `[${part}]`)
            .join("")}`
        }
        error={(field) => errors[`sections.${key}.${field}`]}
      />
    ) : null;
  });
}
