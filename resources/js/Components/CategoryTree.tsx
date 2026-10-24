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
import { defaultPreset, Feedback, PointerActivationConstraints } from "@dnd-kit/dom";
import {
  type DragEndEvent,
  DragDropProvider,
  type DragOverEvent,
  PointerSensor,
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
import type { TableAction } from "../types";
import ConfirmActionDialog from "./ConfirmActionDialog";
import Tree, { TreeItem } from "./Tree";
import { cn } from "cn";

type Category = Larasell.Admin.Data.ViewModel.CategoryData;

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

/** A copy of the tree with the category `id` moved to the end of `parentId`'s children, or of the roots. */
function withMoved(categories: Category[], id: number, parentId: number | null): Category[] {
  let moved: Category | undefined;
  const without = (nodes: Category[]): Category[] =>
    nodes.flatMap((category) => {
      if (category.id === id) {
        moved = category;
        return [];
      }
      return [{ ...category, children: without(category.children) }];
    });
  const into = (nodes: Category[]): Category[] =>
    nodes.map((category) => ({
      ...category,
      children: [...into(category.children), ...(category.id === parentId ? [moved!] : [])],
    }));

  const rest = without(categories);
  if (moved === undefined) return categories;
  return parentId === null ? [...rest, moved] : into(rest);
}

// The root drop zone, below the categories' rows, which it loses to when they overlap.
const ROOT = "root";

// A mouse drags once it moved a little, so a click still follows the link. Touch waits
// for a long press, so a swipe still scrolls.
const sensors = [
  PointerSensor.configure({
    activationConstraints: (event) =>
      event.pointerType === "mouse"
        ? [new PointerActivationConstraints.Distance({ value: 5 })]
        : [new PointerActivationConstraints.Delay({ value: 250, tolerance: 5 })],
  }),
];

// A dropped row jumps straight to its new place instead of animating back to its old one.
const plugins = (defaults: typeof defaultPreset.plugins) =>
  defaults.map((plugin) =>
    plugin === Feedback ? Feedback.configure({ dropAnimation: null }) : plugin,
  );

/**
 * The store's categories as a tree of links to their pages. It starts closed, except
 * for the current category and the path to it. Clicking a category only follows its
 * link; its chevron opens and closes it. Hidden categories are greyed out.
 *
 * Dragging a category onto another moves it inside that one, and onto the space below
 * the tree moves it to the root. Hovering a closed category for a moment while dragging opens it.
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

  // Not into its own parent, nor into itself or a category below it.
  const canMove = (id: number, parentId: number | null) => {
    if (parents.get(id) === parentId) return false;
    for (let at = parentId; at !== null; at = parents.get(at) ?? null) {
      if (at === id) return false;
    }
    return true;
  };

  const move = (id: number, parentId: number | null) => {
    const category = find(categories, id);
    if (category === undefined || !canMove(id, parentId)) return;
    if (parentId !== null) setExpandedIds((current) => new Set(current).add(parentId));
    // Shows the move at once; Inertia rolls it back if the request fails.
    router.post(
      category.moveUrl,
      { parent_id: parentId },
      {
        preserveScroll: true,
        preserveState: true,
        optimistic: (props) => ({
          categories: withMoved(props.categories as Category[], id, parentId),
        }),
      },
    );
  };

  // Opens a closed category with children once it has been dragged over for a moment.
  const openAfterHovering = (event: DragOverEvent) => {
    window.clearTimeout(expandTimer.current);
    const id = event.operation.target?.id;
    if (typeof id !== "number" || expandedIds.has(id)) return;
    if (!find(categories, id)?.children.length) return;
    expandTimer.current = window.setTimeout(
      () => setExpandedIds((current) => new Set(current).add(id)),
      200,
    );
  };

  const drop = (event: DragEndEvent) => {
    window.clearTimeout(expandTimer.current);
    const { source, target } = event.operation;
    if (event.canceled || source === null || target === null) return;
    move(source.id as number, target.id === ROOT ? null : (target.id as number));
  };

  return (
    <MotionConfig reducedMotion="user">
      <DragDropProvider
        sensors={sensors}
        plugins={plugins}
        onDragOver={openAfterHovering}
        onDragEnd={drop}
      >
        <RootDropZone accepts={(id) => canMove(id, null)}>
          <Tree
            aria-labelledby={labelledBy}
            items={categories}
            getLabel={(category) => category.name}
            expandedIds={expandedIds}
            onExpandedIdsChange={setExpandedIds}
            toggleOnClick={false}
            // Not selectable: this only makes the current category the tree's tab stop.
            selectedId={currentId}
            renderItem={(category, { expanded, tabbable }) => (
              <CategoryRow
                category={category}
                current={category.id === currentId}
                expanded={expanded}
                tabbable={tabbable}
                accepts={(id) => canMove(id, category.id)}
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
 * follows the link or drags.
 */
function CategoryRow({
  category,
  current,
  expanded,
  tabbable,
  accepts,
  onToggle,
}: {
  category: Category;
  current: boolean;
  expanded: boolean | undefined;
  tabbable: boolean;
  accepts: (id: number) => boolean;
  onToggle: () => void;
}) {
  const draggable = useDraggable({ id: category.id });
  const droppable = useDroppable({
    id: category.id,
    accept: (source) => accepts(source.id as number),
  });

  return (
    <div
      ref={droppable.ref}
      data-drop-target={droppable.isDropTarget || undefined}
      // As wide as the panel, unless a deeply nested row needs more to keep its name's minimum
      // width; that row then pushes its controls out of line, and the panel scrolls sideways.
      className="group/row relative w-fit min-w-full data-drop-target:outline-1 data-drop-target:-outline-offset-1 data-drop-target:outline-primary-500"
    >
      <TreeItem
        // A native link drag would take over the pointer before ours starts. While dragging,
        // dnd-kit leaves a hidden copy of the link (data-dnd-placeholder) in its old place,
        // which shows as an empty striped slot. It copies the link's
        // attributes, data-dragging too, hence the opacity reset.
        render={<Link ref={draggable.ref} href={category.url} prefetch="hover" draggable={false} />}
        aria-current={current ? "page" : undefined}
        data-dragging={draggable.isDragging || undefined}
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
        <span className="flex shrink-0 text-base text-mauve-500 dark:text-mauve-400">
          <Icon icon={expanded ? FolderOpenLine : FolderLine} />
        </span>
        <span
          // Zero width, so only the minimum, not the whole name, widens the row.
          className={`w-0 min-w-32 flex-1 truncate ${category.visible ? "" : "text-mauve-500 dark:text-mauve-400"}`}
        >
          {category.name}
        </span>
      </TreeItem>
      <div className="pointer-events-none absolute top-0 right-0 flex items-center">
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
        <HotkeysProvider scope="category-actions-menu" exclusive>
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
  const deletion: TableAction | null = deleting && {
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
