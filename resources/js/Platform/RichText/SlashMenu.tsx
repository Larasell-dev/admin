import { Pen, useSlashMenu } from "@input/pen-react";
import type { Editor } from "@input/pen-types";
import { Icon } from "@larasell-dev/ui";
import { useMemo, useState } from "react";
import { blockKinds, convertTo } from "./pen/blocks";

/**
 * Typing "/" at the start of a word opens a menu of Larasell's blocks; picking one removes the
 * "/" and what was typed after it, and turns the line into that block. Pen finds the "/", places
 * the menu at the caret and moves through it with the arrow keys, Enter and Esc.
 */
export default function SlashMenu({ editor }: { editor: Editor }) {
  const pen = useSlashMenu(editor);
  const [selectedIndex, setSelectedIndex] = useState(0);
  const query = pen.target?.query.toLowerCase() ?? "";
  const kinds = useMemo(
    () =>
      blockKinds().filter(
        (kind) =>
          kind.label.toLowerCase().includes(query) ||
          kind.keywords.some((keyword) => keyword.startsWith(query)),
      ),
    [query],
  );
  const selected = Math.min(selectedIndex, Math.max(kinds.length - 1, 0));

  const confirm = (index = selected) => {
    const kind = kinds[index];
    const target = pen.target;
    if (!kind || !target) return false;
    editor.apply(
      [
        {
          type: "splice-text",
          blockId: target.blockId,
          from: target.startOffset,
          to: target.endOffset,
          insert: "",
        },
      ],
      { origin: "user" },
    );
    convertTo(editor, target.blockId, kind);
    setSelectedIndex(0);
    return true;
  };

  const controller = {
    ...pen,
    editor,
    open: pen.open,
    // Pen's keyboard handling counts and confirms these.
    items: kinds.map((kind) => ({ type: kind.id, display: { title: kind.label } })),
    selectedIndex: selected,
    select: setSelectedIndex,
    confirm,
    dismiss: () => {
      setSelectedIndex(0);
      pen.dismiss();
    },
  };

  return (
    <Pen.SlashMenu.Root controller={controller}>
      <Pen.SlashMenu.Content asChild>
        <div className="border-base-subtle bg-base-elevated bg-clip-padding shadow-popover w-56 overflow-y-auto border p-1">
          <Pen.SlashMenu.List>
            {kinds.map((kind, index) => (
              <Pen.SlashMenu.Item key={kind.id} index={index} asChild>
                <div className="type-body-md text-base-primary data-selected:bg-action-neutral-ghost-hover flex cursor-pointer items-center gap-2 px-2.5 py-1.5 select-none">
                  <Icon icon={kind.icon} />
                  {kind.label}
                </div>
              </Pen.SlashMenu.Item>
            ))}
          </Pen.SlashMenu.List>
          <Pen.SlashMenu.Empty asChild>
            <p className="type-body-sm text-base-tertiary px-2.5 py-1.5">
              <fbt desc="Rich text editor: shown in the / menu when no block matches what was typed after the /">
                No matching blocks
              </fbt>
            </p>
          </Pen.SlashMenu.Empty>
        </div>
      </Pen.SlashMenu.Content>
    </Pen.SlashMenu.Root>
  );
}
