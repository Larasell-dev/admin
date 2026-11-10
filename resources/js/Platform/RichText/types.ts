/**
 * Larasell's rich text format, the same as the server's `Platform\RichText\RichText`. It doesn't
 * depend on the editor: only `pen/convert.ts` knows Pen's document.
 */
export type RichText = {
  version: 1;
  blocks: RichTextBlock[];
};

export type RichTextBlock =
  | { type: "paragraph"; children: RichTextSpan[] }
  | { type: "heading"; level: 2 | 3 | 4; children: RichTextSpan[] }
  | { type: "list"; style: "bullet" | "numbered"; items: RichTextSpan[][] };

export type RichTextMark = "bold" | "italic" | "underline";

export type RichTextSpan = {
  text: string;
  marks?: RichTextMark[];
  link?: { href: string };
};

/** In the order the server nests them. */
export const MARKS: readonly RichTextMark[] = ["bold", "italic", "underline"];

/** Absolute http, https and mailto addresses, the only links the server accepts. */
export function isAllowedHref(href: unknown): href is string {
  return typeof href === "string" && /^(https?:\/\/\S+|mailto:\S+)$/i.test(href);
}

export const emptyRichText = (): RichText => ({ version: 1, blocks: [] });
