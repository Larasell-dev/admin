/**
 * Formatted text as Portable Text (https://portabletext.org), limited to what the server's
 * `Larasell\Larasell\RichText\RichText` accepts. It doesn't depend on the editor: only
 * `pen/convert.ts` knows Pen's document.
 */
export type RichText = RichTextBlock[];

export type RichTextBlock = {
  _type: "block";
  _key: string;
  style: "normal" | "h2" | "h3" | "h4";
  listItem?: "bullet" | "number";
  level?: 1;
  markDefs: RichTextLink[];
  children: RichTextSpan[];
};

export type RichTextLink = { _type: "link"; _key: string; href: string };

export type RichTextDecorator = "strong" | "em" | "underline";

export type RichTextSpan = {
  _type: "span";
  _key: string;
  text: string;
  /** Decorators, and the keys of the block's links. */
  marks: string[];
};

/** In the order the server nests them. */
export const DECORATORS: readonly RichTextDecorator[] = ["strong", "em", "underline"];

/** Absolute http, https and mailto addresses, the only links the server accepts. */
export function isAllowedHref(href: unknown): href is string {
  return typeof href === "string" && /^(https?:\/\/\S+|mailto:\S+)$/i.test(href);
}

export const emptyRichText = (): RichText => [];
