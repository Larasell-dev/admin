import { Link, router } from "@inertiajs/react";
import { Icon, Menu } from "@larasell-dev/ui";
import {
  DeleteBinLine,
  EyeLine,
  EyeOffLine,
  FolderAddLine,
  FolderLine,
  FolderOpenLine,
  More2Fill,
} from "@larasell-dev/ui/icons";
import { defaultPreset, Feedback, PointerActivationConstraints } from "@dnd-kit/dom";
import { DragDropProvider, PointerSensor, useDraggable, useDroppable } from "@dnd-kit/react";
import { type ReactNode, useMemo, useRef, useState } from "react";
import type { TableAction } from "../types";
import ConfirmActionDialog from "./ConfirmActionDialog";
import Tree, { TreeItem } from "./Tree";

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
 * for the path to the current category. Hidden categories are greyed out.
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
  const [expandedIds, setExpandedIds] = useState(
    () => new Set(currentId === null ? [] : ancestorIds(categories, currentId)),
  );
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

  return (
    <DragDropProvider
      sensors={sensors}
      plugins={plugins}
      onDragOver={(event) => {
        window.clearTimeout(expandTimer.current);
        const id = event.operation.target?.id;
        if (
          typeof id !== "number" ||
          expandedIds.has(id) ||
          !find(categories, id)?.children.length
        ) {
          return;
        }
        expandTimer.current = window.setTimeout(
          () => setExpandedIds((current) => new Set(current).add(id)),
          200,
        );
      }}
      onDragEnd={(event) => {
        window.clearTimeout(expandTimer.current);
        const { source, target } = event.operation;
        if (event.canceled || source === null || target === null) return;
        move(source.id as number, target.id === ROOT ? null : (target.id as number));
      }}
    >
      <RootDropZone accepts={(id) => canMove(id, null)}>
        <Tree
          aria-labelledby={labelledBy}
          items={categories}
          getLabel={(category) => category.name}
          expandedIds={expandedIds}
          onExpandedIdsChange={setExpandedIds}
          // Not selectable: this only makes the current category the tree's tab stop.
          selectedId={currentId}
          renderItem={(category, { expanded, tabbable }) => (
            <CategoryRow
              category={category}
              current={category.id === currentId}
              expanded={expanded}
              tabbable={tabbable}
              accepts={(id) => canMove(id, category.id)}
            />
          )}
        />
      </RootDropZone>
    </DragDropProvider>
  );
}

function find(categories: Category[], id: number): Category | undefined {
  for (const category of categories) {
    if (category.id === id) return category;
    const below = find(category.children, id);
    if (below) return below;
  }
  return undefined;
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
 * The menu sits next to the link, not inside it, so clicking it never follows the link or drags.
 */
function CategoryRow({
  category,
  current,
  expanded,
  tabbable,
  accepts,
}: {
  category: Category;
  current: boolean;
  expanded: boolean | undefined;
  tabbable: boolean;
  accepts: (id: number) => boolean;
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
      className="group/row relative data-drop-target:outline-1 data-drop-target:-outline-offset-1 data-drop-target:outline-primary-500"
    >
      <TreeItem
        // A native link drag would take over the pointer before ours starts. While dragging,
        // dnd-kit leaves a hidden copy of the link (data-dnd-placeholder) in its old place,
        // which shows as an empty striped slot. It copies the link's
        // attributes, data-dragging too, hence the opacity reset.
        render={<Link ref={draggable.ref} href={category.url} prefetch="hover" draggable={false} />}
        aria-current={current ? "page" : undefined}
        data-dragging={draggable.isDragging || undefined}
        className="type-body-md flex cursor-pointer items-center gap-1.5 py-2 pr-10 pl-[calc(1.5rem+var(--tree-level)*1.25rem)] text-mauve-950 outline-none select-none group-hover/row:bg-mauve-50 focus-visible:focus-ring focus-visible:-outline-offset-2 aria-[current=page]:bg-mauve-100 dark:text-mauve-50 data-dragging:bg-white dark:data-dragging:bg-mauve-950 group-data-drop-target/row:bg-primary-50! dark:group-data-drop-target/row:bg-primary-950/40! data-dnd-placeholder:visible! data-dragging:opacity-60 data-dnd-placeholder:opacity-100! data-dnd-placeholder:bg-mauve-50! data-dnd-placeholder:bg-[image:repeating-linear-gradient(-45deg,color-mix(in_oklab,var(--color-mauve-200)_60%,transparent)_0_1px,transparent_1px_6px)] data-dnd-placeholder:outline-1 data-dnd-placeholder:-outline-offset-1 data-dnd-placeholder:outline-mauve-300 data-dnd-placeholder:outline-dashed data-dnd-placeholder:*:invisible dark:data-dnd-placeholder:bg-mauve-900! dark:data-dnd-placeholder:bg-[image:repeating-linear-gradient(-45deg,var(--color-mauve-800)_0_1px,transparent_1px_6px)] dark:data-dnd-placeholder:outline-mauve-600 dark:group-hover/row:bg-mauve-900 dark:aria-[current=page]:bg-mauve-900"
      >
        <span className="flex shrink-0 text-base text-mauve-500 dark:text-mauve-400">
          <Icon icon={expanded ? FolderOpenLine : FolderLine} />
        </span>
        <span
          className={`truncate ${category.visible ? "" : "text-mauve-500 dark:text-mauve-400"}`}
        >
          {category.name}
        </span>
      </TreeItem>
      <CategoryMenu category={category} tabbable={tabbable} />
    </div>
  );
}

/** A copy of the tree with the category `id` set to `visible`. */
function withVisibility(categories: Category[], id: number, visible: boolean): Category[] {
  return categories.map((category) => ({
    ...category,
    visible: category.id === id ? visible : category.visible,
    children: withVisibility(category.children, id, visible),
  }));
}

/**
 * A category's actions. The button shows while its row is hovered or has keyboard focus, or the menu
 * is open. It is transparent rather than invisible otherwise, so the menu can always return
 * focus to it, and only the tree's tab stop row puts it in the tab order.
 */
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

  const [deleting, setDeleting] = useState(false);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  // The admin types the name to confirm; the server checks it again.
  const deletion: TableAction = {
    name: "delete",
    label: "Delete",
    method: "post",
    url: category.deleteUrl,
    confirm: {
      title: "Delete category",
      message: `This also deletes all categories inside "${category.name}". Their products stay in the store.`,
      confirmButton: "Delete",
      cancelButton: "Cancel",
      confirmationText: category.name,
    },
    destructive: true,
    disabled: false,
    icon: null,
  };

  const destroy = (confirmation: string) =>
    router.delete(category.deleteUrl, {
      data: { confirmation },
      preserveScroll: true,
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => setDeleting(false),
      onError: (errors) => setError(errors.confirmation),
    });

  return (
    <>
      <Menu.Root>
        <Menu.Trigger
          aria-label={`Actions for ${category.name}`}
          tabIndex={tabbable ? 0 : -1}
          className="absolute top-0 right-0 inline-flex size-9 cursor-pointer items-center justify-center text-mauve-950 opacity-0 select-none group-has-[:focus-visible]/row:opacity-100 group-hover/row:opacity-100 hover:bg-mauve-100 focus-visible:focus-ring focus-visible:-outline-offset-2 data-popup-open:bg-mauve-100 data-popup-open:opacity-100 dark:text-mauve-100 dark:hover:bg-mauve-800 dark:data-popup-open:bg-mauve-800"
        >
          <Icon icon={More2Fill} />
        </Menu.Trigger>
        <Menu.Popup>
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
            onClick={() => {
              setError(undefined);
              setDeleting(true);
            }}
          >
            Delete
          </Menu.Item>
        </Menu.Popup>
      </Menu.Root>
      <ConfirmActionDialog
        action={deleting ? deletion : null}
        processing={processing}
        error={error}
        onConfirm={destroy}
        onCancel={() => setDeleting(false)}
      />
    </>
  );
}
