import { Link, router } from "@inertiajs/react";
import { Badge, Button, Icon, Section, SectionStack, Select, Table } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import { ghostAction } from "./ProductForm";
import { fbs } from "fbtee";
import { useState } from "react";

type Data = Larasell.Admin.Product.Data.ProductVariantsData;

/**
 * The product's options and variants, outside the product form: each change saves right away.
 * A product without options shows only how to add the first.
 */
export default function VariantsSection({ variants }: { variants: Data }) {
  return (
    <SectionStack>
      <Section.Root>
        <Section.Header
          title={fbs(
            "Options",
            "Product page: heading of the section with the product's options, e.g. size or colour",
          )}
          description={fbs(
            "Options tell variants apart. Pick from the options defined in the catalog.",
            "Product page: text below the heading of the options section",
          )}
        />
        <div className="flex flex-col gap-4">
          {variants.options.map((option) => (
            <OptionRow key={option.definitionId} option={option} />
          ))}
          <AddOption
            choices={variants.availableOptions}
            url={variants.addOptionUrl}
            definitionsUrl={variants.optionDefinitionsUrl}
          />
        </div>
      </Section.Root>

      {variants.options.length > 0 && (
        <Section.Root>
          <Section.Header
            title={fbs("Variants", "Product page: heading of the table of the product's variants")}
            description={fbs(
              "Each variant is one combination of option values with its own SKU and status.",
              "Product page: text below the heading of the variants section",
            )}
          />
          <Section.Card>
            <VariantsTable variants={variants} />
          </Section.Card>
          {variants.missingCombinations.length > 0 && (
            <MissingCombinations
              combinations={variants.missingCombinations}
              url={variants.createCombinationsUrl}
            />
          )}
        </Section.Root>
      )}
    </SectionStack>
  );
}

function Swatch({ color }: { color: string | null }) {
  return color ? (
    <span
      className="inline-block size-3 rounded-full border border-base-secondary"
      style={{ background: color }}
    />
  ) : null;
}

function OptionRow({ option }: { option: Larasell.Admin.Product.Data.ProductOptionData }) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      <span className="type-label-md w-28 text-base-primary">{option.name}</span>
      {option.values.map((value) => (
        <span
          key={value.id}
          className="flex items-center gap-1.5 rounded-full bg-action-neutral-secondary-hover h-6.5 px-2.5 type-body-sm"
        >
          <Swatch color={value.swatch} />
          {value.name}
        </span>
      ))}
      {option.otherValues.length > 0 && (
        <div className="w-40">
          <Select
            aria-label={fbs(
              "Add value",
              "Product page: accessible name of the select that adds a value to an option",
            )}
            placeholder={fbs(
              "+ Add value",
              "Product page: shown on the select that adds a value to an option",
            )}
            items={option.otherValues}
            itemToKey={(value) => String(value.id)}
            itemToLabel={(value) => value.name}
            value={null}
            onValueChange={(key) =>
              key &&
              router.post(option.addValueUrl, { value: Number(key) }, { preserveScroll: true })
            }
          />
        </div>
      )}
    </div>
  );
}

function AddOption({
  choices,
  url,
  definitionsUrl,
}: {
  choices: Larasell.Admin.Product.Data.OptionChoiceData[];
  url: string;
  definitionsUrl: string;
}) {
  const [definition, setDefinition] = useState<number | null>(null);
  const [values, setValues] = useState<number[]>([]);
  const chosen = choices.find((choice) => choice.id === definition);

  if (choices.length === 0) {
    return (
      <Link href={definitionsUrl} className={ghostAction}>
        <Icon icon={AddLine} />
        <fbt desc="Product page: link to the catalog's options, to create one when every option is already used">
          Create option
        </fbt>
      </Link>
    );
  }

  return (
    <div
      className={
        chosen
          ? "flex flex-col gap-3 rounded-lg border border-dashed border-base-secondary p-3"
          : "flex flex-col"
      }
    >
      <Select.Root
        items={choices}
        itemToKey={(choice) => String(choice.id)}
        itemToLabel={(choice) => choice.name}
        value={definition === null ? null : String(definition)}
        onValueChange={(key) => {
          setDefinition(key ? Number(key) : null);
          setValues([]);
        }}
      >
        <Select.Trigger
          render={<button type="button" className={ghostAction} />}
          aria-label={fbs(
            "Option to add",
            "Product page: accessible name of the button that opens the options to add",
          )}
        >
          <Icon icon={AddLine} />
          {chosen?.name ??
            fbs("Add option", "Product page: button that opens the list of options to add")}
        </Select.Trigger>
        <Select.Popup />
      </Select.Root>
      {chosen && (
        <>
          <div className="flex flex-wrap gap-3">
            {chosen.values.map((value) => (
              <label key={value.id} className="flex items-center gap-1.5 type-body-sm">
                <input
                  type="checkbox"
                  checked={values.includes(value.id)}
                  onChange={(event) =>
                    setValues(
                      event.target.checked
                        ? [...values, value.id]
                        : values.filter((id) => id !== value.id),
                    )
                  }
                />
                <Swatch color={value.swatch} />
                {value.name}
              </label>
            ))}
          </div>
          <div>
            <Button
              size="sm"
              disabled={values.length === 0}
              onClick={() =>
                router.post(
                  url,
                  { definition, values },
                  { preserveScroll: true, onSuccess: () => setDefinition(null) },
                )
              }
            >
              <fbt desc="Product page: button that adds the chosen option with its checked values">
                Add option
              </fbt>
            </Button>
          </div>
        </>
      )}
    </div>
  );
}

function VariantsTable({ variants }: { variants: Data }) {
  const rows = variants.variants;
  // Like the index tables: the pagination waits, disabled, until the next page has loaded.
  const [isPaginating, setIsPaginating] = useState(false);
  // The page lives in the URL, so it survives a reload and can be shared.
  const paginate = ({ page, pageSize }: { page: number; pageSize: number }) =>
    router.get(
      window.location.pathname,
      {
        ...Object.fromEntries(new URLSearchParams(window.location.search)),
        variants_page: page,
        variants_per_page: pageSize,
      },
      {
        only: ["variants"],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => setIsPaginating(true),
        onFinish: () => setIsPaginating(false),
      },
    );

  return (
    <Table.Root
      aria-label={fbs("Variants", "Product page: accessible name of the table of variants")}
    >
      <Table.Content>
        <Table.Header>
          <Table.Column>
            {fbs("Variant", "Product page: column heading of the variant's name")}
          </Table.Column>
          <Table.Column>
            {fbs("SKU", "Product page: column heading of the variant's SKU")}
          </Table.Column>
          <Table.Column>
            {fbs("Status", "Product page: column heading of the variant's visibility")}
          </Table.Column>
        </Table.Header>
        <Table.Body>
          {rows.map((variant) => (
            <Table.Row key={variant.id}>
              <Table.Cell>
                <Table.Link render={<Link href={variant.editUrl} />}>{variant.name}</Table.Link>
              </Table.Cell>
              <Table.Cell>{variant.sku ?? "—"}</Table.Cell>
              <Table.Cell>
                {variant.visible ? (
                  <Badge variant="success">
                    {fbs("Visible", "Product page: status badge of a visible variant")}
                  </Badge>
                ) : (
                  <Badge>{fbs("Hidden", "Product page: status badge of a hidden variant")}</Badge>
                )}
              </Table.Cell>
            </Table.Row>
          ))}
        </Table.Body>
      </Table.Content>
      <Table.Pagination
        page={variants.page}
        pageSize={variants.pageSize}
        total={variants.total}
        pageSizeOptions={[10, 25, 50, 100]}
        onPaginationChange={paginate}
        disabled={isPaginating}
      />
    </Table.Root>
  );
}

function MissingCombinations({
  combinations,
  url,
}: {
  combinations: Larasell.Admin.Product.Data.CombinationData[];
  url: string;
}) {
  const [selected, setSelected] = useState<string[]>(combinations.map((c) => c.valueIds.join(",")));

  return (
    <div className="mt-4 flex flex-col gap-3 rounded-lg border border-dashed border-base-secondary p-3">
      <span className="type-label-md">
        <fbt desc="Product page: heading of the list of option combinations without a variant">
          Combinations without a variant
        </fbt>
      </span>
      <div className="grid grid-cols-2 gap-2 @md:grid-cols-3">
        {combinations.map((combination) => {
          const key = combination.valueIds.join(",");
          return (
            <label key={key} className="flex items-center gap-1.5 type-body-sm">
              <input
                type="checkbox"
                checked={selected.includes(key)}
                onChange={(event) =>
                  setSelected(
                    event.target.checked ? [...selected, key] : selected.filter((k) => k !== key),
                  )
                }
              />
              {combination.label}
            </label>
          );
        })}
      </div>
      <div>
        <Button
          size="sm"
          disabled={selected.length === 0}
          onClick={() =>
            router.post(
              url,
              { combinations: selected.map((key) => key.split(",").map(Number)) },
              { preserveScroll: true },
            )
          }
        >
          <fbt desc="Product page: button that creates a variant for each checked combination">
            Create <fbt:param name="count">{selected.length}</fbt:param> variants
          </fbt>
        </Button>
      </div>
    </div>
  );
}
