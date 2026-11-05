import { router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import type { SharedProps } from "../../types";
import { navigationIcon } from "../../utils/navigationIcon";
import { type CommandItem, useCommand } from "./commands";
import { Highlight, matchRanges } from "./Highlight";

type Search = Larasell.Admin.Platform.Search.Data.SearchData;
type Result = Larasell.Admin.Platform.Search.Data.SearchResultData;

/**
 * Adds a command for each search the server shares: those of the modules that are turned on and
 * those plugins add. Each searches its url as the admin types and opens the result they pick.
 */
export function SearchCommands() {
  const searches = usePage<SharedProps>().props.commandMenu?.searches ?? [];

  return searches.map((search) => <SearchCommand key={search.id} search={search} />);
}

function SearchCommand({ search: { id, label, icon, placeholder, url } }: { search: Search }) {
  useCommand({
    id: `search.${id}`,
    use({ search }) {
      const { results, loading } = useSearch(url, search);

      return {
        label,
        icon: navigationIcon(icon, false),
        placeholder,
        // Searches the server only in its own view, and shows the server's results as they are.
        searchItemsFromRoot: false,
        filterItems: false,
        loading,
        items: results.map(resultItem),
      };
    },
  });

  return null;
}

/** The server's results for the search, once the admin pauses typing for a moment. */
function useSearch(url: string, search: string | null) {
  const query = search?.trim() ?? "";
  const [found, setFound] = useState<{ query: string; results: Result[] }>({
    query: "",
    results: [],
  });

  useEffect(() => {
    if (query === "") return;

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
