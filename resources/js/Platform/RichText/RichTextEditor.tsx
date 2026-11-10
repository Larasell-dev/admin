import { Toolbar as BaseToolbar } from "@base-ui/react/toolbar";
import { cn } from "cn";
import { defaultPreset } from "@input/pen";
import { Pen, useEditor, useFocusController, useSelection, useToolbar } from "@input/pen-react";
import { setInlineMark, toggleInlineMark } from "@input/pen-shortcuts";
import type { Editor } from "@input/pen-types";
import { Button, Icon, Input } from "@larasell-dev/ui";
import { Bold, Italic, Link, Underline } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { type ReactNode, useEffect, useId, useRef, useState } from "react";
import { type BlockKind, blockKinds, convertTo, isKind, markdownShortcuts } from "./pen/blocks";
import { fromPen, loadIntoPen } from "./pen/convert";
import SlashMenu from "./SlashMenu";
import { emptyRichText, isAllowedHref, type RichText } from "./types";

/**
 * Formatted text, submitted with the surrounding form as Larasell's rich text JSON under `name`.
 * Only this component and `pen/convert.ts` know the editor behind it.
 */
export default function RichTextEditor({
  name,
  label,
  defaultValue,
  placeholder,
  invalid = false,
}: {
  name: string;
  /** The accessible name of the text area. */
  label: string;
  defaultValue: RichText | null;
  /** Shown while it's empty. */
  placeholder?: string;
  invalid?: boolean;
}) {
  const editor = useEditor({
    preset: defaultPreset(),
    extensions: [markdownShortcuts()],
    a11yLabel: label,
  });
  const focus = useFocusController(editor);
  // A new object on every render; read from a ref so the editor isn't reloaded while typing.
  const focusRef = useRef(focus);
  useEffect(() => {
    focusRef.current = focus;
  });
  const scope = useId();
  // Some browsers fade the bottom even when nothing scrolls, so the fade only applies while the
  // content overflows.
  const content = useRef<HTMLDivElement>(null);
  const [overflowing, setOverflowing] = useState(false);

  useEffect(() => {
    const element = content.current;
    if (!element) return;
    const update = () => setOverflowing(element.scrollHeight > element.clientHeight);
    update();
    const observer = new ResizeObserver(update);
    observer.observe(element);
    for (const child of element.children) observer.observe(child);
    return () => observer.disconnect();
  }, []);
  const input = useRef<HTMLInputElement>(null);
  const initial = JSON.stringify(defaultValue ?? emptyRichText());

  useEffect(() => {
    loadIntoPen(editor, JSON.parse(initial));

    // Every change the admin makes goes into the input, and tells the form it changed.
    const stop = editor.on("commit", (event) => {
      if (event.origin.type === "system" || !input.current) return;
      // Deleting everything leaves Pen without a line to type in; give it an empty one back.
      if (!editor.firstBlock()) {
        loadIntoPen(editor, emptyRichText());
        const first = editor.firstBlock();
        if (first) void focusRef.current.start(first.id);
      }
      input.current.value = JSON.stringify(fromPen(editor));
      input.current.dispatchEvent(new Event("input", { bubbles: true }));
    });

    // Resetting the form puts the input's saved value back; the editor shows it again.
    const form = input.current?.form;
    const reset = () =>
      setTimeout(() => input.current && loadIntoPen(editor, JSON.parse(input.current.value)));
    form?.addEventListener("reset", reset);

    return () => {
      stop();
      form?.removeEventListener("reset", reset);
    };
  }, [editor, initial]);

  return (
    // The Input's look: its border, surface and type, with its focus ring around the whole field
    // while the text is focused.
    <div
      data-invalid={invalid || undefined}
      className="border-base-strong bg-base-surface text-base-primary has-[[data-pen-editor-root]:focus-within]:focus-ring data-invalid:border-intent-negative data-invalid:focus-ring-negative flex w-full flex-col border"
    >
      {/* A text input that's hidden, not a hidden input, so resetting the form restores it. */}
      <input ref={input} type="text" hidden readOnly name={name} defaultValue={initial} />
      <Toolbar editor={editor} />
      {/* Pen outlines each row it focuses; the field's ring replaces that. Tailwind resets headings
          and links, so the content gets its look back here. List markers sit on their text's
          baseline, in a column as wide for 1. as for 10., rather than centered in Pen's own box. */}
      <div
        // Clicks beside or below the text, in the padding or the space left to the minimum height,
        // put the caret at the end, as clicking an input's empty space does.
        onMouseDown={(event) => {
          const target = event.target as HTMLElement;
          const last = editor.lastBlock();
          if (target.closest("[data-pen-editor-block]") || !last) return;
          event.preventDefault();
          void focus.end(last.id);
        }}
        // Esc leaves the field, as in the other inputs, instead of Pen selecting the whole line.
        // An open / menu takes Esc before this.
        onKeyDownCapture={(event) => {
          if (event.key !== "Escape") return;
          event.preventDefault();
          event.stopPropagation();
          focus.blur();
        }}
        data-rich-text={scope}
        ref={content}
        className={cn(
          "type-input-md max-h-96 min-h-32 cursor-text overflow-y-auto py-2.5 px-3.5 [--pen-placeholder-color:var(--text-color-base-tertiary)] [&_[data-pen-editor-blocks-host]>*+*]:pt-2 [&_[data-pen-inline-content]]:outline-none! [&_[data-pen-list-item-layout]]:items-baseline! [&_[data-pen-list-item-marker]]:min-h-0! [&_[data-pen-list-item-marker]]:min-w-4 [&_[data-pen-list-item-marker]]:items-baseline! [&_[data-pen-list-item-marker]]:justify-end [&_a]:underline [&_a]:underline-offset-2 [&_h2]:type-title-lg [&_h3]:type-title-md [&_h4]:type-title-sm",
          overflowing && "scroll-mask-y",
        )}
      >
        {/* Pen also labels every empty row it focuses, e.g. "Text" or "List"; only the empty
            field's own placeholder shows. */}
        <style>{`[data-rich-text="${CSS.escape(scope)}"] [data-placeholder-visible]:not([data-placeholder="${CSS.escape(placeholder ?? "")}"])::before { content: none; }`}</style>
        {/* PenEditor's own parts, since PenEditor doesn't pass on the block options. */}
        <Pen.Editor.Root
          key={editor.internals.viewId}
          editor={editor}
          // A plain text field: no block handles, dragging or selecting whole blocks.
          blockSelection={false}
          blockDragAndDrop={{ enabled: false }}
        >
          <Pen.Editor.Content emptyPlaceholder={placeholder} />
        </Pen.Editor.Root>
        <SlashMenu editor={editor} />
      </div>
    </div>
  );
}

function Toolbar({ editor }: { editor: Editor }) {
  const state = useToolbar(editor);
  const selection = useSelection(editor);
  const blockId = selection?.type === "text" ? selection.focus.blockId : null;
  const block = blockId ? editor.getBlock(blockId) : null;
  const [linking, setLinking] = useState(false);

  const kinds = blockKinds();
  /** Turns the caret's block into the kind, or back into a paragraph when it already is one. */
  const toggle = (kind: BlockKind) => {
    if (block) convertTo(editor, block.id, isKind(block, kind) ? kinds[0] : kind);
  };

  return (
    <div className="border-base-subtle bg-base-surface border-b">
      {/* One tab stop; the arrow keys move between the buttons. */}
      <BaseToolbar.Root
        aria-label={fbs(
          "Formatting",
          "Rich text editor: accessible name of the toolbar with the formatting buttons",
        )}
        className="flex flex-wrap items-center gap-1 px-1.5 py-1"
      >
        <BaseToolbar.Group
          aria-label={fbs(
            "Text style",
            "Rich text editor: accessible name of the toolbar group with bold, italic, underline and link",
          )}
          className="flex items-center gap-0.5"
        >
          <ToolbarButton
            label={fbs(
              "Bold",
              "Rich text editor: toolbar button that makes the selected text bold",
            )}
            icon={Bold}
            pressed={Boolean(state.activeMarks.bold)}
            onPress={() => toggleInlineMark(editor, "bold")}
          />
          <ToolbarButton
            label={fbs(
              "Italic",
              "Rich text editor: toolbar button that makes the selected text italic",
            )}
            icon={Italic}
            pressed={Boolean(state.activeMarks.italic)}
            onPress={() => toggleInlineMark(editor, "italic")}
          />
          <ToolbarButton
            label={fbs(
              "Underline",
              "Rich text editor: toolbar button that underlines the selected text",
            )}
            icon={Underline}
            pressed={Boolean(state.activeMarks.underline)}
            onPress={() => toggleInlineMark(editor, "underline")}
          />
          <ToolbarButton
            label={fbs(
              "Link",
              "Rich text editor: toolbar button that links the selected text to a web address",
            )}
            icon={Link}
            pressed={Boolean(state.activeMarks.link) || linking}
            onPress={() => setLinking(!linking)}
          />
        </BaseToolbar.Group>
        <BaseToolbar.Separator className="border-base-strong h-4 border-l" />
        <BaseToolbar.Group
          aria-label={fbs(
            "Block type",
            "Rich text editor: accessible name of the toolbar group that turns the line into a heading or list",
          )}
          className="flex items-center gap-0.5"
        >
          {kinds.slice(1).map((kind) => (
            <ToolbarButton
              key={kind.id}
              label={kind.label}
              icon={kind.icon}
              pressed={isKind(block, kind)}
              onPress={() => toggle(kind)}
            />
          ))}
        </BaseToolbar.Group>
      </BaseToolbar.Root>
      {linking && (
        <LinkForm
          defaultHref={(state.activeMarks.link as { href?: string } | undefined)?.href ?? ""}
          onApply={(href) => {
            setInlineMark(editor, "link", href === "" ? null : { href });
            setLinking(false);
          }}
          onCancel={() => setLinking(false)}
        />
      )}
    </div>
  );
}

function ToolbarButton({
  label,
  icon,
  pressed,
  onPress,
}: {
  label: string;
  icon: Parameters<typeof Icon>[0]["icon"];
  pressed: boolean;
  onPress: () => void;
}) {
  return (
    // Base UI's toolbar button, not the shared IconButton: toolbar buttons are smaller, borderless, and show whether their
    // format is on.
    <BaseToolbar.Button
      aria-label={label}
      aria-pressed={pressed}
      // Keeps the editor's selection, which the button formats.
      onMouseDown={(event) => event.preventDefault()}
      onClick={onPress}
      className="text-base-secondary hover:bg-action-neutral-ghost-hover hover:text-base-primary aria-pressed:bg-action-neutral-secondary-hover aria-pressed:text-base-primary focus-visible:focus-ring box-border inline-flex size-8 shrink-0 cursor-pointer items-center justify-center select-none"
    >
      <Icon icon={icon} />
    </BaseToolbar.Button>
  );
}

/**
 * The address for the selected text. The selection stays in the editor while the address is
 * typed, so applying it links that text; an empty address removes the link.
 */
function LinkForm({
  defaultHref,
  onApply,
  onCancel,
}: {
  defaultHref: string;
  onApply: (href: string) => void;
  onCancel: () => void;
}) {
  const [href, setHref] = useState(defaultHref);
  const id = useId();
  const valid = href === "" || isAllowedHref(href);
  let error: ReactNode = null;
  if (!valid)
    error = fbs(
      "Enter an address starting with https://, http:// or mailto:.",
      "Rich text editor: error below the link address field when the address isn't allowed",
    );

  return (
    <div className="flex flex-col gap-1.5 border-base-subtle border-t p-2">
      <div className="flex items-center gap-2">
        <div className="min-w-0 flex-1">
          <Input
            id={id}
            aria-label={fbs(
              "Link address",
              "Rich text editor: accessible name of the field for the web address the selected text links to",
            )}
            placeholder="https://"
            value={href}
            autoComplete="off"
            spellCheck={false}
            // Enter would submit the product form.
            onKeyDown={(event) => {
              if (event.key === "Enter") {
                event.preventDefault();
                if (valid) onApply(href.trim());
              }
              if (event.key === "Escape") onCancel();
            }}
            onChange={(event) => setHref(event.currentTarget.value)}
          />
        </div>
        <Button
          size="sm"
          variant="secondary"
          disabled={!valid}
          onClick={() => onApply(href.trim())}
        >
          {href === "" ? (
            <fbt desc="Rich text editor: button that removes the link from the selected text">
              Remove link
            </fbt>
          ) : (
            <fbt desc="Rich text editor: button that links the selected text to the entered address">
              Apply
            </fbt>
          )}
        </Button>
      </div>
      {error && <p className="type-body-sm text-danger">{error}</p>}
    </div>
  );
}
