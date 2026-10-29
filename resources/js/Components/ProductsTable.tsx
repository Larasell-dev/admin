import { Combobox } from "@base-ui/react/combobox";
import { Field } from "@base-ui/react/field";
import { Link, router } from "@inertiajs/react";
import {
  Button,
  EmptyState,
  Error,
  Icon,
  IconButton,
  Input,
  Menu,
  Pagination,
  Section,
  Table,
} from "@larasell-dev/ui";
import { AddLine, More2Fill, ShoppingBagLine } from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { cn } from "cn";
import { useEffect, useMemo, useState } from "react";
import type { TableRowAction, TableResource } from "../types";
import { abortPrevious } from "../utils/abortPrevious";
import { debounce } from "../utils/debounce";
import { overlayExclusive } from "../utils/hotkeys";
import { isSameSite } from "../utils/isSameSite";
import { navigationIcon } from "../utils/navigationIcon";
import ConfirmActionDialog from "./ConfirmActionDialog";

type Props = (
  | /** The category doesn't exist yet, so it can't list or take products. */
    { mode: "create" }
  | {
      mode: "edit";
      table: TableResource;
      availableProductsUrl: string;
      addProductUrl: string;
    }
) & {
  /**
   * Where a product is created, offered instead of adding one while the store has no products.
   * Null when there are products to add, or while the products are turned off.
   */
  createProductUrl: string | null;
};

/**
 * The products section of a category: its header and a Section.Card with the products and a
 * menu of row actions. When editing, the empty state, or the header once there are products,
 * offers to add one, which shows a bar to pick products until closed. When creating, it
 * explains that products show up once the category exists. Either empty state links to
 * creating a product instead, given `createProductUrl`, so it only ever offers one action.
 */
export default function ProductsTable(props: Props) {
  const [adding, setAdding] = useState(false);
  const [pending, setPending] = useState<TableRowAction | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  const perform = (action: TableRowAction, confirmation?: string) => {
    if (action.method === "get") {
      setPending(null);
      router.visit(action.url);
      return;
    }

    router.post(action.url, confirmation ? { confirmation } : {}, {
      preserveScroll: true,
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => setPending(null),
      onError: (errors) => setError(errors.confirmation),
    });
  };

  const choose = (action: TableRowAction) => {
    setError(undefined);

    if (action.confirm) {
      setPending(action);
    } else {
      perform(action);
    }
  };

  const table = props.mode === "edit" ? props.table : null;
  const rows = table?.rows ?? [];
  const canAdd = props.mode === "edit";
  const createProductUrl = props.createProductUrl;
  const showsAddRow = props.mode === "edit" && adding;
  const hasActions = rows.some((row) => row._actions.length > 0);
  const startAdding = () => setAdding(true);
  const empty =
    props.mode === "edit"
      ? (props.table.emptyState ?? { title: "No products yet", description: null })
      : {
          title: "No products yet",
          description: "Products show up here once the category is created.",
        };

  return (
    <Section.Root>
      <Section.Header
        title="Products"
        description="The products listed in this category."
        action={
          canAdd &&
          rows.length > 0 &&
          !adding && (
            <Button type="button" variant="secondary" onClick={startAdding}>
              <Icon icon={AddLine} />
              Add product
            </Button>
          )
        }
      />
      {rows.length === 0 && !showsAddRow ? (
        <Section.Card>
          <div className="flex justify-center px-(--inset) py-20">
            <EmptyState
              icon={ShoppingBagLine}
              title={empty.title}
              description={empty.description ?? undefined}
              // Only given children when it has an action; even `false` renders an empty actions row.
              {...((canAdd || createProductUrl) && {
                children: createProductUrl ? (
                  <Button render={<Link href={createProductUrl} prefetch="hover" />}>
                    <Icon icon={AddLine} />
                    Create product
                  </Button>
                ) : (
                  <Button type="button" onClick={startAdding}>
                    <Icon icon={AddLine} />
                    Add product
                  </Button>
                ),
              })}
            />
          </div>
        </Section.Card>
      ) : (
        <Section.Card>
          {showsAddRow && (
            <AddProductBar
              availableProductsUrl={props.availableProductsUrl}
              addProductUrl={props.addProductUrl}
              onCancel={() => setAdding(false)}
            />
          )}
          {rows.length > 0 && (
            <table aria-label={table!.title} className="w-full border-collapse text-left">
              <thead>
                <tr className="border-b border-border">
                  {table!.columns.map((column) => (
                    <th
                      key={column.name}
                      scope="col"
                      className="px-(--inset) py-2.5 type-label-sm text-mauve-600 dark:text-mauve-400"
                    >
                      {column.label}
                    </th>
                  ))}
                  {hasActions && (
                    <th scope="col" className="w-0 px-1.5">
                      <span className="sr-only">Actions</span>
                    </th>
                  )}
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr
                    key={row.id}
                    // `relative` contains the row link, which stretches over the whole row.
                    className={cn(
                      "relative border-b border-border last:border-b-0",
                      row._link &&
                        "hover:bg-mauve-50 has-[a:focus-visible]:bg-mauve-50 has-[a:focus-visible]:outline-2 has-[a:focus-visible]:-outline-offset-2 has-[a:focus-visible]:outline-primary-500 dark:hover:bg-mauve-800/50 dark:has-[a:focus-visible]:bg-mauve-800/50",
                    )}
                  >
                    {table!.columns.map((column, index) => (
                      <td
                        key={column.name}
                        className="px-(--inset) py-1.5 type-body-md text-mauve-950 dark:text-mauve-50"
                      >
                        {index === 0 && row._link ? (
                          <Table.Link
                            render={
                              isSameSite(row._link) ? (
                                <Link href={row._link} prefetch="hover" />
                              ) : (
                                <a href={row._link} />
                              )
                            }
                          >
                            {row[column.name] as string | null}
                          </Table.Link>
                        ) : (
                          (row[column.name] as string | null)
                        )}
                      </td>
                    ))}
                    {hasActions && (
                      // Above the row link, so the menu opens instead of following it.
                      <td className="relative z-10 px-1.5 py-1.5 text-right">
                        {row._actions.length > 0 && (
                          <Menu.Root>
                            <Menu.Trigger
                              render={
                                <IconButton
                                  variant="tertiary"
                                  size="sm"
                                  aria-label={`Actions for ${String(row[table!.columns[0]?.name ?? "id"] as string | number | null)}`}
                                >
                                  <Icon icon={More2Fill} />
                                </IconButton>
                              }
                            />
                            <Menu.Popup>
                              <HotkeysProvider
                                scope="product-actions-menu"
                                exclusive={overlayExclusive}
                              >
                                {row._actions.map((action) => (
                                  <Menu.Item
                                    key={action.name}
                                    variant={action.destructive ? "destructive" : "default"}
                                    disabled={action.disabled}
                                    icon={
                                      action.icon ? navigationIcon(action.icon, false) : undefined
                                    }
                                    {...(action.method === "get" && !action.confirm
                                      ? { render: <Link href={action.url} prefetch="hover" /> }
                                      : { onClick: () => choose(action) })}
                                  >
                                    {action.label}
                                  </Menu.Item>
                                ))}
                              </HotkeysProvider>
                            </Menu.Popup>
                          </Menu.Root>
                        )}
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          {table && table.pagination.total > table.pagination.pageSize && (
            <div className="border-t border-border px-(--inset) py-3">
              <Pagination
                {...table.pagination}
                pageSizeOptions={[25, 50, 100]}
                onPaginationChange={({ page, pageSize }) =>
                  router.get(
                    window.location.pathname,
                    { page, per_page: pageSize },
                    { preserveScroll: true, preserveState: true },
                  )
                }
              />
            </div>
          )}
        </Section.Card>
      )}

      <ConfirmActionDialog
        action={pending}
        processing={processing}
        error={error}
        onConfirm={(typed) => pending && perform(pending, typed)}
        onCancel={() => setPending(null)}
      />
    </Section.Root>
  );
}

type AvailableProduct = { id: number; name: string };

/** Above the table's header: search for a product that isn't in the category yet and add it. */
function AddProductBar({
  availableProductsUrl,
  addProductUrl,
  onCancel,
}: {
  availableProductsUrl: string;
  addProductUrl: string;
  onCancel: () => void;
}) {
  const [products, setProducts] = useState<AvailableProduct[]>([]);
  const [searching, setSearching] = useState(false);
  const [selected, setSelected] = useState<AvailableProduct | null>(null);
  const [posting, setPosting] = useState(false);
  const [error, setError] = useState<string>();

  // Debounced, and a newer search aborts the one still running.
  const find = useMemo(() => {
    const search = abortPrevious(async (signal, query: string) => {
      const url = new URL(availableProductsUrl, window.location.origin);
      url.searchParams.set("search", query);
      setSearching(true);
      try {
        const response = await fetch(url, { signal, headers: { Accept: "application/json" } });
        setProducts(((await response.json()) as { products: AvailableProduct[] }).products);
        setSearching(false);
      } catch (error) {
        // An aborted search was replaced by a newer one, which is still searching.
        if (signal.aborted) return;
        setSearching(false);
        throw error;
      }
    });
    const debounced = debounce((query: string) => void search(query), 200);

    return Object.assign(debounced, {
      stop: () => {
        debounced.cancel();
        search.abort();
      },
    });
  }, [availableProductsUrl]);
  useEffect(() => find.stop, [find]);

  function add() {
    if (!selected) return;

    router.post(
      addProductUrl,
      { product_id: selected.id },
      {
        preserveScroll: true,
        onStart: () => setPosting(true),
        onFinish: () => setPosting(false),
        // Stays open to add the next product.
        onSuccess: () => {
          setSelected(null);
          setError(undefined);
          setProducts([]);
        },
        onError: (errors) => setError(errors.product_id),
      },
    );
  }

  return (
    <div className="@container border-b border-border p-3 last:border-b-0">
      <div className="flex flex-col gap-1.5 @md:flex-row @md:items-start">
        {/* Without a name: the field only shows the error, it submits nothing. */}
        <Field.Root invalid={error !== undefined} className="flex min-w-0 flex-1 flex-col gap-1.5">
          <Combobox.Root
            items={products}
            // The server filters by the search, so every result it returns is shown.
            filter={null}
            value={selected}
            onValueChange={(product) => {
              setSelected(product);
              setError(undefined);
            }}
            itemToStringLabel={(product: AvailableProduct) => product.name}
            isItemEqualToValue={(a: AvailableProduct, b: AvailableProduct) => a.id === b.id}
            onInputValueChange={(query) => find(query)}
            onOpenChange={(open) => open && products.length === 0 && find("")}
          >
            <Combobox.Input
              aria-label="Product to add"
              placeholder="Search products"
              render={<Input />}
            />
            <Combobox.Portal>
              <Combobox.Positioner sideOffset={4} align="start" className="z-50 w-(--anchor-width)">
                <Combobox.Popup className="max-h-72 overflow-y-auto border border-border bg-white py-1 shadow-lg dark:bg-mauve-900">
                  <HotkeysProvider scope="product-search" exclusive={overlayExclusive}>
                    <Combobox.Empty className="px-3.5 py-2 type-body-md text-mauve-600 empty:hidden dark:text-mauve-400">
                      {searching ? "Searching…" : "No products found."}
                    </Combobox.Empty>
                    <Combobox.List>
                      {(product: AvailableProduct) => (
                        <Combobox.Item
                          key={product.id}
                          value={product}
                          className="cursor-pointer px-3.5 py-2 type-body-md text-mauve-950 outline-none select-none data-highlighted:bg-mauve-100 dark:text-mauve-100 dark:data-highlighted:bg-mauve-800"
                        >
                          {product.name}
                        </Combobox.Item>
                      )}
                    </Combobox.List>
                  </HotkeysProvider>
                </Combobox.Popup>
              </Combobox.Positioner>
            </Combobox.Portal>
          </Combobox.Root>
          {error && <Error match>{error}</Error>}
        </Field.Root>
        <div className="flex shrink-0 justify-end gap-1">
          <Button type="button" variant="tertiary" disabled={posting} onClick={onCancel}>
            Close
          </Button>
          <Button
            type="button"
            {...(posting ? { loading: true } : { disabled: !selected })}
            onClick={add}
          >
            Add product
          </Button>
        </div>
      </div>
    </div>
  );
}
