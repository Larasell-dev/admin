import { Link, router, useFormContext } from "@inertiajs/react";
import { cn } from "cn";
import {
  AlertDialog,
  Badge,
  Field,
  Input,
  Button,
  Icon,
  Section,
  SectionStack,
  Select,
  Table,
} from "@larasell-dev/ui";
import { AddLine, ArrowRightSLine } from "@larasell-dev/ui/icons";
import { AttributeValuesPicker, ghostAction, useMarkDirty, usePinnedAnchor } from "./ProductForm";
import { fbs } from "fbtee";
import { useRef, useState } from "react";

type Data = Larasell.Admin.Product.Data.ProductVariantsData;

/**
 * The product's options and variants, outside the product form: each change saves right away.
 * A product without options shows only how to add the first.
 */
export default function VariantsSection({
  variants,
  productName,
}: {
  variants: Data;
  /** What the admin types to confirm deleting variants. */
  productName: string;
}) {
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
        <OptionFields variants={variants} productName={productName} />
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

type Definition = { id: number; name: string; values: { id: number; name: string }[] };

/**
 * The product's options like its attributes: a row of value chips per option, a round add
 * button for values, and "Add option" for another. Saved with the product form; removing values
 * that variants use asks to confirm deleting them first.
 */
function OptionFields({ variants, productName }: { variants: Data; productName: string }) {
  const errors: Record<string, string | undefined> = useFormContext()?.errors ?? {};
  const definitions: Definition[] = [
    ...variants.options.map((option) => ({
      id: option.definitionId,
      name: option.name,
      values: [...option.values, ...option.otherValues],
    })),
    ...variants.availableOptions,
  ];
  const [shown, setShown] = useState(() => variants.options.map((option) => option.definitionId));
  const picked = (id: number) =>
    variants.options.find((option) => option.definitionId === id)?.values.map((v) => v.id) ?? [];
  const deletions = Number(errors.delete_variants?.match(/\d+/)?.[0] ?? 0);
  const form = useFormContext();
  const root = useRef<HTMLDivElement>(null);
  const confirmed = useRef<HTMLInputElement>(null);
  useMarkDirty(root, shown);
  // The server refuses a save that deletes variants until it's sent their count; ask first.
  // Each refused save brings a new errors object, which opens the dialog again.
  const [dismissed, setDismissed] = useState<object | null>(null);
  const confirming = deletions > 0 && dismissed !== errors;
  const [typed, setTyped] = useState("");
  const setConfirming = (open: boolean) => {
    if (!open) setDismissed(errors);
    setTyped("");
  };

  const pinned = usePinnedAnchor();
  return (
    <div ref={root} className="flex flex-col gap-3">
      <input ref={confirmed} type="hidden" name="delete_variants" defaultValue="" />
      {/* Says the options were sent, so removing every option still saves. */}
      <input type="hidden" name="options_sent" value="1" />
      {definitions
        .filter((definition) => shown.includes(definition.id))
        .map((definition) => (
          <div key={definition.id} className="flex flex-wrap items-center gap-2">
            <span className="w-28 type-label-md text-base-primary">{definition.name}</span>
            <AttributeValuesPicker
              name={`options[${definition.id}]`}
              label={definition.name}
              values={definition.values}
              defaultValue={picked(definition.id)}
            />
          </div>
        ))}
      <AlertDialog.Root open={confirming} onOpenChange={setConfirming}>
        <AlertDialog.Popup
          title={fbs(
            "Delete variants",
            "Product page: title of the dialog that confirms saving deletes variants",
          )}
          description={fbs(
            [
              "Saving deletes ",
              fbs.plural("variant", deletions, { many: "variants", showCount: "yes" }),
              " made of the removed option values. Deleted variants lose their SKUs and stock and are taken out of carts. This can't be undone.",
            ],
            "Product page: text of the dialog that confirms saving deletes the variants of removed option values",
          )}
        >
          <Field
            name="confirm_delete_variants"
            label={fbs(
              ['Type "', fbs.param("productName", productName), '" to confirm'],
              "Product page: label of the field in the variant deletion dialog the admin types the product's name into to confirm",
            )}
          >
            <Input
              value={typed}
              onChange={(event) => setTyped(event.target.value)}
              autoComplete="off"
              spellCheck={false}
            />
          </Field>
          <AlertDialog.Actions>
            <AlertDialog.Cancel>
              <fbt desc="Product page: button that closes the variant deletion dialog without saving">
                Cancel
              </fbt>
            </AlertDialog.Cancel>
            <AlertDialog.Confirm
              variant="destructive"
              disabled={typed.trim() !== productName.trim()}
              onClick={(_event, dialog) => {
                if (confirmed.current) confirmed.current.value = String(deletions);
                dialog.close();
                form?.submit();
              }}
            >
              <fbt desc="Product page: button that saves and deletes the variants">
                Delete and save
              </fbt>
            </AlertDialog.Confirm>
          </AlertDialog.Actions>
        </AlertDialog.Popup>
      </AlertDialog.Root>
      <div className="flex items-center gap-1">
        <Select.Root
          multiple
          items={definitions}
          itemToKey={(definition) => String(definition.id)}
          itemToLabel={(definition) => definition.name}
          value={shown.map(String)}
          onValueChange={(keys) => setShown(keys.map(Number))}
          filter={{
            placeholder: fbs(
              "Search options",
              "Product page: placeholder of the search in the list of options to add",
            ),
            empty: fbs(
              "No options found",
              "Product page: shown in the list of options when the search matches none",
            ),
          }}
        >
          <span {...pinned.trigger}>
            <Select.Trigger render={<button type="button" className={ghostAction} />}>
              <Icon icon={AddLine} />
              <fbt desc="Product page: button that opens the list of options to give the product">
                Add option
              </fbt>
            </Select.Trigger>
          </span>
          <Select.Popup anchor={pinned.anchor} />
        </Select.Root>
        <Link
          href={variants.optionDefinitionsUrl}
          className={cn(ghostAction, "w-6.5 justify-center px-0")}
          aria-label={fbs(
            "Options page",
            "Product page: accessible name of the button next to Add option that opens the catalog's options",
          )}
        >
          <Icon icon={ArrowRightSLine} />
        </Link>
      </div>
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
    <div className="flex flex-col gap-3 rounded-lg border border-dashed border-base-secondary p-3">
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
