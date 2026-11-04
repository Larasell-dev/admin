import { router, usePage } from "@inertiajs/react";
import { FoldersLine, GroupLine, ReceiptLine, ShoppingBagLine } from "@larasell-dev/ui/icons";
import { useEffect, useState } from "react";
import type { SharedProps } from "../../types";
import {
  type CommandDefinition,
  type CommandFields,
  type CommandItem,
  defineCommand,
} from "./commands";
import { Highlight, matchRanges } from "./Highlight";

type Resource = keyof NonNullable<SharedProps["searchUrls"]>;
type Result = Larasell.Admin.Data.ViewModel.Search.SearchResultData;

/**
 * A command whose nested view searches the resource on the server as the admin types, and opens
 * the result they pick. It's left out when the resource's module is turned off.
 */
function searchCommand(
  resource: Resource,
  fields: Pick<CommandFields, "label" | "icon" | "placeholder"> & { label: string },
): CommandDefinition {
  return defineCommand({
    id: `search.${resource}`,
    use({ search }) {
      const url = usePage<SharedProps>().props.searchUrls?.[resource];
      const { results, loading } = useSearch(url, search);

      if (!url) return null;

      return {
        ...fields,
        // Searches the server only in its own view, and shows the server's results as they are.
        searchItemsFromRoot: false,
        filterItems: false,
        loading,
        items: results.map(resultItem),
      };
    },
  });
}

/** The server's results for the search, once the admin pauses typing for a moment. */
function useSearch(url: string | undefined, search: string | null) {
  const query = search?.trim() ?? "";
  const [found, setFound] = useState<{ query: string; results: Result[] }>({
    query: "",
    results: [],
  });

  useEffect(() => {
    if (!url || query === "") return;

    const controller = new AbortController();
    const timer = setTimeout(async () => {
      try {
        const response = await fetch(`${url}?${new URLSearchParams({ q: query })}`, {
          signal: controller.signal,
          headers: { Accept: "application/json" },
        });
        const { results } = response.ok
          ? ((await response.json()) as { results: Result[] })
          : { results: [] };
        setFound({ query, results });
      } catch (error) {
        // A newer search took its place.
        if (!controller.signal.aborted) setFound({ query, results: [] });
        else if (!(error instanceof DOMException)) throw error;
      }
    }, 200);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [url, query]);

  if (query === "") return { results: [], loading: false };

  // Keeps the last results until the new ones arrive, so the list doesn't flicker while typing.
  return { results: found.results, loading: found.query !== query };
}

/** The result's label, and below it what else matched, with the match marked. */
function resultItem(result: Result): CommandItem {
  const { match } = result;

  return {
    id: String(result.id),
    textValue: match ? `${result.label}, ${match.label}: ${match.value}` : result.label,
    label: ({ search }) => (
      <span className="flex flex-col">
        <span>
          {/* The server matched the label only when it says nothing else matched. */}
          <Highlight text={result.label} ranges={match ? [] : matchRanges(result.label, search)} />
        </span>
        {match && (
          <span className="type-body-sm text-mauve-500">
            {match.label}:{" "}
            <Highlight text={match.value} ranges={matchRanges(match.value, search)} />
          </span>
        )}
      </span>
    ),
    run: () => router.visit(result.url),
  };
}

/** Searches products, categories, orders and customers, in the navigation's order. */
export const searchCommands: CommandDefinition[] = [
  searchCommand("products", {
    label: "Search products",
    icon: ShoppingBagLine,
    placeholder: "Search by name, SKU or barcode",
  }),
  searchCommand("categories", {
    label: "Search categories",
    icon: FoldersLine,
    placeholder: "Search by name",
  }),
  searchCommand("orders", {
    label: "Search orders",
    icon: ReceiptLine,
    placeholder: "Search by number, customer or email",
  }),
  searchCommand("customers", {
    label: "Search customers",
    icon: GroupLine,
    placeholder: "Search by name",
  }),
];
