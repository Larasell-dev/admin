import { Field as BaseField } from "@base-ui/react/field";
import { useFormContext } from "@inertiajs/react";
import {
  Checkbox,
  Error,
  Field,
  Icon,
  Input,
  Section,
  SectionStack,
  Stack,
} from "@larasell-dev/ui";
import { ArrowDownSLine } from "@larasell-dev/ui/icons";
import { type ChangeEventHandler, type Ref, useRef } from "react";
import { slugify } from "../utils/slugify";
import { truncate } from "../utils/truncate";
import { useCoupledField } from "../utils/useCoupledField";
import FormCheckbox from "./FormCheckbox";
import VisibilityField from "./VisibilityField";

type Product = Omit<Larasell.Admin.Data.ViewModel.ProductDetailData, "id">;
type Errors = Record<string, string | undefined>;

/**
 * The sections of a product's fields. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a product it starts empty and visible.
 */
export default function ProductForm({ product }: { product?: Product }) {
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
