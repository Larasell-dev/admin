import { Link, router } from "@inertiajs/react";
import { Icon, IconButton, Menu } from "@larasell-dev/ui";
import {
  ArrowRightSLine,
  DeleteBinLine,
  EyeLine,
  EyeOffLine,
  FolderAddLine,
  FolderLine,
  FolderOpenLine,
  More2Fill,
} from "@larasell-dev/ui/icons";
import {
  type DragEndEvent,
  DragDropProvider,
  type DragMoveEvent,
  type DragOverEvent,
  useDraggable,
  useDroppable,
} from "@dnd-kit/react";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { MotionConfig, motion } from "motion/react";
import {
  createContext,
  type ReactNode,
  useCallback,
  useContext,
  useMemo,
  useRef,
  useState,
} from "react";
import type { TableRowAction } from "../../types";
import { plugins, sensors } from "../../utils/dragAndDrop";
import { overlayExclusive } from "../../utils/hotkeys";
import ConfirmActionDialog from "../ConfirmActionDialog";
import Tree, { TreeItem } from "../Tree";
import { cn } from "cn";

type Category = Larasell.Admin.Category.Data.CategoryData;

/** The ids of the categories above the one with `id`, or [] when it is a root or missing. */
function ancestorIds(categories: Category[], id: number): number[] {
  for (const category of categories) {
    if (category.id === id) return [];
    const below = ancestorIds(category.children, id);
    if (below.length > 0 || category.children.some((child) => child.id === id)) {
      return [category.id, ...below];
    }
  }
  return [];
}

/** Each category's parent id, null for root categories. */
function parentIds(
  categories: Category[],
  parentId: number | null = null,
  into = new Map<number, number | null>(),
) {
  for (const category of categories) {
    into.set(category.id, parentId);
    parentIds(category.children, category.id, into);
  }
  return into;
}

/**
 * A copy of the tree with the category `id` moved among `parentId`'s children, or the roots, to
 * `index` counted without itself.
 */
function withMoved(
  categories: Category[],
  id: number,
  parentId: number | null,
  index: number,
): Category[] {
  let moved: Category | undefined;
  const without = (nodes: Category[]): Category[] =>
    nodes.flatMap((category) => {
      if (category.id === id) {
        moved = category;
        return [];
      }
      return [{ ...category, children: without(category.children) }];
    });
  const insert = (nodes: Category[]) => [...nodes.slice(0, index), moved!, ...nodes.slice(index)];
  const into = (nodes: Category[]): Category[] =>
    nodes.map((category) => {
      const children = into(category.children);
      return { ...category, children: category.id === parentId ? insert(children) : children };
    });

  const rest = without(categories);
  if (moved === undefined) return categories;
  return parentId === null ? insert(rest) : into(rest);
}

// The root drop zone, below the categories' rows, which it loses to when they overlap.
const ROOT = "root";

/**
 * Where a category dropped onto a row goes: before or after the row, inside it as its last
 * child, or as its first child, which is what dropping after an open category with children means.
 */
type DropZone = "before" | "after" | "inside" | "first-child";
type Placement = { id: number; zone: DropZone };

/**
 * The store's categories as a tree of links to their pages. It starts closed, except
 * for the current category and the path to it. Clicking a category only follows its
 * link; its chevron opens and closes it. Hidden categories are greyed out.
 *
 * Dragging a category onto the top or bottom edge of another puts it before or after that
 * one, which also orders the categories; onto the middle moves it inside that one, and onto
 * the space below the tree moves it to the root. Hovering a closed category for a moment
 * while dragging opens it.
 */
export default function CategoryTree({
  categories,
  labelledBy,
  currentId = null,
}: {
  categories: Category[];
  labelledBy: string;
  currentId?: number | null;
}) {
  const pathIds = (ids: Iterable<number>) =>
    new Set([
      ...ids,
      ...(currentId === null ? [] : [...ancestorIds(categories, currentId), currentId]),
    ]);
  const [expandedIds, setExpandedIds] = useState(() => pathIds([]));

  // The layout stays mounted across visits, so every page load, a new current category
  // or fresh props for this one, opens the current category and the path to it again. Closing it
  // in between still works, as that changes neither.
  const [loaded, setLoaded] = useState({ categories, currentId });
  if (loaded.categories !== categories || loaded.currentId !== currentId) {
    setLoaded({ categories, currentId });
    setExpandedIds(pathIds(expandedIds));
  }
  const parents = useMemo(() => parentIds(categories), [categories]);
  const expandTimer = useRef<number>(undefined);
  // Where the dragged category would go, shown on the row it is over.
  const [placement, setPlacement] = useState<Placement | null>(null);

  /** Whether `at` is the category `id` or one below it. */
  const isWithin = (at: number | null, id: number) => {
    for (; at !== null; at = parents.get(at) ?? null) {
      if (at === id) return true;
    }
    return false;
  };

  // Not into its own parent, nor into itself or a category below it.
  const canMoveInto = (id: number, parentId: number | null) =>
    parents.get(id) !== parentId && !isWithin(parentId, id);

  const siblingsOf = (parentId: number | null) =>
    parentId === null ? categories : (find(categories, parentId)?.children ?? []);

  /** Moves the category `id` among `parentId`'s children to `index`, counted without itself, or last. */
  const move = (id: number, parentId: number | null, index?: number) => {
    const category = find(categories, id);
    if (category === undefined || isWithin(parentId, id)) return;
    const siblings = siblingsOf(parentId);
    const others = siblings.filter((sibling) => sibling.id !== id);
    const position = Math.min(index ?? others.length, others.length);
    if (siblings.findIndex((sibling) => sibling.id === id) === position) return;
    if (parentId !== null) setExpandedIds((current) => new Set(current).add(parentId));
    // Shows the move at once; Inertia rolls it back if the request fails.
    router.post(
      category.moveUrl,
      { parent_id: parentId, position },
      {
        preserveScroll: true,
        preserveState: true,
        optimistic: (props) => ({
          categories: withMoved(props.categories as Category[], id, parentId, position),
        }),
      },
    );
  };

  /** Moves the category `id` to the placement it was dropped on. */
  const place = (id: number, { id: rowId, zone }: Placement) => {
    if (zone === "inside") return move(id, rowId);
    if (zone === "first-child") return move(id, rowId, 0);
    const parentId = parents.get(rowId) ?? null;
    const others = siblingsOf(parentId).filter((sibling) => sibling.id !== id);
    const index = others.findIndex((sibling) => sibling.id === rowId);
    move(id, parentId, zone === "before" ? index : index + 1);
  };

  /**
   * The zone of the row `rowId` the pointer is in: its top and bottom quarter put the dragged
   * category before or after it, the middle inside it. Without the middle, as for the dragged
   * category's own parent, the row splits in half.
   */
  const zoneAt = (id: number, rowId: number, element: Element, y: number): DropZone => {
    const { top, height } = element.getBoundingClientRect();
    const fraction = (y - top) / height;
    const edge = canMoveInto(id, rowId) ? 0.25 : 0.5;
    if (fraction < edge) return "before";
    if (fraction <= 1 - edge) return "inside";
    return expandedIds.has(rowId) && find(categories, rowId)?.children.length
      ? "first-child"
      : "after";
  };

  /** The row the dragged category is over and the zone of that row the pointer is in. */
  const placementOf = ({
    operation,
  }: DragOverEvent | DragMoveEvent | DragEndEvent): Placement | null => {
    const { source, target, position } = operation;
    const rowId = target?.id;
    if (source === null || typeof rowId !== "number" || !target?.element) return null;
    return {
      id: rowId,
      zone: zoneAt(source.id as number, rowId, target.element, position.current.y),
    };
  };

  // Follows the pointer over the rows, and opens a closed category with children once it has
  // been dragged over its middle for a moment.
  const track = (event: DragOverEvent | DragMoveEvent) => {
    const next = placementOf(event);
    if (next?.id === placement?.id && next?.zone === placement?.zone) return;
    setPlacement(next);

    window.clearTimeout(expandTimer.current);
    if (next?.zone !== "inside" || expandedIds.has(next.id)) return;
    if (!find(categories, next.id)?.children.length) return;
    expandTimer.current = window.setTimeout(
      () => setExpandedIds((current) => new Set(current).add(next.id)),
      200,
    );
  };

  const drop = (event: DragEndEvent) => {
    window.clearTimeout(expandTimer.current);
    setPlacement(null);
    const { source, target } = event.operation;
    if (event.canceled || source === null || target === null) return;
    if (target.id === ROOT) return move(source.id as number, null);
    // From the drop itself, as the last pointer move may not have rendered its placement yet.
    const dropped = placementOf(event);
    if (dropped !== null) place(source.id as number, dropped);
  };

  return (
    <MotionConfig reducedMotion="user">
      <DragDropProvider
        sensors={sensors}
        plugins={plugins}
        onDragOver={track}
        onDragMove={track}
        onDragEnd={drop}
      >
        <RootDropZone accepts={(id) => canMoveInto(id, null)}>
          <Tree
            aria-labelledby={labelledBy}
            items={categories}
            getLabel={(category) => category.name}
            expandedIds={expandedIds}
            onExpandedIdsChange={setExpandedIds}
            toggleOnClick={false}
            // Not selectable: this only makes the current category the tree's tab stop.
            selectedId={currentId}
            renderItem={(category, { level, expanded, tabbable }) => (
              <CategoryRow
                category={category}
                current={category.id === currentId}
                level={level}
                expanded={expanded}
                tabbable={tabbable}
                // Every category can go next to this one, unless it is this one or above it.
                accepts={(id) => !isWithin(category.id, id)}
                dropZone={placement?.id === category.id ? placement.zone : undefined}
                onToggle={() =>
                  setExpandedIds((current) => {
                    const next = new Set(current);
                    if (!next.delete(category.id)) next.add(category.id);
                    return next;
                  })
                }
              />
            )}
          />
        </RootDropZone>
      </DragDropProvider>
    </MotionConfig>
  );
}

/** The category `id` anywhere in the tree, searching it level by level. */
function find(categories: Category[], id: number): Category | undefined {
  const match = categories.find((category) => category.id === id);
  if (match) return match;
  if (categories.length === 0) return undefined;
  return find(
    categories.flatMap((category) => category.children),
    id,
  );
}

/** Fills the rest of the sidebar, so there is room below the tree to drop a category at the root. */
function RootDropZone({
  accepts,
  children,
}: {
  accepts: (id: number) => boolean;
  children: ReactNode;
}) {
  const { ref, isDropTarget } = useDroppable({
    id: ROOT,
    accept: (source) => accepts(source.id as number),
    collisionPriority: 0,
  });

  return (
    <div
      ref={ref}
      data-drop-target={isDropTarget || undefined}
      className="min-h-full data-drop-target:bg-mauve-50 dark:data-drop-target:bg-mauve-900"
    >
      {children}
    </div>
  );
}

/**
 * A category's row: its link is what drags, and the whole row is where others drop.
 * The chevron and the menu sit next to the link, not inside it, so clicking them never
 * follows the link or drags. Dropping inside the row outlines it; before or after it, or
 * as its first child, draws a line where the category will go.
 */
function CategoryRow({
  category,
  current,
  level,
  expanded,
  tabbable,
  accepts,
  dropZone,
  onToggle,
}: {
  category: Category;
  current: boolean;
  level: number;
  expanded: boolean | undefined;
  tabbable: boolean;
  accepts: (id: number) => boolean;
  dropZone: DropZone | undefined;
  onToggle: () => void;
}) {
  const { ref: dragRef, isDragging } = useDraggable({ id: category.id });
  const { ref: dropRef, isDropTarget } = useDroppable({
    id: category.id,
    accept: (source) => accepts(source.id as number),
  });

  return (
    <div
      ref={dropRef}
      data-drop-target={(isDropTarget && dropZone === "inside") || undefined}
      // As wide as the panel, unless a deeply nested row needs more to keep its name's minimum
      // width; that row then pushes its controls out of line, and the panel scrolls sideways.
      className="group/row relative w-fit min-w-full data-drop-target:outline-1 data-drop-target:-outline-offset-1 data-drop-target:outline-primary-500"
    >
      <TreeItem
        // A native link drag would take over the pointer before ours starts. While dragging,
        // dnd-kit leaves a hidden copy of the link (data-dnd-placeholder) in its old place,
        // which shows as an empty striped slot. It copies the link's
        // attributes, data-dragging too, hence the opacity reset.
        render={<Link ref={dragRef} href={category.url} prefetch="hover" draggable={false} />}
        aria-current={current ? "page" : undefined}
        data-dragging={isDragging || undefined}
        className={cn(
          // Row
          "type-body-md flex cursor-pointer items-center gap-1.5 py-2 pl-[calc(1.5rem+var(--tree-level)*1.25rem)] text-mauve-950 outline-none select-none dark:text-mauve-50",
          // Room for the controls on the right
          expanded === undefined ? "pr-10" : "pr-28",
          "group-hover/row:bg-mauve-50 dark:group-hover/row:bg-mauve-900",
          "focus-visible:focus-ring focus-visible:-outline-offset-2",
          "aria-[current=page]:bg-mauve-100 dark:aria-[current=page]:bg-mauve-900",
          // The row being dragged
          "data-dragging:bg-white data-dragging:opacity-60 dark:data-dragging:bg-mauve-950",
          // The row dropped onto
          "group-data-drop-target/row:bg-primary-50! dark:group-data-drop-target/row:bg-primary-950/40!",
          // The striped placeholder in the dragged row's old place
          "data-dnd-placeholder:visible! data-dnd-placeholder:opacity-100! data-dnd-placeholder:*:invisible",
          "data-dnd-placeholder:outline-1 data-dnd-placeholder:-outline-offset-1 data-dnd-placeholder:outline-dashed data-dnd-placeholder:outline-mauve-300 dark:data-dnd-placeholder:outline-mauve-600",
          "data-dnd-placeholder:bg-mauve-50! data-dnd-placeholder:bg-[image:repeating-linear-gradient(-45deg,color-mix(in_oklab,var(--color-mauve-200)_60%,transparent)_0_1px,transparent_1px_6px)]",
          "dark:data-dnd-placeholder:bg-mauve-900! dark:data-dnd-placeholder:bg-[image:repeating-linear-gradient(-45deg,var(--color-mauve-800)_0_1px,transparent_1px_6px)]",
        )}
      >
        <span className="flex shrink-0 text-mauve-500 dark:text-mauve-400">
          <Icon icon={expanded ? FolderOpenLine : FolderLine} size={4} />
        </span>
        <span
          // Zero width, so only the minimum, not the whole name, widens the row.
          className={cn(
            "w-0 min-w-32 flex-1 truncate",
            !category.visible && "text-mauve-500 dark:text-mauve-400",
          )}
        >
          {category.name}
        </span>
      </TreeItem>
      {/* Only the link moves with the pointer, so the controls would stay behind in the empty slot. */}
      <div
        className={cn(
          "pointer-events-none absolute top-0 right-0 flex items-center",
          isDragging && "invisible",
        )}
      >
        {expanded !== undefined && (
          <>
            <span className="type-body-md pointer-events-none pr-1 text-mauve-500 tabular-nums dark:text-mauve-400">
              ({category.children.length})
            </span>
            {/* Out of the tab order: the row itself opens and closes with the arrow keys. */}
            <IconButton
              variant="tertiary"
              size="sm"
              aria-label={`${expanded ? "Close" : "Open"} ${category.name}`}
              tabIndex={-1}
              onClick={onToggle}
              DANGEROUS_className={rowButtonClassName}
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
      {isDropTarget && dropZone !== undefined && dropZone !== "inside" && (
        <span
          aria-hidden
          className={cn(
            "pointer-events-none absolute right-0 h-0.5 -translate-y-1/2 bg-primary-500",
            dropZone === "before" ? "top-0" : "top-full",
          )}
          // Starts at the dragged category's new level, under the icon of its row.
          style={{
            left: `calc(1.5rem + ${level + (dropZone === "first-child" ? 1 : 0)} * 1.25rem)`,
          }}
        />
      )}
    </div>
  );
}

/** Lets the icon buttons at the end of a row take clicks and keeps their focus ring inside it. */
const rowButtonClassName = "pointer-events-auto focus-visible:-outline-offset-2";

/** A copy of the tree with the category `id` set to `visible`. */
function withVisibility(categories: Category[], id: number, visible: boolean): Category[] {
  return categories.map((category) => ({
    ...category,
    visible: category.id === id ? visible : category.visible,
    children: withVisibility(category.children, id, visible),
  }));
}

/** A category's actions. Only the tree's tab stop row puts its button in the tab order. */
function CategoryMenu({ category, tabbable }: { category: Category; tabbable: boolean }) {
  // Shows the new visibility at once; Inertia rolls it back if the request fails.
  const post = (url: string, visible: boolean) =>
    router.post(
      url,
      {},
      {
        preserveScroll: true,
        preserveState: true,
        optimistic: (props) => ({
          categories: withVisibility(props.categories as Category[], category.id, visible),
        }),
      },
    );

  const askToDelete = useContext(DeleteContext);

  return (
    <Menu.Root>
      <Menu.Trigger
        tabIndex={tabbable ? 0 : -1}
        render={
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={`Actions for ${category.name}`}
            DANGEROUS_className={rowButtonClassName}
          >
            <Icon icon={More2Fill} />
          </IconButton>
        }
      />
      <Menu.Popup>
        <HotkeysProvider scope="category-actions-menu" exclusive={overlayExclusive}>
          <Menu.Item
            icon={FolderAddLine}
            render={<Link href={category.createChildUrl} prefetch="hover" />}
          >
            Add subcategory
          </Menu.Item>
          {category.visible ? (
            <Menu.Item icon={EyeOffLine} onClick={() => post(category.hideUrl, false)}>
              Hide
            </Menu.Item>
          ) : (
            <Menu.Item icon={EyeLine} onClick={() => post(category.unhideUrl, true)}>
              Make visible
            </Menu.Item>
          )}
          <Menu.Item
            variant="destructive"
            icon={DeleteBinLine}
            onClick={() => askToDelete(category)}
          >
            Delete
          </Menu.Item>
        </HotkeysProvider>
      </Menu.Popup>
    </Menu.Root>
  );
}

const DeleteContext = createContext<(category: Category) => void>(() => {});

/**
 * Lets the category trees inside it delete a category after the admin confirms it. The
 * confirmation lives here rather than in the deleted row, so it can still animate out once the
 * row, or every tree when it was the last category, is gone.
 */
export function CategoryDeletion({ children }: { children: ReactNode }) {
  const [deleting, setDeleting] = useState<Category | null>(null);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  // The admin types the name to confirm; the server checks it again.
  const deletion: TableRowAction | null = deleting && {
    name: "delete",
    label: "Delete",
    method: "post",
    url: deleting.deleteUrl,
    confirm: {
      title: "Delete category",
      message: `This also deletes all categories inside "${deleting.name}". Their products stay in the store.`,
      confirmButton: "Delete",
      cancelButton: "Cancel",
      confirmationText: deleting.name,
    },
    destructive: true,
    disabled: false,
    icon: null,
  };

  const destroy = (confirmation: string) =>
    router.delete(deleting!.deleteUrl, {
      data: { confirmation },
      preserveScroll: true,
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => setDeleting(null),
      onError: (errors) => setError(errors.confirmation),
    });

  const askToDelete = useCallback((category: Category) => {
    setError(undefined);
    setDeleting(category);
  }, []);

  return (
    <DeleteContext value={askToDelete}>
      {children}
      <ConfirmActionDialog
        action={deletion}
        processing={processing}
        error={error}
        onConfirm={destroy}
        onCancel={() => setDeleting(null)}
      />
    </DeleteContext>
  );
}
