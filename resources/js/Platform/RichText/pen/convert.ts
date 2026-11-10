import type { DocumentOp, Editor } from "@input/pen-types";
import {
  DECORATORS,
  isAllowedHref,
  type RichText,
  type RichTextBlock,
  type RichTextDecorator,
  type RichTextSpan,
} from "../types";

/**
 * The only file that knows Pen's document. Swapping the editor means rewriting this file and the
 * editor component, not the format the server stores.
 */

const LIST_TYPES = { bulletListItem: "bullet", numberedListItem: "number" } as const;
/** Pen's other text blocks, which keep their text as paragraphs. */
const TEXT_TYPES = new Set([
  "paragraph",
  "blockquote",
  "codeBlock",
  "callout",
  "toggle",
  "checkListItem",
]);
/** Portable Text's decorators with Pen's names for them. */
const PEN_MARKS: Record<RichTextDecorator, string> = {
  strong: "bold",
  em: "italic",
  underline: "underline",
};
/** Every mark Larasell knows, unset, so inserted text doesn't take over its neighbor's. */
const NO_MARKS = { bold: null, italic: null, underline: null, link: null };

let nextId = 0;
const id = () => `larasell-${Date.now().toString(36)}-${nextId++}`;

/** Replaces Pen's content with the document, as one change. */
export function loadIntoPen(editor: Editor, document: RichText) {
  const ops: DocumentOp[] = [...editor.blocks()].map((block) => ({
    type: "delete-block",
    blockId: block.id,
  }));

  const insert = (blockType: string, props: Record<string, unknown>, block?: RichTextBlock) => {
    const blockId = id();
    ops.push({ type: "insert-block", blockId, blockType, props, position: "last" });
    const links = new Map((block?.markDefs ?? []).map((link) => [link._key, link.href]));
    let offset = 0;
    for (const span of block?.children ?? []) {
      if (span.text === "") continue;
      const marks: Record<string, unknown> = { ...NO_MARKS };
      for (const mark of span.marks ?? []) {
        if (mark in PEN_MARKS) marks[PEN_MARKS[mark as RichTextDecorator]] = true;
        else if (links.has(mark)) marks.link = { href: links.get(mark) };
      }
      ops.push({
        type: "splice-text",
        blockId,
        from: offset,
        to: offset,
        insert: span.text,
        marks,
      });
      offset += span.text.length;
    }
  };

  for (const block of document) {
    if (block.listItem) {
      insert(block.listItem === "bullet" ? "bulletListItem" : "numberedListItem", {}, block);
    } else if (block.style === "normal") {
      insert("paragraph", {}, block);
    } else {
      insert("heading", { level: Number(block.style.slice(1)) }, block);
    }
  }

  // Pen needs a block to put the caret in.
  if (document.length === 0) insert("paragraph", {});

  editor.apply(ops, { origin: "system" });
}

/**
 * Pen's content as Portable Text that Larasell supports. The rest is reduced to it: headings fit
 * levels 2 to 4, other text blocks become paragraphs, lists are flat, other marks and unsafe
 * links are dropped with their text kept, and blocks without text, like tables, go.
 */
export function fromPen(editor: Editor): RichText {
  const blocks: RichTextBlock[] = [];

  for (const block of editor.blocks()) {
    if (block.parent) continue;
    const key = `b${blocks.length}`;
    const base = { _type: "block" as const, _key: key, ...toChildren(key, block.inlineDeltas()) };

    if (block.type in LIST_TYPES) {
      const listItem = LIST_TYPES[block.type as keyof typeof LIST_TYPES];
      blocks.push({ ...base, style: "normal", listItem, level: 1 });
    } else if (block.type === "heading") {
      const level = Math.min(4, Math.max(2, Number(block.props.level) || 1));
      blocks.push({ ...base, style: `h${level}` as "h2" | "h3" | "h4" });
    } else if (TEXT_TYPES.has(block.type)) {
      blocks.push({ ...base, style: "normal" });
    }
  }

  return blocks;
}

function toChildren(
  key: string,
  deltas: { insert: unknown; attributes?: Record<string, unknown> }[],
): Pick<RichTextBlock, "markDefs" | "children"> {
  const markDefs: RichTextBlock["markDefs"] = [];
  const children: RichTextSpan[] = [];

  for (const delta of deltas) {
    if (typeof delta.insert !== "string" || delta.insert === "") continue;
    const attributes = delta.attributes ?? {};
    const marks: string[] = DECORATORS.filter((mark) => attributes[PEN_MARKS[mark]]);
    const href = (attributes.link as { href?: unknown } | null | undefined)?.href;
    if (isAllowedHref(href)) {
      let link = markDefs.find((def) => def.href === href);
      if (!link) {
        link = { _type: "link", _key: `${key}l${markDefs.length}`, href };
        markDefs.push(link);
      }
      marks.push(link._key);
    }

    const previous = children.at(-1);
    if (previous && String(previous.marks) === String(marks)) {
      previous.text += delta.insert;
    } else {
      children.push({
        _type: "span",
        _key: `${key}s${children.length}`,
        text: delta.insert,
        marks,
      });
    }
  }

  return { markDefs, children };
}
