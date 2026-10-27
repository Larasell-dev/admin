import { Field as BaseField } from "@base-ui/react/field";
import { router, useFormContext, useHttp } from "@inertiajs/react";
import { cn } from "cn";
import {
  Badge,
  Checkbox,
  Error,
  Field,
  Icon,
  IconButton,
  Input,
  Menu,
  Section,
  SectionStack,
  Spinner,
  Stack,
} from "@larasell-dev/ui";
import { AddLine, ArrowDownSLine, FileReduceLine, More2Fill } from "@larasell-dev/ui/icons";
import { Accessibility, type defaultPreset } from "@dnd-kit/dom";
import { type DragEndEvent, DragDropProvider } from "@dnd-kit/react";
import { isSortable, useSortable } from "@dnd-kit/react/sortable";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import {
  type ChangeEventHandler,
  type Ref,
  type RefObject,
  useEffect,
  useEffectEvent,
  useRef,
  useState,
} from "react";
import { plugins, sensors } from "../utils/dragAndDrop";
import { slugify } from "../utils/slugify";
import { overlayExclusive } from "../utils/hotkeys";
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
        <ImageGrid images={product?.images ?? []} uploadUrl={uploadImagesUrl} saveErrors={errors} />
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

// Without the plugin that makes each image a focusable button for keyboard dragging, which only
// the pointer can do here, so the images stay the list's items.
const imagePlugins = (defaults: typeof defaultPreset.plugins) =>
  plugins(defaults).filter((plugin) => plugin !== Accessibility);

/** The file types the server accepts. */
const IMAGE_TYPES = "image/jpeg,image/png,image/webp,image/avif";

/**
 * The product's images in its order, then a square that opens the system's file picker. The
 * chosen images are uploaded right away, one at a time so a rejected one keeps none of the others
 * out, with a plain request since the unsaved changes toast blocks Inertia visits, and join the
 * grid. Dragging an image moves it to another's place. The form submits the grid's image ids in
 * order, so saving the product links them; until then, cancelling the changes drops them again.
 * Saving waits for the uploads; cancelling or leaving the page stops them.
 */
function ImageGrid({
  images: savedImages,
  uploadUrl,
  saveErrors,
}: {
  images: Image[];
  uploadUrl: string;
  /** The form's errors from saving, of which those for its images show here too. */
  saveErrors: Errors;
}) {
  const [images, setImages] = useState(savedImages);
  // The files still to upload, each shown as a loading card where its image will appear.
  const [uploading, setUploading] = useState<File[]>([]);
  // Why chosen images weren't uploaded, one message per image.
  const [errors, setErrors] = useState<string[]>([]);
  const grid = useRef<HTMLUListElement>(null);
  const upload = useHttp<{ image: File | null }, { image: Image }>({ image: null });
  // The running uploads; replacing it stops them from adding anything more.
  const run = useRef<object | null>(null);
  const cancelUpload = upload.cancel;

  // Inertia's <Form> only rechecks whether it's dirty on input, change and reset events, which
  // inputs React adds or removes don't fire. Without this, uploading wouldn't show the unsaved
  // changes toast.
  useEffect(() => {
    grid.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [images]);

  // Once the product is saved, the rejected images no longer matter.
  useEffect(() => router.on("success", () => setErrors([])), []);

  // Stops the uploads when the form goes away, e.g. when leaving the page.
  useEffect(
    () => () => {
      run.current = null;
      cancelUpload();
    },
    [cancelUpload],
  );

  // Cancelling the changes resets the form, which puts its fields back to their saved values but
  // can't remove the hidden inputs of images uploaded since. Going back to the saved images does,
  // after stopping the uploads that would add more.
  useFormReset(grid, () => {
    run.current = null;
    cancelUpload();
    setUploading([]);
    setErrors([]);
    setImages(savedImages);
  });

  // The grid reorders while dragging; dropping keeps that order, cancelling puts it back.
  const drop = ({ canceled, operation: { source } }: DragEndEvent) => {
    if (canceled || !isSortable(source) || source.initialIndex === source.index) return;
    setImages((current) => {
      const next = [...current];
      next.splice(source.index, 0, ...next.splice(source.initialIndex, 1));
      return next;
    });
  };

  async function uploadFiles(input: HTMLInputElement) {
    const files = [...(input.files ?? [])];
    // Lets the same files be chosen again.
    input.value = "";
    if (files.length === 0) return;

    const current = {};
    run.current = current;
    setErrors([]);
    setUploading(files);

    for (const file of files) {
      let reason = "Try again.";
      let response: { image: Image } | undefined;
      upload.transform(() => ({ image: file }));
      try {
        // Undefined when the image is invalid; onError says why.
        response = await upload.post(uploadUrl, {
          onError: (fieldErrors) => (reason = Object.values(fieldErrors)[0] ?? reason),
          onHttpException: ({ status }) => {
            if (status === 413) reason = "It's larger than the server accepts.";
          },
        });
      } catch {
        // The reason stays "Try again.", unless the uploads were stopped.
      }
      if (run.current !== current) return;

      const uploaded = response?.image;
      if (uploaded) setImages((images) => [...images, uploaded]);
      else setErrors((errors) => [...errors, `Couldn't upload ${file.name}. ${reason}`]);
      setUploading((files) => files.slice(1));
    }
  }

  // Why saving refused the images, e.g. one deleted since it was uploaded. Once per reason, however
  // many images have it.
  const invalidImages = [
    ...new Set(
      Object.entries(saveErrors)
        .filter(([key]) => key === "images" || key.startsWith("images."))
        .map(([, error]) => error)
        .filter((error) => error !== undefined),
    ),
  ];

  return (
    <BaseField.Root
      invalid={invalidImages.length + errors.length > 0}
      className="flex flex-col gap-1.5"
    >
      {/* Fewer columns as the form narrows, so the images stay large enough to tell apart. */}
      <div className="@container">
        <ul
          ref={grid}
          aria-label="Images"
          aria-busy={uploading.length > 0}
          className="grid auto-rows-fr grid-cols-1 gap-3 @xs:grid-cols-2 @lg:grid-cols-3 @2xl:grid-cols-4"
        >
          <DragDropProvider sensors={sensors} plugins={imagePlugins} onDragEnd={drop}>
            {images.map((image, index) => (
              <ImageCard
                key={image.id}
                image={image}
                index={index}
                onRemove={() => setImages((current) => current.filter(({ id }) => id !== image.id))}
              />
            ))}
          </DragDropProvider>
          {uploading.map((file, index) => (
            <li
              // The same file can be chosen twice, so its name isn't unique.
              key={index}
              className="border-border flex aspect-square items-center justify-center border bg-mauve-50 text-2xl text-mauve-600 dark:bg-mauve-900 dark:text-mauve-300"
            >
              <Spinner aria-label={`Uploading ${file.name}`} />
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
                // Clickable while no upload is running, dimmed while one is
                "cursor-pointer has-disabled:cursor-default has-disabled:opacity-50",
                "not-has-disabled:hover:bg-mauve-50 dark:not-has-disabled:hover:bg-mauve-800",
                // Focus is on the hidden file input inside
                "has-focus-visible:focus-ring",
              )}
            >
              <input
                type="file"
                multiple
                accept={IMAGE_TYPES}
                aria-label="Add images"
                disabled={uploading.length > 0}
                className="sr-only"
                onChange={(event) => uploadFiles(event.currentTarget)}
              />
              <Icon icon={AddLine} />
            </label>
          </li>
        </ul>
      </div>
      {[...invalidImages, ...errors].map((error, index) => (
        // The same file can be chosen twice, so its message isn't unique.
        <Error key={index} match>
          {error}
        </Error>
      ))}
    </BaseField.Root>
  );
}

/** An image in the grid, which drags from anywhere on it, its menu too once the pointer moves. */
function ImageCard({
  image,
  index,
  onRemove,
}: {
  image: Image;
  index: number;
  onRemove: () => void;
}) {
  // No transition, so the other images jump to their new places instead of sliding there.
  const { ref, isDragging } = useSortable({ id: image.id, index, transition: null });

  return (
    <li
      ref={ref}
      data-dragging={isDragging || undefined}
      className={cn(
        "border-border relative aspect-square cursor-grab overflow-hidden border bg-mauve-50 select-none dark:bg-mauve-900",
        "data-dragging:cursor-grabbing data-dragging:opacity-60",
        // Otherwise iOS opens its share and save menu on the long press that starts a touch
        // drag; it takes over the touch, and no image drags after that.
        "[-webkit-touch-callout:none]",
      )}
    >
      <input type="hidden" name="images[]" value={image.id} />
      {/* A native image drag would take over the pointer before ours starts. */}
      <img
        src={image.url}
        alt={`Image ${index + 1}`}
        draggable={false}
        className="size-full object-cover"
      />
      {index === 0 && (
        // Flex, so the badge doesn't sit on a text line that adds space above it.
        <div className="absolute top-2 left-2 flex">
          <Badge>Thumbnail</Badge>
        </div>
      )}
      <div className="absolute top-2 right-2">
        <ImageMenu label={`Actions for image ${index + 1}`} onRemove={onRemove} />
      </div>
    </li>
  );
}

/**
 * An image's actions, always visible so they're found without hovering. Removing only takes the
 * image out of the grid: saving unlinks it from the product, cancelling brings it back.
 */
function ImageMenu({ label, onRemove }: { label: string; onRemove: () => void }) {
  return (
    <Menu.Root>
      <Menu.Trigger
        render={
          // A solid chip like the thumbnail badge, so it stands out on any image.
          <IconButton variant="secondary" size="sm" aria-label={label}>
            <Icon icon={More2Fill} />
          </IconButton>
        }
      />
      <Menu.Popup>
        <HotkeysProvider scope="image-actions-menu" exclusive={overlayExclusive}>
          <Menu.Item variant="destructive" icon={FileReduceLine} onClick={onRemove}>
            Remove
          </Menu.Item>
        </HotkeysProvider>
      </Menu.Popup>
    </Menu.Root>
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
