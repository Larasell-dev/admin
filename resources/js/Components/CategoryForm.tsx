import { useFormContext } from "@inertiajs/react";
import { Field, Input, Stack } from "@larasell-dev/ui";
import { slugify } from "../utils/slugify";
import { truncate } from "../utils/truncate";
import { useCoupledField } from "../utils/useCoupledField";
import VisibilityField from "./VisibilityField";

type Category = Pick<Larasell.Admin.Data.ViewModel.CategoryDetailData, "name" | "slug" | "visible">;

/**
 * The fields of a category. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a category it starts empty and visible.
 */
export default function CategoryForm({ category }: { category?: Category }) {
  const errors = useFormContext()?.errors ?? {};
  const slug = useCoupledField([slugify, truncate(255), slugify], { enabled: !category });

  return (
    <Stack spacing={5}>
      {/* Side by side when the form is wide enough, stacked otherwise. It queries the form's
          own width, since the sidebars decide how much of the viewport it gets. */}
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Name" name="name" error={errors.name}>
            <Input {...slug.source} defaultValue={category?.name} autoComplete="off" />
          </Field>

          <Field label="Slug" name="slug" error={errors.slug}>
            <Input
              {...slug.target}
              defaultValue={category?.slug}
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
