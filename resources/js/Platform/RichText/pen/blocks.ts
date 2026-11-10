import { autoformatExtension, defaultBlockRules, defaultInlineRules } from "@input/pen-autoformat";
import { convertBlockOps } from "@input/pen-core";
import type { BlockHandle, Editor, Extension } from "@input/pen-types";
import { H2, H3, ListOrdered, ListUnordered, Text } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";

/**
 * The kinds of block Larasell's format has, as Pen blocks. The toolbar and the slash menu offer these and nothing else, so the editor only writes what the format can hold.
 */
export function blockKinds() {
  return [
    {
      id: "paragraph",
      type: "paragraph",
      props: {},
      icon: Text,
      label: fbs("Text", "Rich text editor: name of the plain paragraph block in its menus"),
      keywords: ["paragraph", "text", "p"],
    },
    {
      id: "heading",
      type: "heading",
      props: { level: 2 },
      icon: H2,
      label: fbs("Heading", "Rich text editor: name of the heading block in its menus"),
      keywords: ["heading", "title", "h2"],
    },
    {
      id: "subheading",
      type: "heading",
      props: { level: 3 },
      icon: H3,
      label: fbs("Subheading", "Rich text editor: name of the smaller heading block in its menus"),
      keywords: ["subheading", "heading", "h3"],
    },
    {
      id: "bullet-list",
      type: "bulletListItem",
      props: {},
      icon: ListUnordered,
      label: fbs("Bulleted list", "Rich text editor: name of the bulleted list block in its menus"),
      keywords: ["bulleted list", "bullet", "list", "ul"],
    },
    {
      id: "numbered-list",
      type: "numberedListItem",
      props: {},
      icon: ListOrdered,
      label: fbs("Numbered list", "Rich text editor: name of the numbered list block in its menus"),
      keywords: ["numbered list", "ordered", "list", "ol"],
    },
  ] as const;
}

export type BlockKind = ReturnType<typeof blockKinds>[number];

/** Whether the block is of the kind. */
export function isKind(block: BlockHandle | null, kind: BlockKind) {
  if (!block || block.type !== kind.type) return false;
  return !("level" in kind.props) || block.props.level === kind.props.level;
}

/** Turns the block into the kind, keeping its text. */
export function convertTo(editor: Editor, blockId: string, kind: BlockKind) {
  editor.apply(
    convertBlockOps(editor, { blockId, newType: kind.type, newProps: { ...kind.props } }),
    { origin: "user" },
  );
}

/** Pen's Markdown shortcuts that write Larasell's blocks and marks: ##, ###, - and 1., ** and *. */
const BLOCK_RULES = ["heading-2", "heading-3", "unordered-list", "ordered-list"];
const INLINE_RULES = ["bold", "italic"];

export function markdownShortcuts(): Extension {
  return autoformatExtension({
    disableDefaults: true,
    disableDefaultInlineRules: true,
    rules: defaultBlockRules.filter((rule) =>
      BLOCK_RULES.includes(rule.id.replace("input-rule:", "")),
    ),
    inlineRules: defaultInlineRules.filter((rule) =>
      INLINE_RULES.includes(rule.id.replace("inline-rule:", "")),
    ),
  });
}
