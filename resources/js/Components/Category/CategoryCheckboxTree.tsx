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
import { cn } from "cn";
import { useEffect, useMemo, useRef, useState } from "react";
import { overlayExclusive } from "../../utils/hotkeys";
import Tree, { TreeItem } from "../Tree";
import { fbs } from "fbtee";

type Category = Larasell.Admin.Category.Data.CategoryOptionData;
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
  /** Where categories are created, offered while the store has none. Null while they're turned off. */
  createUrl: string | null;
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
            title={fbs(
              "No categories yet",
              "Product form, categories section: heading shown while the store has no categories to pick from",
            )}
            description={fbs(
              "Create categories to list the product in the store.",
              "Product form, categories section: text below 'No categories yet', shown while the store has no categories",
            )}
            // Only given children when it has an action; even `null` renders an empty actions row.
            {...(createUrl && {
              children: (
                <Button render={<Link href={createUrl} prefetch="hover" />}>
                  <Icon icon={AddLine} />
                  <fbt desc="Product form, categories section: button that goes to creating a category, shown while the store has none">
                    Create categories
                  </fbt>
                </Button>
              ),
            })}
          />
        </div>
      ) : (
        // The rows run to the card's edges and pad their content by its --inset themselves.
        <div ref={scroll} className="scroll-mask-y max-h-80 overflow-y-auto">
          <MotionConfig reducedMotion="user">
            <Tree
              aria-label={fbs(
                "Categories",
                "Product form: accessible name of the tree of checkboxes that picks the categories the product is listed in",
              )}
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
        className={cn(
          "type-body-md flex cursor-pointer items-center gap-2 py-2 pl-[calc(0.5rem+var(--tree-level)*1.5rem)] text-base-primary outline-none select-none group-hover/row:bg-state-hover focus-visible:focus-ring focus-visible:-outline-offset-2",
          expanded === undefined ? "pr-10" : "pr-28",
        )}
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
        <span className="flex shrink-0 text-base-tertiary">
          <Icon icon={expanded ? FolderOpenLine : FolderLine} size={4} />
        </span>
        <span className={cn("min-w-0 flex-1 truncate", !category.visible && "text-base-tertiary")}>
          {category.name}
        </span>
      </TreeItem>
      {/* Clicks on the count pass through to the row underneath. */}
      <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center">
        {expanded !== undefined && (
          <>
            <span className="type-body-md pr-1 text-base-tertiary tabular-nums">
              ({category.children.length})
            </span>
            {/* Out of the tab order: the row itself opens and closes with the arrow keys. */}
            <IconButton
              variant="tertiary"
              size="sm"
              aria-label={
                expanded
                  ? fbs(
                      ["Close ", fbs.param("categoryName", category.name)],
                      "Product form, categories tree: accessible name of the button that collapses a category to hide its subcategories, e.g. 'Close Shirts'",
                    )
                  : fbs(
                      ["Open ", fbs.param("categoryName", category.name)],
                      "Product form, categories tree: accessible name of the button that expands a category to show its subcategories, e.g. 'Open Shirts'",
                    )
              }
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
        {category.url && (
          <CategoryMenu url={category.url} name={category.name} tabbable={tabbable} />
        )}
      </div>
    </div>
  );
}

/**
 * A category's actions, left out while the categories are turned off. Only the tree's tab stop
 * row puts its button in the tab order.
 */
function CategoryMenu({ url, name, tabbable }: { url: string; name: string; tabbable: boolean }) {
  return (
    <Menu.Root>
      <Menu.Trigger
        tabIndex={tabbable ? 0 : -1}
        render={
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={fbs(
              ["Actions for ", fbs.param("categoryName", name)],
              "Product form, categories tree: accessible name of the button that opens a category's menu of actions, e.g. 'Actions for Shirts'",
            )}
            DANGEROUS_className="pointer-events-auto focus-visible:-outline-offset-2"
          >
            <Icon icon={More2Fill} />
          </IconButton>
        }
      />
      <Menu.Popup>
        <HotkeysProvider scope="category-actions-menu" exclusive={overlayExclusive}>
          <Menu.Item icon={EyeLine} render={<Link href={url} prefetch="hover" />}>
            <fbt desc="Product form, categories tree: menu item of a category that opens the category's page">
              View
            </fbt>
          </Menu.Item>
        </HotkeysProvider>
      </Menu.Popup>
    </Menu.Root>
  );
}
