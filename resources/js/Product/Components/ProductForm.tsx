import { cn } from "cn";
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
import { type ChangeEventHandler, Fragment, type ReactNode, type Ref, useRef } from "react";
import CategoryCheckboxTree from "../../Category/Components/CategoryCheckboxTree";
import ExtensionSections from "../../Platform/Sections/Components/ExtensionSections";
import FormCheckbox from "../../Platform/Panel/Components/FormCheckbox";
import VisibilityField from "../../Platform/Panel/Components/VisibilityField";
import RichTextEditor from "../../Platform/RichText/RichTextEditor";
import { fbs } from "fbtee";

type Product = Omit<Larasell.Admin.Product.Data.ProductDetailData, "id">;
type Category = Larasell.Admin.Category.Data.CategoryOptionData;
type Errors = Record<string, string | undefined>;
type ExtensionSection = Larasell.Admin.Platform.Sections.Data.SectionData;
/** The product form's own sections, generated from the ProductSection enum. */
type Anchor = Larasell.Admin.Product.Enums.ProductSection;

/**
 * The sections of a product's fields. It must sit inside an Inertia `<Form>`, which submits them
 * and provides their errors; without a product it starts empty, visible and in no category.
 */
export default function ProductForm({
  product,
  categories,
  createCategoryUrl,
  sections = [],
}: {
  product?: Product;
  /** The store's categories to pick the product's from. */
  categories: Category[];
  /** Where the store's first categories are created, offered while it has none. Null while the
   * categories are turned off. */
  createCategoryUrl: string | null;
  /** The sections that modules and plugins add, placed around the product's own. */
  sections?: ExtensionSection[];
}) {
  const errors: Errors = useFormContext()?.errors ?? {};
  // Every one of ProductSection's anchors, in the order they show: a new one fails to compile here
  // until the form renders it, so no section placed at it goes missing.
  const own = {
    general: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "General",
            "Product form: heading of the section with the product's name and visibility",
          )}
          description={fbs(
            "How the product is named and found in the store.",
            "Product form: text below the heading of the General section, with the name and visibility",
          )}
        />
        <GeneralFields product={product} errors={errors} />
      </Section.Root>
    ),
    categories: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Categories",
            "Product form: heading of the section that picks the categories the product is listed in",
          )}
          description={fbs(
            "Where the product is listed in the store.",
            "Product form: text below the heading of the Categories section",
          )}
        />
        <CategoryCheckboxTree
          categories={categories}
          defaultSelectedIds={product?.categoryIds ?? []}
          createUrl={createCategoryUrl}
        />
      </Section.Root>
    ),
    stock: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Stock",
            "Product form: heading of the section with the stock level, backorders and order quantity limits",
          )}
          description={fbs(
            "How many can be sold, and how many a customer can buy at once.",
            "Product form: text below the heading of the Stock section",
          )}
        />
        <StockFields product={product} errors={errors} />
      </Section.Root>
    ),
    "weight-and-dimensions": (
      <Section.Root>
        <Section.Header
          title={fbs(
            "Weight and dimensions",
            "Product form: heading of the section with the product's shipping weight, length, width and height",
          )}
          description={fbs(
            "Used to choose shipping methods and their prices. Leave them empty for products that aren't shipped.",
            "Product form: text below the heading of the Weight and dimensions section",
          )}
        />
        <WeightAndDimensionsFields product={product} errors={errors} />
      </Section.Root>
    ),
    identifiers: (
      <Section.Root>
        <Section.Header
          title={fbs(
            "SKU and barcode",
            "Product form: heading of the section with the stock keeping unit and the barcode (e.g. EAN or UPC)",
          )}
          description={fbs(
            "Identify the product in your inventory and when it's scanned. Each belongs to one product only.",
            "Product form: text below the heading of the SKU and barcode section",
          )}
        />
        <IdentifierFields product={product} errors={errors} />
      </Section.Root>
    ),
  } satisfies Record<Anchor, ReactNode>;

  return (
    <SectionStack>
      {Object.entries(own).map(([anchor, section]) => (
        <Fragment key={anchor}>
          <ExtensionSections sections={sections} before={anchor} />
          {section}
          <ExtensionSections sections={sections} after={anchor} />
        </Fragment>
      ))}
      <ExtensionSections sections={sections} anchors={Object.keys(own)} />
    </SectionStack>
  );
}

function GeneralFields({ product, errors }: { product?: Product; errors: Errors }) {
  // Untranslated fields stay empty and show what the store shows instead.
  const untranslated = product?.name === null;

  return (
    <Stack spacing={5}>
      <Field
        label={fbs("Name", "Product form: label of the field for the product's name")}
        name="name"
        error={errors.name}
      >
        <Input
          defaultValue={product?.name ?? undefined}
          placeholder={untranslated ? product.fallbackName : undefined}
          autoComplete="off"
        />
      </Field>

      <div className="flex flex-col gap-1.5">
        <span className="type-label-md text-base-primary" aria-hidden>
          <fbt desc="Product form: label of the formatted text field for the product's description">
            Description
          </fbt>
        </span>
        <RichTextEditor
          name="description"
          label={fbs(
            "Description",
            "Product form: accessible name of the formatted text field for the product's description",
          )}
          defaultValue={product?.description ?? null}
          placeholder={fbs(
            "Enter a description…",
            "Product form: placeholder in the empty description field",
          )}
          invalid={!!errors.description}
        />
        {errors.description && <Error match>{errors.description}</Error>}
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
        <Field
          label={fbs(
            "SKU",
            "Product form: label of the field for the stock keeping unit, the store's own product code",
          )}
          name="sku"
          error={errors.sku}
        >
          <Input defaultValue={product?.sku ?? ""} autoComplete="off" spellCheck={false} />
        </Field>

        <Field
          label={fbs(
            "Barcode",
            "Product form: label of the field for the product's barcode number, e.g. EAN or UPC",
          )}
          name="barcode"
          error={errors.barcode}
        >
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
            label={fbs("Stock", "Product form: label of the field for how many items are in stock")}
            name="stock"
            error={errors.stock}
            description={fbs(
              "Leave empty to sell without counting.",
              "Product form: hint below the stock field, saying an empty stock isn't tracked",
            )}
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
                defaultChecked={product?.allowBackorders ?? false}
              />
              <fbt desc="Product form: label of the checkbox that lets customers order the product while it's out of stock">
                Allow backorders
              </fbt>
            </Checkbox.Label>
            {errors.allow_backorders && <Error match>{errors.allow_backorders}</Error>}
          </BaseField.Root>
        </div>
      </div>

      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field
            label={fbs(
              "Minimum quantity",
              "Product form: label of the field for the fewest items of the product a customer must buy at once",
            )}
            name="min_quantity"
            error={errors.min_quantity}
          >
            <Input
              defaultValue={product?.minQuantity ?? ""}
              inputMode="numeric"
              autoComplete="off"
            />
          </Field>

          <Field
            label={fbs(
              "Maximum quantity",
              "Product form: label of the field for the most items of the product a customer can buy at once",
            )}
            name="max_quantity"
            error={errors.max_quantity}
          >
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

/** The dimensions, with their field's label and their unit select's, in the interface language. */
function axes() {
  return [
    {
      axis: "length",
      label: fbs("Length", "Product form: label of the field for the product's shipping length"),
      unitLabel: fbs(
        "Length unit",
        "Product form: accessible name of the select next to the length field that picks the unit, e.g. cm",
      ),
    },
    {
      axis: "width",
      label: fbs("Width", "Product form: label of the field for the product's shipping width"),
      unitLabel: fbs(
        "Width unit",
        "Product form: accessible name of the select next to the width field that picks the unit, e.g. cm",
      ),
    },
    {
      axis: "height",
      label: fbs("Height", "Product form: label of the field for the product's shipping height"),
      unitLabel: fbs(
        "Height unit",
        "Product form: accessible name of the select next to the height field that picks the unit, e.g. cm",
      ),
    },
  ] as const;
}

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
            label={fbs(
              "Weight",
              "Product form: label of the field for the product's shipping weight",
            )}
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
                  label={fbs(
                    "Weight unit",
                    "Product form: accessible name of the select next to the weight field that picks the unit, e.g. kg",
                  )}
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
          {axes().map(({ axis, label, unitLabel }, index) => (
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
                    label={unitLabel}
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
    <div className="border-base-subtle relative -mr-3.5 flex self-stretch border-l">
      <select
        {...props}
        ref={ref}
        aria-label={label}
        className={cn(
          "type-input-md cursor-pointer appearance-none bg-transparent pr-9 pl-3.5 text-base-primary select-none",
          "hover:bg-action-neutral-ghost-hover focus-visible:focus-ring focus-visible:outline-offset-0",
        )}
      >
        {units.map((unit) => (
          <option key={unit} value={unit}>
            {unit}
          </option>
        ))}
      </select>
      {/* Clicks pass through to the select underneath. */}
      <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-base-primary">
        <Icon icon={ArrowDownSLine} />
      </span>
    </div>
  );
}
