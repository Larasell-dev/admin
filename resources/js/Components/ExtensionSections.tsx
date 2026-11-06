import { useFormContext } from "@inertiajs/react";
import { extensions } from "../extensions";

type Section = Larasell.Admin.Platform.Sections.Data.SectionData;

/**
 * The sections that modules and plugins add to a form, in the server's order, placed before or
 * after one of the form's own sections, or, with neither, after all of them. Rendered inside the
 * form's Inertia `<Form>`, so their fields are saved, reset and marked unsaved with the form's
 * own.
 *
 * Every section the server sends must show. One whose component no extension registered, or
 * whose anchor is none of the form's `anchors`, is a bug: it throws in development and is logged
 * and left out in production, where the rest of the form keeps working.
 */
export default function ExtensionSections({
  sections,
  before,
  after,
  anchors,
}: {
  sections: Section[];
  before?: string;
  after?: string;
  /** For the outlet after all of the form's sections: the anchors the form rendered outlets at. */
  anchors?: readonly string[];
}) {
  const errors: Record<string, string | undefined> = useFormContext()?.errors ?? {};

  sections
    .filter(({ before, after }) => {
      const anchor = before ?? after;

      return anchors !== undefined && anchor !== null && !anchors.includes(anchor);
    })
    .forEach(({ form, key, before, after }) =>
      broken(
        `The section [${form}/${key}] is placed at [${before ?? after}], which the form doesn't render.`,
      ),
    );
  const here = sections.filter((section) =>
    before !== undefined
      ? section.before === before
      : after !== undefined
        ? section.after === after
        : section.before === null && section.after === null,
  );

  return here.map(({ form, key, props }) => {
    const Component = extensions.section(form, key);

    if (!Component) {
      broken(
        `No extension registered a component for the section [${form}/${key}]. Register it with sections.register() while booting.`,
      );
    }

    return Component ? (
      <Component
        key={`${form}/${key}`}
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

function broken(message: string): void {
  if (import.meta.env.DEV) {
    throw new Error(message);
  }

  console.error(message);
}
