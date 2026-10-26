import { Field as BaseField } from "@base-ui/react/field";
import { useFormContext, useHttp } from "@inertiajs/react";
import { cn } from "cn";
import {
  Badge,
  Checkbox,
  Error,
  Field,
  Icon,
  Input,
  Section,
  SectionStack,
  Spinner,
  Stack,
} from "@larasell-dev/ui";
import { AddLine, ArrowDownSLine } from "@larasell-dev/ui/icons";
import {
  type ChangeEventHandler,
  type Ref,
  type RefObject,
  useEffect,
  useEffectEvent,
  useRef,
  useState,
} from "react";
import { slugify } from "../utils/slugify";
import { truncate } from "../utils/truncate";
import { useCoupledField } from "../utils/useCoupledField";
import CategoryCheckboxTree from "./CategoryCheckboxTree";
import FormCheckbox from "./FormCheckbox";
import VisibilityField from "./VisibilityField";

type Product = Omit<Larasell.Admin.Data.ViewModel.ProductDetailData, "id">;
type Category = Larasell.Admin.Data.ViewModel.CategoryData;
type Image = Larasell.Admin.Data.ViewModel.ProductImageData;
type Errors = Record<string, string | undefined>;

/**
 * The sections of a product's fields. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a product it starts empty, visible and in no category.
 */
export default function ProductForm({
  product,
  categories,
  createCategoryUrl,
  uploadImagesUrl,
}: {
  product?: Product;
  /** The store's categories to pick the product's from. */
  categories: Category[];
  /** Where the store's first categories are created, offered while it has none. */
  createCategoryUrl: string;
  /** Where images are uploaded before saving the product links them. */
  uploadImagesUrl: string;
}) {
  const errors: Errors = useFormContext()?.errors ?? {};

  return (
    <SectionStack>
      <Section.Root>
        <Section.Header
          title="General"
          description="How the product is named and found in the store."
        />
        <GeneralFields product={product} errors={errors} />
      </Section.Root>
      <Section.Root>
        <Section.Header title="Images" description="The first image is the product's thumbnail." />
        <ImageGrid images={product?.images ?? []} uploadUrl={uploadImagesUrl} />
      </Section.Root>
      <Section.Root>
        <Section.Header
          title="Categories"
          description="Where the product is listed in the store."
        />
        <CategoryCheckboxTree
          categories={categories}
          defaultSelectedIds={product?.categoryIds ?? []}
          createUrl={createCategoryUrl}
        />
      </Section.Root>
      <Section.Root>
        <Section.Header
          title="Stock"
          description="How many can be sold, and how many a customer can buy at once."
        />
        <StockFields product={product} errors={errors} />
      </Section.Root>
      <Section.Root>
        <Section.Header
          title="Weight and dimensions"
          description="Used to choose shipping methods and their prices. Leave them empty for products that aren't shipped."
        />
        <WeightAndDimensionsFields product={product} errors={errors} />
      </Section.Root>
      <Section.Root>
        <Section.Header
          title="SKU and barcode"
          description="Identify the product in your inventory and when it's scanned. Each belongs to one product only."
        />
        <IdentifierFields product={product} errors={errors} />
      </Section.Root>
    </SectionStack>
  );
}

function GeneralFields({ product, errors }: { product?: Product; errors: Errors }) {
  // A new product's slug follows its name; an existing one keeps the slug its links use.
  const slug = useCoupledField([slugify, truncate(255), slugify], { enabled: !product });

  return (
    <Stack spacing={5}>
      {/* Side by side when the form is wide enough, stacked otherwise. */}
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Name" name="name" error={errors.name}>
            <Input {...slug.source} defaultValue={product?.name} autoComplete="off" />
          </Field>

          <Field label="Slug" name="slug" error={errors.slug}>
            <Input
              {...slug.target}
              defaultValue={product?.slug}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
        </div>
      </div>

      <VisibilityField defaultVisible={product?.visible ?? true} error={errors.status} />
    </Stack>
  );
}

/** The file types the server accepts. */
const IMAGE_TYPES = "image/jpeg,image/png,image/webp,image/avif";

/**
 * The product's images in its order, then a square that opens the system's file picker. The
 * chosen images are uploaded right away, with a plain request since the unsaved changes toast
 * blocks Inertia visits, and join the grid. The form submits the grid's image ids in order, so
 * saving the product links them; until then, cancelling the changes drops them again.
 */
function ImageGrid({ images: savedImages, uploadUrl }: { images: Image[]; uploadUrl: string }) {
  const [images, setImages] = useState(savedImages);
  const grid = useRef<HTMLUListElement>(null);
  const upload = useHttp<{ images: File[] }, { images: Image[] }>({ images: [] });
  // One message per kind of problem, however many images have it.
  const errors = [...new Set(Object.values(upload.errors as Errors))];

  // Inertia's <Form> only rechecks whether it's dirty on input, change and reset events, which
  // inputs React adds or removes don't fire. Without this, uploading wouldn't show the unsaved
  // changes toast.
  useEffect(() => {
    grid.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [images]);

  // Cancelling the changes resets the form, which puts its fields back to their saved values but
  // can't remove the hidden inputs of images uploaded since. Going back to the saved images does.
  useFormReset(grid, () => {
    upload.clearErrors();
    setImages(savedImages);
  });

  async function uploadFiles(input: HTMLInputElement) {
    const files = [...(input.files ?? [])];
    // Lets the same files be chosen again.
    input.value = "";
    if (files.length === 0) return;

    upload.clearErrors();
    upload.transform(() => ({ images: files }));

    try {
      // Undefined when the images are invalid; the errors say why.
      const response = await upload.post(uploadUrl);
      if (response) setImages((current) => [...current, ...response.images]);
    } catch {
      upload.setError("images", "The images couldn't be uploaded. Try again.");
    }
  }

  return (
    <BaseField.Root invalid={errors.length > 0} className="flex flex-col gap-1.5">
      {/* Fewer columns as the form narrows, so the images stay large enough to tell apart. */}
      <div className="@container">
        <ul
          ref={grid}
          aria-label="Images"
          className="grid auto-rows-fr grid-cols-1 gap-3 @xs:grid-cols-2 @lg:grid-cols-3 @2xl:grid-cols-4"
        >
          {images.map((image, index) => (
            <li
              key={image.id}
              className="border-border relative aspect-square overflow-hidden border bg-mauve-50 dark:bg-mauve-900"
            >
              <input type="hidden" name="images[]" value={image.id} />
              <img src={image.url} alt={`Image ${index + 1}`} className="size-full object-cover" />
              {index === 0 && (
                <div className="absolute top-2 left-2">
                  <Badge>Thumbnail</Badge>
                </div>
              )}
            </li>
          ))}
          <li>
            {/* A label, so clicking anywhere on the square opens the file picker. */}
            <label
              className={cn(
                // A square with the icon in its middle. Relative keeps the hidden file input inside
                // it; otherwise focusing the input scrolls the whole page to where it would be.
                "relative flex aspect-square items-center justify-center text-2xl",
                "border border-dashed border-mauve-300 dark:border-mauve-600",
                "text-mauve-600 dark:text-mauve-300",
                // Clickable while no upload is running
                "cursor-pointer has-disabled:cursor-default",
                "hover:bg-mauve-50 dark:hover:bg-mauve-800",
                // Focus is on the hidden file input inside
                "has-focus-visible:focus-ring",
              )}
            >
              <input
                type="file"
                multiple
                accept={IMAGE_TYPES}
                aria-label="Add images"
                disabled={upload.processing}
                className="sr-only"
                onChange={(event) => uploadFiles(event.currentTarget)}
              />
              {upload.processing ? (
                <Spinner aria-label="Uploading images" />
              ) : (
                <Icon icon={AddLine} />
              )}
            </label>
          </li>
        </ul>
      </div>
      {errors.map((error) => (
        <Error key={error} match>
          {error}
        </Error>
      ))}
    </BaseField.Root>
  );
}

/** Calls `onReset` whenever the form around `element` is reset, e.g. by cancelling its changes. */
function useFormReset(element: RefObject<HTMLElement | null>, onReset: () => void) {
  // Always the latest callback, without listening anew on every render.
  const reset = useEffectEvent(onReset);

  useEffect(() => {
    const form = element.current?.closest("form");
    if (!form) return;
    form.addEventListener("reset", reset);
    return () => form.removeEventListener("reset", reset);
  }, [element]);
}

/** Both optional; the barcode is saved as its digits, without spaces or dashes. */
function IdentifierFields({ product, errors }: { product?: Product; errors: Errors }) {
  return (
    <div className="@container">
      <div className="grid items-start gap-5 @md:grid-cols-2">
        <Field label="SKU" name="sku" error={errors.sku}>
          <Input defaultValue={product?.sku ?? ""} autoComplete="off" spellCheck={false} />
        </Field>

        <Field label="Barcode" name="barcode" error={errors.barcode}>
          <Input defaultValue={product?.barcode ?? ""} autoComplete="off" spellCheck={false} />
        </Field>
      </div>
    </div>
  );
}

/** Every number is optional: without a stock it isn't tracked, without a limit there is none. */
function StockFields({ product, errors }: { product?: Product; errors: Errors }) {
  return (
    <Stack spacing={5}>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label="Stock"
            name="stock"
            error={errors.stock}
            description="Leave empty to sell without counting."
          >
            <Input defaultValue={product?.stock ?? ""} inputMode="numeric" autoComplete="off" />
          </Field>

          <BaseField.Root
            name="allow_backorders"
            invalid={!!errors.allow_backorders}
            // Relative keeps the checkbox's absolutely positioned hidden input inside the scrolling
            // content. Beside the stock, the padding skips its label to center on its input.
            className="relative flex flex-col gap-1.5 @md:pt-9"
          >
            <Checkbox.Label>
              <FormCheckbox
                name="allow_backorders"
                value="1"
                uncheckedValue="0"
                defaultChecked={product?.allowBackorders ?? true}
              />
              Allow backorders
            </Checkbox.Label>
            {errors.allow_backorders && <Error match>{errors.allow_backorders}</Error>}
          </BaseField.Root>
        </div>
      </div>

      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Minimum quantity" name="min_quantity" error={errors.min_quantity}>
            <Input
              defaultValue={product?.minQuantity ?? ""}
              inputMode="numeric"
              autoComplete="off"
            />
          </Field>

          <Field label="Maximum quantity" name="max_quantity" error={errors.max_quantity}>
            <Input
              defaultValue={product?.maxQuantity ?? ""}
              inputMode="numeric"
              autoComplete="off"
            />
          </Field>
        </div>
      </div>
    </Stack>
  );
}

const AXES = [
  ["length", "Length"],
  ["width", "Width"],
  ["height", "Height"],
] as const;

/** The units Larasell's WeightUnit and LengthUnit define. */
const WEIGHT_UNITS = ["g", "kg", "oz", "lb"];
const LENGTH_UNITS = ["mm", "cm", "m", "in", "ft"];

/**
 * Metric by default; saved values keep the unit they were entered in. Changing a unit only
 * relabels the amounts, it never converts them.
 */
function WeightAndDimensionsFields({ product, errors }: { product?: Product; errors: Errors }) {
  const weightUnit = product?.weight?.unit ?? "kg";
  const dimensionsUnit = product?.dimensions?.unit ?? "cm";
  // Dimensions share one unit, so every select shows it and changing one changes all.
  const dimensionsUnits = useRef<(HTMLSelectElement | null)[]>([]);
  const syncDimensionsUnits: ChangeEventHandler<HTMLSelectElement> = (event) => {
    for (const select of dimensionsUnits.current) {
      if (select) select.value = event.currentTarget.value;
    }
  };

  return (
    // More room than between the fields, so the weight and the dimensions read as two groups.
    <Stack spacing={12}>
      {/* The same two columns as the dimensions, with the weight in the first. They stack until
            the form is nearly its full width, since amounts with a unit need the room. */}
      <div className="@container">
        <div className="grid items-start gap-5 @min-[40rem]:grid-cols-2">
          <Field
            label="Weight"
            name="weight[amount]"
            error={errors["weight.amount"] ?? errors["weight.unit"]}
          >
            <Input
              defaultValue={product?.weight?.amount}
              inputMode="decimal"
              autoComplete="off"
              suffix={
                <UnitSelect
                  name="weight[unit]"
                  label="Weight unit"
                  units={WEIGHT_UNITS}
                  defaultValue={weightUnit}
                />
              }
            />
          </Field>
        </div>
      </div>

      <div className="@container">
        <div className="grid items-start gap-5 @min-[40rem]:grid-cols-2">
          {AXES.map(([axis, label], index) => (
            <Field
              key={axis}
              label={label}
              name={`dimensions[${axis}]`}
              error={
                errors[`dimensions.${axis}`] ??
                (index === 0 ? errors["dimensions.unit"] : undefined)
              }
            >
              <Input
                defaultValue={product?.dimensions?.[axis]}
                inputMode="decimal"
                autoComplete="off"
                // Only the first unit select is submitted.
                suffix={
                  <UnitSelect
                    ref={(select) => {
                      dimensionsUnits.current[index] = select;
                    }}
                    onChange={syncDimensionsUnits}
                    name={index === 0 ? "dimensions[unit]" : undefined}
                    label={`${label} unit`}
                    units={LENGTH_UNITS}
                    defaultValue={dimensionsUnit}
                  />
                }
              />
            </Field>
          ))}
        </div>
      </div>
    </Stack>
  );
}

/**
 * A native select in an Input's suffix, since the UI library has no select. It's styled like the
 * PasswordInput's visibility toggle, filling the input's height up to its right edge, with a
 * border that separates it from the amount and our own chevron in place of the browser's.
 * Errors show on the amount, since the unit is always one of the options.
 */
function UnitSelect({
  label,
  units,
  ref,
  ...props
}: {
  /** Left out for selects that only mirror another one's unit. */
  name?: string;
  /** The accessible name, which says what the unit is for. */
  label: string;
  units: string[];
  defaultValue: string;
  ref?: Ref<HTMLSelectElement>;
  onChange?: ChangeEventHandler<HTMLSelectElement>;
}) {
  return (
    // The wrapper takes the suffix's place; the select fills it so the whole area opens it.
    <div className="border-border relative -mr-3.5 flex self-stretch border-l">
      <select
        {...props}
        ref={ref}
        aria-label={label}
        className="type-input-md cursor-pointer appearance-none bg-transparent pr-9 pl-3.5 text-mauve-950 select-none hover:bg-mauve-100 focus-visible:focus-ring focus-visible:outline-offset-0 dark:text-mauve-100 dark:hover:bg-mauve-800"
      >
        {units.map((unit) => (
          <option key={unit} value={unit}>
            {unit}
          </option>
        ))}
      </select>
      {/* Clicks pass through to the select underneath. */}
      <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-mauve-950 dark:text-mauve-100">
        <Icon icon={ArrowDownSLine} />
      </span>
    </div>
  );
}
