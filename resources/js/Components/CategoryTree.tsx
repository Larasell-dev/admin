import { Link, router } from "@inertiajs/react";
import { Icon, Menu } from "@larasell-dev/ui";
import {
  ArrowDownSLine,
  ArrowRightSLine,
  EyeLine,
  EyeOffLine,
  FolderLine,
  FolderOpenLine,
  More2Fill,
} from "@larasell-dev/ui/icons";
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

/**
 * The store's categories as a tree of links to their pages. It starts closed, except
 * for the path to the current category. Hidden categories are greyed out.
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
  return (
    <Tree
      aria-labelledby={labelledBy}
      items={categories}
      getLabel={(category) => category.name}
      defaultExpandedIds={currentId === null ? [] : ancestorIds(categories, currentId)}
      // Not selectable: this only makes the current category the tree's tab stop.
      selectedId={currentId}
      renderItem={(category, { expanded, tabbable }) => (
        // The menu sits next to the link, not inside it, so clicking it never follows the link.
        <div className="group/row relative">
          <TreeItem
            render={<Link href={category.url} prefetch="hover" />}
            aria-current={category.id === currentId ? "page" : undefined}
            className="type-body-md flex cursor-pointer items-center gap-1.5 py-2 pr-10 pl-[calc(1rem+var(--tree-level)*1.25rem)] text-mauve-950 outline-none select-none group-hover/row:bg-mauve-50 focus-visible:focus-ring focus-visible:-outline-offset-2 aria-[current=page]:bg-mauve-100 dark:text-mauve-50 dark:group-hover/row:bg-mauve-900 dark:aria-[current=page]:bg-mauve-900"
          >
            <span className="flex size-4 shrink-0 text-mauve-500 dark:text-mauve-400">
              {expanded !== undefined && (
                <Icon icon={expanded ? ArrowDownSLine : ArrowRightSLine} />
              )}
            </span>
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
      )}
    />
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

  return (
    <Menu.Root>
      <Menu.Trigger
        aria-label={`Actions for ${category.name}`}
        tabIndex={tabbable ? 0 : -1}
        className="absolute top-0 right-0 inline-flex size-9 cursor-pointer items-center justify-center text-mauve-950 opacity-0 select-none group-has-[:focus-visible]/row:opacity-100 group-hover/row:opacity-100 hover:bg-mauve-100 focus-visible:focus-ring focus-visible:-outline-offset-2 data-popup-open:bg-mauve-100 data-popup-open:opacity-100 dark:text-mauve-100 dark:hover:bg-mauve-800 dark:data-popup-open:bg-mauve-800"
      >
        <Icon icon={More2Fill} />
      </Menu.Trigger>
      <Menu.Popup>
        {category.visible ? (
          <Menu.Item icon={EyeOffLine} onClick={() => post(category.hideUrl, false)}>
            Hide
          </Menu.Item>
        ) : (
          <Menu.Item icon={EyeLine} onClick={() => post(category.unhideUrl, true)}>
            Make visible
          </Menu.Item>
        )}
      </Menu.Popup>
    </Menu.Root>
  );
}
