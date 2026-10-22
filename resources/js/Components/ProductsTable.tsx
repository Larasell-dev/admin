import { Combobox } from "@base-ui/react/combobox";
import { router, useHttp } from "@inertiajs/react";
import { Button, EmptyState, Icon, Input, Menu, Pagination } from "@larasell-dev/ui";
import { AddLine, More2Fill } from "@larasell-dev/ui/icons";
import { useRef, useState } from "react";
import type { TableAction, TableResource } from "../types";
import { navigationIcon } from "../utils/navigationIcon";
import ConfirmActionDialog from "./ConfirmActionDialog";
import { Section } from "./Section";

/**
 * The products section of a category: its header and a Section.Card with the products and a
 * menu of row actions. With the urls to find and add products, the empty state, or the header
 * once there are products, offers to add one, which shows a row to pick products until cancelled. Without a table, e.g. while the
 * category is being created, it only explains that none are listed yet.
 */
export default function ProductsTable({
  table,
  availableProductsUrl,
  addProductUrl,
}: {
  table: TableResource | null;
  availableProductsUrl?: string;
  addProductUrl?: string;
}) {
  const [adding, setAdding] = useState(false);
  const [pending, setPending] = useState<TableAction | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  const perform = (action: TableAction, confirmation?: string) => {
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

  const choose = (action: TableAction) => {
    setError(undefined);

    if (action.confirm) {
      setPending(action);
    } else {
      perform(action);
    }
  };

  const rows = table?.rows ?? [];
  const canAdd =
    table !== null && availableProductsUrl !== undefined && addProductUrl !== undefined;
  const showsAddRow = canAdd && adding;
  const hasActions = rows.some((row) => row._actions.length > 0);
  const startAdding = () => setAdding(true);
  const empty = table
    ? (table.emptyState ?? { title: "No products yet", description: null })
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
          <div className="flex justify-center px-(--inset) py-10">
            <EmptyState
              title={empty.title}
              description={empty.description ?? undefined}
              // Only given children when it has an action; even `false` renders an empty actions row.
              {...(canAdd && {
                children: (
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
              availableProductsUrl={availableProductsUrl!}
              addProductUrl={addProductUrl!}
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
                  <tr key={row.id} className="border-b border-border last:border-b-0">
                    {table!.columns.map((column) => (
                      <td
                        key={column.name}
                        className="px-(--inset) py-1.5 type-body-md text-mauve-950 dark:text-mauve-50"
                      >
                        {row[column.name] as string | null}
                      </td>
                    ))}
                    {hasActions && (
                      <td className="px-1.5 py-1.5 text-right">
                        <Menu.Root>
                          <Menu.Trigger
                            aria-label={`Actions for ${row[table!.columns[0]?.name ?? "id"]}`}
                            className="inline-flex size-9 cursor-pointer items-center justify-center text-mauve-950 select-none hover:bg-mauve-100 focus-visible:focus-ring data-popup-open:bg-mauve-100 dark:text-mauve-100 dark:hover:bg-mauve-800 dark:data-popup-open:bg-mauve-800"
                          >
                            <Icon icon={More2Fill} />
                          </Menu.Trigger>
                          <Menu.Popup>
                            {row._actions.map((action) => (
                              <Menu.Item
                                key={action.name}
                                variant={action.destructive ? "destructive" : "default"}
                                disabled={action.disabled}
                                icon={action.icon ? navigationIcon(action.icon, false) : undefined}
                                onClick={() => choose(action)}
                              >
                                {action.label}
                              </Menu.Item>
                            ))}
                          </Menu.Popup>
                        </Menu.Root>
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
  const search = useHttp<{ search: string }, { products: AvailableProduct[] }>({ search: "" });
  const [products, setProducts] = useState<AvailableProduct[]>([]);
  const [selected, setSelected] = useState<AvailableProduct | null>(null);
  const [posting, setPosting] = useState(false);
  const [error, setError] = useState<string>();
  const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

  // Debounced, and a newer search cancels the one still running.
  function find(query: string) {
    clearTimeout(timer.current);
    timer.current = setTimeout(async () => {
      search.cancel();
      search.transform(() => ({ search: query }));
      try {
        setProducts((await search.get(availableProductsUrl)).products);
      } catch {
        // A cancelled search is replaced by the newer one.
      }
    }, 200);
  }

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
    <div className="flex items-start gap-1.5 border-b border-border p-3 last:border-b-0">
      <div className="min-w-0 flex-1">
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
                <Combobox.Empty className="px-3.5 py-2 type-body-md text-mauve-600 empty:hidden dark:text-mauve-400">
                  {search.processing ? "Searching…" : "No products found."}
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
              </Combobox.Popup>
            </Combobox.Positioner>
          </Combobox.Portal>
        </Combobox.Root>
        {error && (
          <p role="alert" className="mt-1.5 type-body-sm text-red-600 dark:text-red-400">
            {error}
          </p>
        )}
      </div>
      <div className="flex shrink-0 gap-1">
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
  );
}
