import { createHeadlessEditor } from "@input/pen";
import type { DocumentOp } from "@input/pen-types";
import { expect, it } from "vitest";
import { block as pt, bullet, number } from "../testing";
import type { RichText } from "../types";
import { fromPen, loadIntoPen } from "./convert";

const everything: RichText = [
  pt(0, [["Made to last"]], { style: "h2" }),
  pt(
    1,
    [
      ["Waxed "],
      ["leather", ["strong", "em", "underline"]],
      [", see the "],
      ["care guide", ["strong", "b1l0"]],
    ],
    {},
    [{ _type: "link", _key: "b1l0", href: "https://example.com/care" }],
  ),
  pt(2, [["Waterproof"]], bullet),
  pt(3, [["Resoleable"]], bullet),
  pt(4, [["Unpack"]], number),
  pt(5, [["Sizes"]], { style: "h4" }),
];

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
  loadIntoPen(editor, [pt(0, [["Hi"]])]);

  // Assert
  expect(fromPen(editor)).toEqual([pt(0, [["Hi"]])]);
});

it("keeps two lists apart when a paragraph separates them", () => {
  // Arrange
  const editor = createHeadlessEditor();
  const lists: RichText = [
    pt(0, [["One"]], bullet),
    pt(1, [["Between"]]),
    pt(2, [["Two"]], bullet),
  ];

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
  expect(result).toEqual([
    // Headings fit Larasell's levels 2 to 4: the product's name is the page's h1.
    pt(0, [["Title"]], { style: "h2" }),
    pt(1, [["Fine print"]], { style: "h4" }),
    pt(2, [["struck", ["em"]]]),
    // Other text blocks keep their text as paragraphs.
    pt(3, [["Quoted"]]),
    pt(4, [["let a"]]),
    // Lists are flat.
    pt(5, [["Nested"]], bullet),
  ]);
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
  expect(result).toEqual([pt(0, [["unsafe"]])]);
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
  expect(result).toEqual([pt(0, [["two words", ["strong"]]])]);
});
