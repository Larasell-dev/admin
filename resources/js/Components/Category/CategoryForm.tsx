import { useFormContext } from "@inertiajs/react";
import { Field, Input, SectionStack, Stack } from "@larasell-dev/ui";
import type { ReactNode } from "react";
import ExtensionSections from "../ExtensionSections";
import VisibilityField from "../VisibilityField";
import { fbs } from "fbtee";

type Category = Pick<
  Larasell.Admin.Category.Data.CategoryDetailData,
  "name" | "fallbackName" | "visible"
>;

/**
 * The fields of a category. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a category it starts empty and visible.
 */
export default function CategoryForm({ category }: { category?: Category }) {
  const errors = useFormContext()?.errors ?? {};
  return (
    <Stack spacing={5}>
      <Field
        label={fbs("Name", "Category form: label of the field for the category's name")}
        name="name"
        error={errors.name}
      >
        {/* Untranslated fields stay empty and show what the store shows instead. */}
        <Input
          defaultValue={category?.name ?? undefined}
          placeholder={category?.name === null ? category.fallbackName : undefined}
          autoComplete="off"
        />
      </Field>

      <VisibilityField defaultVisible={category?.visible ?? true} error={errors.status} />
    </Stack>
  );
}

/** The category form's own sections, generated from the CategorySection enum. */
type Anchor = Larasell.Admin.Category.Enums.CategorySection;

/**
 * The category form's own sections with those that modules and plugins add, placed around them.
 * It must sit inside the Inertia `<Form>`, so the added sections' fields are saved with it.
 */
export function CategorySections({
  general,
  sections,
}: {
  /** The section with the category's name and visibility. */
  general: ReactNode;
  sections: Larasell.Admin.Platform.Sections.Data.SectionData[];
}) {
  // Every one of CategorySection's anchors: a new one fails to compile here until it's rendered.
  const anchors = ["general"] as const satisfies readonly Anchor[];

  return (
    <SectionStack>
      <ExtensionSections sections={sections} before="general" />
      {general}
      <ExtensionSections sections={sections} after="general" />
      <ExtensionSections sections={sections} anchors={anchors} />
    </SectionStack>
  );
}
