import type { ReactNode } from "react";

/**
 * The text with the ranges marked, e.g. where a search matched it. Each range is a start and an
 * end index, the end excluded, like String.slice(); ranges can come in any order.
 */
export function Highlight({
  text,
  ranges,
}: {
  text: string;
  ranges: ReadonlyArray<readonly [start: number, end: number]>;
}) {
  const parts: ReactNode[] = [];
  let at = 0;

  for (const [start, end] of [...ranges].sort(([a], [b]) => a - b)) {
    const from = Math.max(start, at);
    const to = Math.min(end, text.length);

    if (to <= from) continue;
    if (from > at) parts.push(text.slice(at, from));
    parts.push(
      <mark
        key={from}
        // Color only, no weight or padding, so marking a match never moves the text around it.
        className="rounded-xs bg-primary-500/15 text-primary-700 dark:bg-primary-400/20 dark:text-primary-300"
      >
        {text.slice(from, to)}
      </mark>,
    );
    at = to;
  }

  if (at < text.length) parts.push(text.slice(at));

  return <>{parts}</>;
}

/**
 * Where the search matched the text, for Highlight. Matches like the menu's filter: the search's
 * letters in order, ignoring case, with anything in between, so "oxfordshirt" marks both words
 * of "Oxford Shirt". Empty when the text doesn't match.
 */
export function matchRanges(text: string, search: string): Array<[start: number, end: number]> {
  const haystack = text.toLowerCase();
  const ranges: Array<[number, number]> = [];
  let from = 0;

  for (const letter of search.toLowerCase()) {
    if (letter.trim() === "") continue;

    const at = haystack.indexOf(letter, from);
    if (at < 0) return [];

    const last = ranges[ranges.length - 1];
    if (last && last[1] === at) last[1] = at + 1;
    else ranges.push([at, at + 1]);
    from = at + 1;
  }

  return ranges;
}
