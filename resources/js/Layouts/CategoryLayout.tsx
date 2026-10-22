import { Link, router, usePage } from "@inertiajs/react";
import { Button, Icon, useScrollMaskScrollbar } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import { type ReactNode, useEffect, useRef } from "react";
import CategoryTree from "../Components/CategoryTree";
import type { Page } from "../types";

export default function CategoryLayout({ children }: { children: ReactNode }) {
  // Pages of a single category also pass that category; the index and create pages don't.
  const { categories, createUrl, category } =
    usePage<
      Page<
        Larasell.Admin.Data.View.CategoriesPageData &
          Partial<Pick<Larasell.Admin.Data.View.CategoryPageData, "category">>
      >
    >().props;

  // Every category page shares the tree, so a change can leave any prefetched category page
  // stale, not just the one the request returns (the only one Inertia flushes). Flushing once
  // the request finishes also drops prefetches still in flight, which may predate the change.
  useEffect(
    () =>
      router.on("finish", (event) => {
        if (event.detail.visit.method !== "get") router.flushAll();
      }),
    [],
  );

  const treeScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(treeScroll);

  return (
    // Below lg the 3.5rem mobile nav sits above the layout, so it takes the rest of the viewport.
    <div className="flex h-[calc(100dvh-3.5rem)] lg:h-dvh">
      <section
        aria-labelledby="category-tree-heading"
        className="flex min-h-0 w-full max-w-80 shrink-0 flex-col border-r border-border"
      >
        <header className="flex h-14 shrink-0 items-center justify-between border-b border-border pr-1.5 pl-6">
          <h1 id="category-tree-heading" className="type-body-md text-mauve-950 dark:text-mauve-50">
            Categories
          </h1>
          <Button
            variant="tertiary"
            nativeButton={false}
            // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
            role="link"
            render={<Link href={createUrl} prefetch="hover" />}
          >
            <Icon icon={AddLine} />
            Add
          </Button>
        </header>
        <div
          ref={treeScroll}
          className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto pb-3"
        >
          {categories.length > 0 && (
            <CategoryTree
              categories={categories}
              labelledBy="category-tree-heading"
              currentId={category?.id}
            />
          )}
        </div>
      </section>

      {/* Pages scroll their own content area so their header stays put, like the tree's. */}
      <div className="flex min-h-0 min-w-0 flex-1 flex-col">{children}</div>
    </div>
  );
}
