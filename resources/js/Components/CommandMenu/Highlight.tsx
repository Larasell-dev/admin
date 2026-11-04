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
      <mark key={from} className="bg-transparent font-medium text-mauve-950 dark:text-mauve-50">
        {text.slice(from, to)}
      </mark>,
    );
    at = to;
  }

  if (at < text.length) parts.push(text.slice(at));

  return <>{parts}</>;
}
