import type { DocumentOp, Editor } from "@input/pen-types";
import {
  isAllowedHref,
  MARKS,
  type RichText,
  type RichTextBlock,
  type RichTextSpan,
} from "../types";

/**
 * The only file that knows Pen's document. Swapping the editor means rewriting this file and the
 * editor component, not the format the server stores.
 */

const LIST_TYPES = { bulletListItem: "bullet", numberedListItem: "numbered" } as const;
/** Pen's other text blocks, which keep their text as paragraphs. */
const TEXT_TYPES = new Set([
  "paragraph",
  "blockquote",
  "codeBlock",
  "callout",
  "toggle",
  "checkListItem",
]);
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

  const insert = (blockType: string, props: Record<string, unknown>, spans: RichTextSpan[]) => {
    const blockId = id();
    ops.push({ type: "insert-block", blockId, blockType, props, position: "last" });
    let offset = 0;
    for (const span of spans) {
      if (span.text === "") continue;
      const marks: Record<string, unknown> = { ...NO_MARKS };
      for (const mark of span.marks ?? []) marks[mark] = true;
      if (span.link) marks.link = { href: span.link.href };
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

  for (const block of document.blocks) {
    if (block.type === "paragraph") insert("paragraph", {}, block.children);
    if (block.type === "heading") insert("heading", { level: block.level }, block.children);
    if (block.type === "list") {
      const type = block.style === "bullet" ? "bulletListItem" : "numberedListItem";
      for (const item of block.items) insert(type, {}, item);
    }
  }

  // Pen needs a block to put the caret in.
  if (document.blocks.length === 0) insert("paragraph", {}, []);

  editor.apply(ops, { origin: "system" });
}

/**
 * Pen's content in Larasell's format. What Larasell doesn't support is reduced to what it does:
 * headings fit levels 2 to 4, other text blocks become paragraphs, lists are flat, other marks
 * and unsafe links are dropped with their text kept, and blocks without text, like tables, go.
 */
export function fromPen(editor: Editor): RichText {
  const blocks: RichTextBlock[] = [];

  for (const block of editor.blocks()) {
    if (block.parent) continue;
    const spans = () => toSpans(block.inlineDeltas());

    if (block.type in LIST_TYPES) {
      const style = LIST_TYPES[block.type as keyof typeof LIST_TYPES];
      const last = blocks.at(-1);
      if (last?.type === "list" && last.style === style) last.items.push(spans());
      else blocks.push({ type: "list", style, items: [spans()] });
    } else if (block.type === "heading") {
      const level = Math.min(4, Math.max(2, Number(block.props.level) || 1)) as 2 | 3 | 4;
      blocks.push({ type: "heading", level, children: spans() });
    } else if (TEXT_TYPES.has(block.type)) {
      blocks.push({ type: "paragraph", children: spans() });
    }
  }

  return { version: 1, blocks };
}

function toSpans(deltas: { insert: unknown; attributes?: Record<string, unknown> }[]) {
  const spans: RichTextSpan[] = [];

  for (const delta of deltas) {
    if (typeof delta.insert !== "string" || delta.insert === "") continue;
    const attributes = delta.attributes ?? {};
    const span: RichTextSpan = { text: delta.insert };
    const marks = MARKS.filter((mark) => attributes[mark]);
    if (marks.length > 0) span.marks = marks;
    const href = (attributes.link as { href?: unknown } | null | undefined)?.href;
    if (isAllowedHref(href)) span.link = { href };

    const previous = spans.at(-1);
    if (
      previous &&
      String(previous.marks) === String(span.marks) &&
      previous.link?.href === span.link?.href
    ) {
      previous.text += span.text;
    } else {
      spans.push(span);
    }
  }

  return spans;
}
