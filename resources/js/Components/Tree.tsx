import { mergeProps } from "@base-ui/react/merge-props";
import { useRender } from "@base-ui/react/use-render";
import {
  createContext,
  type KeyboardEvent,
  type ReactNode,
  type Ref,
  useContext,
  useId,
  useMemo,
  useRef,
  useState,
} from "react";

export type TreeItemState = {
  /** 0 for root nodes. */
  level: number;
  /** Whether a parent node is open; undefined for end nodes. */
  expanded: boolean | undefined;
  selected: boolean;
  /** Whether this row is the tree's single tab stop: the focused, selected or first node. */
  tabbable: boolean;
};

type Label = { "aria-label": string } | { "aria-labelledby": string };

/** `getId` and `getChildren` default to a node's `id` and `children`, so they're only required without them. */
type Accessors<T, TId> = (T extends { id: TId }
  ? { getId?: (item: T) => TId }
  : { getId: (item: T) => TId }) &
  (T extends { children: readonly T[] }
    ? { getChildren?: (item: T) => readonly T[] }
    : { getChildren: (item: T) => readonly T[] });

export type TreeProps<T, TId extends string | number> = Label &
  Accessors<T, TId> & {
    items: readonly T[];
    /** Plain-text name of a node, used for type-ahead. */
    getLabel: (item: T) => string;
    /** Renders a node's row: return a `<TreeItem>`, whose content becomes the node's accessible name. */
    renderItem: (item: T, state: TreeItemState) => ReactNode;
    // NoInfer: TId comes from getId, or from the nodes' `id` when getId is left out.
    defaultExpandedIds?: Iterable<NoInfer<TId>>;
    /** Controls which nodes are open; pair it with `onExpandedIdsChange`. */
    expandedIds?: ReadonlySet<NoInfer<TId>>;
    onExpandedIdsChange?: (ids: Set<NoInfer<TId>>) => void;
    /** Whether clicking a parent node opens or closes it. Defaults to true. */
    toggleOnClick?: boolean;
    selectedId?: NoInfer<TId> | null;
    /** Makes nodes selectable: Enter, Space and click select the node. */
    onSelectedIdChange?: (id: NoInfer<TId>) => void;
    className?: string;
    groupClassName?: string;
  };

type Entry<T, TId> = {
  item: T;
  id: TId;
  parentId: TId | null;
  hasChildren: boolean;
};

/**
 * A headless single-select tree view following the WAI-ARIA APG tree view pattern:
 * https://www.w3.org/WAI/ARIA/apg/patterns/treeview/
 *
 * It is one tab stop (roving tabindex) and supports Up/Down, Right/Left to open,
 * close and move between parents and children, Home/End, `*` to open all
 * siblings, and single-character type-ahead. Children of closed nodes are not rendered.
 *
 * Each node is an `<li role="none">` holding the treeitem row (a `<TreeItem>` from
 * `renderItem`) and, when open, its child group, which the row owns through `aria-owns`.
 * The APG navigation tree view uses this structure so a row can be a link.
 */
// Module-level, so the defaults keep their identity across renders.
const getOwnId = (item: unknown) => (item as { id: string | number }).id;
const getOwnChildren = (item: unknown) => (item as { children: readonly unknown[] }).children;

export default function Tree<
  T,
  TId extends string | number = T extends { id: infer Id extends string | number }
    ? Id
    : string | number,
>({
  items,
  getId = getOwnId as (item: T) => TId,
  getChildren = getOwnChildren as (item: T) => readonly T[],
  getLabel,
  renderItem,
  defaultExpandedIds = [],
  expandedIds,
  onExpandedIdsChange,
  toggleOnClick = true,
  selectedId = null,
  onSelectedIdChange,
  className,
  groupClassName,
  ...label
}: TreeProps<T, TId>) {
  const baseId = useId();
  const [ownExpanded, setOwnExpanded] = useState(() => new Set(defaultExpandedIds));
  const expanded = expandedIds ?? ownExpanded;
  const [focusedId, setFocusedId] = useState<TId | null>(null);
  const elements = useRef(new Map<TId, HTMLElement>());
  const selectable = onSelectedIdChange !== undefined;

  // Visible nodes in display order: what Up, Down, Home, End and type-ahead move through.
  const visible = useMemo(() => {
    const entries: Entry<T, TId>[] = [];
    const collect = (nodes: readonly T[], parentId: TId | null) => {
      for (const item of nodes) {
        const id = getId(item);
        const children = getChildren(item);
        entries.push({ item, id, parentId, hasChildren: children.length > 0 });
        if (children.length > 0 && expanded.has(id)) {
          collect(children, id);
        }
      }
    };
    collect(items, null);
    return entries;
  }, [items, expanded, getId, getChildren]);

  // The single tab stop: the focused node, else the selected one, else the first.
  const tabStopId = useMemo(() => {
    const isVisible = (id: TId | null) => id !== null && visible.some((entry) => entry.id === id);
    if (isVisible(focusedId)) return focusedId;
    if (isVisible(selectedId)) return selectedId;
    return visible[0]?.id ?? null;
  }, [visible, focusedId, selectedId]);

  const focus = (id: TId) => {
    setFocusedId(id);
    elements.current.get(id)?.focus();
  };

  const setOpen = (ids: TId[], open: boolean) => {
    const next = new Set(expanded);
    if (open) ids.forEach((id) => next.add(id));
    else ids.forEach((id) => next.delete(id));
    setOwnExpanded(next);
    onExpandedIdsChange?.(next);
  };

  const onKeyDown = (event: KeyboardEvent<HTMLUListElement>) => {
    if (event.altKey || event.ctrlKey || event.metaKey) return;
    // Keys pressed in a row's own controls, such as a menu, belong to that control.
    if (
      !(event.target instanceof HTMLElement) ||
      event.target.getAttribute("role") !== "treeitem"
    ) {
      return;
    }

    const index = visible.findIndex((entry) => entry.id === tabStopId);
    const current = visible[index];
    if (current === undefined) return;
    const open = expanded.has(current.id);
    const focusAt = (at: number) => {
      const target = visible[at];
      if (target) focus(target.id);
    };

    switch (event.key) {
      case "ArrowDown":
        focusAt(index + 1);
        break;
      case "ArrowUp":
        focusAt(index - 1);
        break;
      case "ArrowRight":
        if (current.hasChildren && !open) setOpen([current.id], true);
        // An open node's first child comes right after it.
        else if (current.hasChildren) focusAt(index + 1);
        break;
      case "ArrowLeft":
        if (current.hasChildren && open) setOpen([current.id], false);
        else if (current.parentId !== null) focus(current.parentId);
        break;
      case "Home":
        focusAt(0);
        break;
      case "End":
        focusAt(visible.length - 1);
        break;
      case "*":
        setOpen(
          visible
            .filter((entry) => entry.parentId === current.parentId && entry.hasChildren)
            .map((entry) => entry.id),
          true,
        );
        break;
      case "Enter":
        // Activate like a click, so a link row also follows its link.
        elements.current.get(current.id)?.click();
        break;
      case " ":
        if (selectable) onSelectedIdChange(current.id);
        break;
      default: {
        if (event.key.length !== 1 || event.key.trim() === "") return;
        const char = event.key.toLowerCase();
        // Search after the focused node first, wrapping around to the ones before it.
        const ordered = [...visible.slice(index + 1), ...visible.slice(0, index)];
        const match = ordered.find((entry) => getLabel(entry.item).toLowerCase().startsWith(char));
        if (match) focus(match.id);
      }
    }

    event.preventDefault();
  };

  const onItemClick = (entry: Entry<T, TId>) => {
    focus(entry.id);
    if (toggleOnClick && entry.hasChildren) setOpen([entry.id], !expanded.has(entry.id));
    if (selectable) onSelectedIdChange(entry.id);
  };

  const renderNodes = (nodes: readonly T[], level: number, parentId: TId | null): ReactNode =>
    nodes.map((item) => {
      const id = getId(item);
      const children = getChildren(item);
      const entry = { item, id, parentId, hasChildren: children.length > 0 };
      const isExpanded = entry.hasChildren ? expanded.has(id) : undefined;
      const state = {
        level,
        expanded: isExpanded,
        selected: selectable && id === selectedId,
        tabbable: id === tabStopId,
      };
      const groupId = `${baseId}-${id}`;

      const context: TreeItemContextValue = {
        state,
        ref: (element) => {
          if (element) elements.current.set(id, element);
          else elements.current.delete(id);
        },
        props: {
          role: "treeitem",
          "aria-expanded": isExpanded,
          "aria-selected": selectable ? state.selected : undefined,
          "aria-owns": isExpanded ? groupId : undefined,
          tabIndex: id === tabStopId ? 0 : -1,
          style: { "--tree-level": level },
          onFocus: () => setFocusedId(id),
          onClick: () => onItemClick(entry),
        },
      };

      return (
        <li key={id} role="none">
          <TreeItemContext.Provider value={context}>
            {renderItem(item, state)}
          </TreeItemContext.Provider>
          {isExpanded && (
            <ul id={groupId} role="group" className={groupClassName}>
              {renderNodes(children, level + 1, id)}
            </ul>
          )}
        </li>
      );
    });

  return (
    <ul role="tree" {...label} className={className} onKeyDown={onKeyDown}>
      {renderNodes(items, 0, null)}
    </ul>
  );
}

type TreeItemContextValue = {
  state: TreeItemState;
  ref: Ref<HTMLElement>;
  props: useRender.ElementProps<"div">;
};

const TreeItemContext = createContext<TreeItemContextValue | null>(null);

export type TreeItemProps = useRender.ComponentProps<"div", TreeItemState>;

/**
 * A node's treeitem row; return it from `Tree`'s `renderItem`. Renders a `<div>` by
 * default; use `render` to render another element, such as a link. Its props merge with
 * the tree's, so event handlers chain and classes join. The row sets `--tree-level`
 * (0 for roots) for indentation and `data-level`, `data-expanded`, `data-selected` and `data-tabbable`.
 */
export function TreeItem({ render, ...props }: TreeItemProps) {
  const context = useContext(TreeItemContext);
  if (context === null) {
    throw new Error("TreeItem must be returned from the renderItem prop of a Tree.");
  }

  return useRender({
    defaultTagName: "div",
    render,
    state: context.state,
    ref: context.ref,
    props: mergeProps<"div">(context.props, props),
  });
}
