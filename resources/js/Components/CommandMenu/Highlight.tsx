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
        className="rounded-xs bg-intent-accent-subtle text-intent-accent"
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
 * Where the search matched the text, for Highlight. Ignores case and spaces, so "oxfordshirt"
 * marks both words of "Oxford Shirt". Prefers the search's letters next to each other, like the
 * server's searches match; otherwise marks them in order with anything in between, like the
 * menu's filter. Empty when the text doesn't match.
 */
export function matchRanges(text: string, search: string): Array<[start: number, end: number]> {
  const letters = search.toLowerCase().replace(/\s/g, "");
  if (letters === "") return [];

  // The text's letters without spaces, with where each one is in the text.
  const lower = text.toLowerCase();
  const positions: number[] = [];
  let squeezed = "";
  for (let index = 0; index < lower.length; index++) {
    const letter = lower.charAt(index);
    if (/\s/.test(letter)) continue;
    positions.push(index);
    squeezed += letter;
  }

  const at = squeezed.indexOf(letters);
  const found =
    at >= 0 ? positions.slice(at, at + letters.length) : inOrder(squeezed, letters, positions);

  const ranges: Array<[number, number]> = [];
  for (const index of found ?? []) {
    const last = ranges[ranges.length - 1];
    if (last && last[1] === index) last[1] = index + 1;
    else ranges.push([index, index + 1]);
  }

  return ranges;
}

/** Where each letter is found after the one before it, or null when one is missing. */
function inOrder(squeezed: string, letters: string, positions: number[]): number[] | null {
  const found: number[] = [];
  let from = 0;

  for (let index = 0; index < letters.length; index++) {
    const at = squeezed.indexOf(letters.charAt(index), from);
    if (at < 0) return null;
    found.push(positions[at] ?? at);
    from = at + 1;
  }

  return found;
}
