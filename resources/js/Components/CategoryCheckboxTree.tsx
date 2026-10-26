import { Link } from "@inertiajs/react";
import {
  Button,
  Checkbox,
  EmptyState,
  Icon,
  IconButton,
  Menu,
  Section,
  useScrollMaskScrollbar,
} from "@larasell-dev/ui";
import {
  AddLine,
  ArrowRightSLine,
  EyeLine,
  FolderLine,
  FolderOpenLine,
  FoldersLine,
  More2Fill,
} from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { MotionConfig, motion } from "motion/react";
import { useEffect, useMemo, useRef, useState } from "react";
import { overlayExclusive } from "../utils/hotkeys";
import Tree, { TreeItem } from "./Tree";

type Category = Larasell.Admin.Data.ViewModel.CategoryData;
type CheckedState = boolean | "mixed";

/** The ids of the categories without children at or below `category`. */
function leafIds(category: Category): number[] {
  if (category.children.length === 0) return [category.id];
  return category.children.flatMap(leafIds);
}

/** Every category in the tree, parents before their children. */
function flatten(categories: Category[]): Category[] {
  return categories.flatMap((category) => [category, ...flatten(category.children)]);
}

/** Checked when every category without children at or below it is selected, mixed when some are. */
function checkedState(category: Category, selected: ReadonlySet<number>): CheckedState {
  const leaves = leafIds(category);
  const count = leaves.filter((id) => selected.has(id)).length;
  if (count === 0) return false;
  return count === leaves.length || "mixed";
}

/**
 * The store's categories in a card, each with a checkbox. Only categories without children are
 * selected themselves: a parent is checked once all of them below it are, mixed while some are.
 * Clicking a parent selects or unselects everything below it, even while it's closed. Its
 * chevron opens and closes it; every category starts closed.
 *
 * It submits a `categories[]` for each checked category, parents included, so the form leaves
 * the field out when none is. Selecting one changes the surrounding Inertia `<Form>`, so it
 * shows the unsaved changes toast, and the form's reset puts the selection back.
 */
export default function CategoryCheckboxTree({
  categories,
  defaultSelectedIds,
  createUrl,
}: {
  categories: Category[];
  defaultSelectedIds: number[];
  /** Where categories are created, offered while the store has none. */
  createUrl: string;
}) {
  const all = useMemo(() => flatten(categories), [categories]);
  const [selected, setSelected] = useState(() => {
    const leaves = new Set(
      all.filter((category) => category.children.length === 0).map((c) => c.id),
    );
    return new Set(defaultSelectedIds.filter((id) => leaves.has(id)));
  });
  const [expandedIds, setExpandedIds] = useState(() => new Set<number>());
  const inputs = useRef<HTMLDivElement>(null);
  const scroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(scroll);

  // Inertia compares the form's data on its input and change events, so this one fires once the
  // inputs show the new selection.
  const changed = useRef(false);
  useEffect(() => {
    if (!changed.current) return;
    changed.current = false;
    inputs.current?.dispatchEvent(new Event("change", { bubbles: true }));
  }, [selected]);

  // The form's reset sets the inputs directly. Read them back right away, before React's next
  // render puts the old selection back into them.
  useEffect(() => {
    const form = inputs.current?.closest("form");
    if (!form) return;
    const sync = () => {
      const checked = inputs.current?.querySelectorAll<HTMLInputElement>("input:checked") ?? [];
      setSelected(new Set(Array.from(checked, (input) => Number(input.value))));
    };
    form.addEventListener("reset", sync);
    return () => form.removeEventListener("reset", sync);
  }, []);

  const toggle = (category: Category) => {
    const leaves = leafIds(category);
    const next = new Set(selected);
    if (checkedState(category, selected) === true) leaves.forEach((id) => next.delete(id));
    else leaves.forEach((id) => next.add(id));
    changed.current = true;
    setSelected(next);
  };

  const toggleOpen = (id: number) =>
    setExpandedIds((current) => {
      const next = new Set(current);
      if (!next.delete(id)) next.add(id);
      return next;
    });

  return (
    <Section.Card>
      <div ref={inputs} hidden>
        {all.map((category) => (
          <input
            key={category.id}
            type="checkbox"
            name="categories[]"
            value={category.id}
            checked={checkedState(category, selected) === true}
            readOnly
          />
        ))}
      </div>
      {categories.length === 0 ? (
        <div className="flex justify-center px-(--inset) py-10">
          <EmptyState
            icon={FoldersLine}
            title="No categories yet"
            description="Create categories to list the product in the store."
          >
            <Button
              nativeButton={false}
              // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
              role="link"
              render={<Link href={createUrl} prefetch="hover" />}
            >
              <Icon icon={AddLine} />
              Create categories
            </Button>
          </EmptyState>
        </div>
      ) : (
        // The rows run to the card's edges and pad their content by its --inset themselves.
        <div ref={scroll} className="scroll-mask-y max-h-80 overflow-y-auto">
          <MotionConfig reducedMotion="user">
            <Tree
              aria-label="Categories"
              items={categories}
              getLabel={(category) => category.name}
              expandedIds={expandedIds}
              onExpandedIdsChange={setExpandedIds}
              toggleOnClick={false}
              renderItem={(category, { expanded, tabbable }) => (
                <CategoryRow
                  category={category}
                  checked={checkedState(category, selected)}
                  expanded={expanded}
                  tabbable={tabbable}
                  onToggle={() => toggle(category)}
                  onToggleOpen={() => toggleOpen(category.id)}
                />
              )}
            />
          </MotionConfig>
        </div>
      )}
    </Section.Card>
  );
}

/**
 * A category's row, which selects it on click, Enter and Space. The checkbox only shows the
 * row's state, so clicks pass through it to the row. The child count, chevron and menu sit next
 * to the row, not inside it, so they stay out of its name and clicking them never selects.
 */
function CategoryRow({
  category,
  checked,
  expanded,
  tabbable,
  onToggle,
  onToggleOpen,
}: {
  category: Category;
  checked: CheckedState;
  expanded: boolean | undefined;
  tabbable: boolean;
  onToggle: () => void;
  onToggleOpen: () => void;
}) {
  return (
    <div className="group/row relative">
      <TreeItem
        aria-checked={checked}
        onClick={onToggle}
        onKeyDown={(event) => {
          if (event.key === " ") onToggle();
        }}
        className={`type-body-md flex cursor-pointer items-center gap-2 py-2 pl-[calc(0.5rem+var(--tree-level)*1.5rem)] text-mauve-950 outline-none select-none group-hover/row:bg-mauve-50 focus-visible:focus-ring focus-visible:-outline-offset-2 dark:text-mauve-50 dark:group-hover/row:bg-mauve-900 ${expanded === undefined ? "pr-10" : "pr-28"}`}
      >
        <Checkbox.Indicator
          checked={checked === true}
          indeterminate={checked === "mixed"}
          readOnly
          tabIndex={-1}
          aria-hidden
          data-testid="category-checkbox"
          DANGEROUS_className="pointer-events-none"
        />
        <span className="flex shrink-0 text-base text-mauve-500 dark:text-mauve-400">
          <Icon icon={expanded ? FolderOpenLine : FolderLine} />
        </span>
        <span
          className={`min-w-0 flex-1 truncate ${category.visible ? "" : "text-mauve-500 dark:text-mauve-400"}`}
        >
          {category.name}
        </span>
      </TreeItem>
      {/* Clicks on the count pass through to the row underneath. */}
      <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center">
        {expanded !== undefined && (
          <>
            <span className="type-body-md pr-1 text-mauve-500 tabular-nums dark:text-mauve-400">
              ({category.children.length})
            </span>
            {/* Out of the tab order: the row itself opens and closes with the arrow keys. */}
            <IconButton
              variant="tertiary"
              size="sm"
              aria-label={`${expanded ? "Close" : "Open"} ${category.name}`}
              tabIndex={-1}
              onClick={onToggleOpen}
              DANGEROUS_className="pointer-events-auto focus-visible:-outline-offset-2"
            >
              <motion.span
                className="flex"
                initial={false}
                animate={{ rotate: expanded ? 90 : 0 }}
                transition={{ type: "spring", bounce: 0, visualDuration: 0.15 }}
              >
                <Icon icon={ArrowRightSLine} />
              </motion.span>
            </IconButton>
          </>
        )}
        <CategoryMenu category={category} tabbable={tabbable} />
      </div>
    </div>
  );
}

/** A category's actions. Only the tree's tab stop row puts its button in the tab order. */
function CategoryMenu({ category, tabbable }: { category: Category; tabbable: boolean }) {
  return (
    <Menu.Root>
      <Menu.Trigger
        tabIndex={tabbable ? 0 : -1}
        render={
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={`Actions for ${category.name}`}
            DANGEROUS_className="pointer-events-auto focus-visible:-outline-offset-2"
          >
            <Icon icon={More2Fill} />
          </IconButton>
        }
      />
      <Menu.Popup>
        <HotkeysProvider scope="category-actions-menu" exclusive={overlayExclusive}>
          <Menu.Item icon={EyeLine} render={<Link href={category.url} prefetch="hover" />}>
            View category
          </Menu.Item>
        </HotkeysProvider>
      </Menu.Popup>
    </Menu.Root>
  );
}
