import { createHeadlessEditor } from "@input/pen";
import type { DocumentOp } from "@input/pen-types";
import { expect, it } from "vitest";
import type { RichText } from "../types";
import { fromPen, loadIntoPen } from "./convert";

const everything: RichText = {
  version: 1,
  blocks: [
    { type: "heading", level: 2, children: [{ text: "Made to last" }] },
    {
      type: "paragraph",
      children: [
        { text: "Waxed " },
        { text: "leather", marks: ["bold", "italic", "underline"] },
        { text: ", see the " },
        { text: "care guide", marks: ["bold"], link: { href: "https://example.com/care" } },
      ],
    },
    { type: "list", style: "bullet", items: [[{ text: "Waterproof" }], [{ text: "Resoleable" }]] },
    { type: "list", style: "numbered", items: [[{ text: "Unpack" }]] },
    { type: "heading", level: 4, children: [{ text: "Sizes" }] },
  ],
};

it("gives back every block and mark it loaded into Pen", () => {
  // Arrange
  const editor = createHeadlessEditor();

  // Act
  loadIntoPen(editor, everything);

  // Assert
  expect(fromPen(editor)).toEqual(everything);
});

it("replaces what Pen had before", () => {
  // Arrange
  const editor = createHeadlessEditor();
  loadIntoPen(editor, everything);

  // Act
  loadIntoPen(editor, { version: 1, blocks: [{ type: "paragraph", children: [{ text: "Hi" }] }] });

  // Assert
  expect(fromPen(editor)).toEqual({
    version: 1,
    blocks: [{ type: "paragraph", children: [{ text: "Hi" }] }],
  });
});

it("keeps two lists apart when a paragraph separates them", () => {
  // Arrange
  const editor = createHeadlessEditor();
  const lists: RichText = {
    version: 1,
    blocks: [
      { type: "list", style: "bullet", items: [[{ text: "One" }]] },
      { type: "paragraph", children: [{ text: "Between" }] },
      { type: "list", style: "bullet", items: [[{ text: "Two" }]] },
    ],
  };

  // Act
  loadIntoPen(editor, lists);

  // Assert
  expect(fromPen(editor)).toEqual(lists);
});

/** Builds Pen content directly, as pasting or Pen's own menus would. */
function penWith(ops: DocumentOp[]) {
  const editor = createHeadlessEditor();
  const existing = [...editor.blocks()].map((block) => block.id);
  editor.apply(
    [...existing.map((blockId) => ({ type: "delete-block" as const, blockId })), ...ops],
    { origin: "user" },
  );
  return editor;
}

const block = (blockId: string, blockType: string, props: Record<string, unknown> = {}) =>
  ({ type: "insert-block", blockId, blockType, props, position: "last" }) as const;
const text = (blockId: string, insert: string, marks?: Record<string, unknown>) =>
  ({ type: "splice-text", blockId, from: 0, to: 0, insert, marks }) as const;

it("keeps only what Larasell supports of what Pen can hold", () => {
  // Arrange
  const editor = penWith([
    block("h1", "heading", { level: 1 }),
    text("h1", "Title"),
    block("h6", "heading", { level: 6 }),
    text("h6", "Fine print"),
    block("p", "paragraph"),
    text("p", "struck", { strikethrough: true, textColor: { color: "red" }, italic: true }),
    block("quote", "blockquote"),
    text("quote", "Quoted"),
    block("code", "codeBlock"),
    text("code", "let a"),
    block("li", "bulletListItem", { indent: 2 }),
    text("li", "Nested"),
    block("table", "table"),
    block("divider", "divider"),
  ]);

  // Act
  const result = fromPen(editor);

  // Assert
  expect(result).toEqual({
    version: 1,
    blocks: [
      // Headings fit Larasell's levels 2 to 4: the product's name is the page's h1.
      { type: "heading", level: 2, children: [{ text: "Title" }] },
      { type: "heading", level: 4, children: [{ text: "Fine print" }] },
      { type: "paragraph", children: [{ text: "struck", marks: ["italic"] }] },
      // Other text blocks keep their text as paragraphs.
      { type: "paragraph", children: [{ text: "Quoted" }] },
      { type: "paragraph", children: [{ text: "let a" }] },
      // Lists are flat.
      { type: "list", style: "bullet", items: [[{ text: "Nested" }]] },
    ],
  });
});

it("keeps the text of links it can't save, without the link", () => {
  // Arrange
  const editor = penWith([
    block("p", "paragraph"),
    text("p", "unsafe", { link: { href: "javascript:alert(1)" } }),
  ]);

  // Act
  const result = fromPen(editor);

  // Assert
  expect(result).toEqual({
    version: 1,
    blocks: [{ type: "paragraph", children: [{ text: "unsafe" }] }],
  });
});

it("joins neighboring text with the same formatting", () => {
  // Arrange
  const editor = penWith([
    block("p", "paragraph"),
    text("p", "two", { bold: true, strikethrough: true }),
    { type: "splice-text", blockId: "p", from: 3, to: 3, insert: " words", marks: { bold: true } },
  ]);

  // Act
  const result = fromPen(editor);

  // Assert
  expect(result.blocks).toEqual([
    { type: "paragraph", children: [{ text: "two words", marks: ["bold"] }] },
  ]);
});
