import { useFormContext } from "@inertiajs/react";
import { Field, Input, Stack } from "@larasell-dev/ui";
import { slugify } from "../../utils/slugify";
import { truncate } from "../../utils/truncate";
import { useCoupledField } from "../../utils/useCoupledField";
import VisibilityField from "../VisibilityField";
import { fbs } from "fbtee";

type Category = Pick<
  Larasell.Admin.Category.Data.CategoryDetailData,
  "name" | "slug" | "fallbackName" | "fallbackSlug" | "visible"
>;

/**
 * The fields of a category. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a category it starts empty and visible.
 */
export default function CategoryForm({ category }: { category?: Category }) {
  const errors = useFormContext()?.errors ?? {};
  // A new category's slug follows its name, as does one in a language it has no slug in yet; an
  // existing one keeps the slug its links use.
  const slug = useCoupledField([slugify, truncate(255), slugify], {
    enabled: !category || category.slug === null,
  });

  return (
    <Stack spacing={5}>
      {/* Side by side when the form is wide enough, stacked otherwise. It queries the form's
          own width, since the sidebars decide how much of the viewport it gets. */}
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs("Name", "Category form: label of the field for the category's name")}
            name="name"
            error={errors.name}
          >
            {/* Untranslated fields stay empty and show what the store shows instead. */}
            <Input
              {...slug.source}
              defaultValue={category?.name ?? undefined}
              placeholder={category?.name === null ? category.fallbackName : undefined}
              autoComplete="off"
            />
          </Field>

          <Field
            label={fbs(
              "Slug",
              "Category form: label of the field for the category's URL slug, the part of its storefront address made from its name",
            )}
            name="slug"
            error={errors.slug}
          >
            <Input
              {...slug.target}
              defaultValue={category?.slug ?? undefined}
              placeholder={category?.slug === null ? category.fallbackSlug : undefined}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
        </div>
      </div>

      <VisibilityField defaultVisible={category?.visible ?? true} error={errors.status} />
    </Stack>
  );
}
