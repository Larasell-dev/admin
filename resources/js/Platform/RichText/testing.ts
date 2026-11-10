import type { RichTextBlock, RichTextLink } from "./types";

/** A Portable Text block for tests, keyed the way the editor keys them, with spans as [text, marks]. */
export function block(
  index: number,
  spans: [string, string[]?][],
  extra: Partial<Pick<RichTextBlock, "style" | "listItem" | "level">> = {},
  markDefs: RichTextLink[] = [],
): RichTextBlock {
  const key = `b${index}`;
  return {
    _type: "block",
    _key: key,
    style: "normal",
    ...extra,
    markDefs,
    children: spans.map(([text, marks = []], i) => ({
      _type: "span",
      _key: `${key}s${i}`,
      text,
      marks,
    })),
  };
}

export const bullet = { listItem: "bullet", level: 1 } as const;
export const number = { listItem: "number", level: 1 } as const;
