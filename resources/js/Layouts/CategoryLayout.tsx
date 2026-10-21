import { usePage } from "@inertiajs/react";
import { useScrollMaskScrollbar } from "@larasell-dev/ui";
import { type ReactNode, useRef } from "react";
import CategoryTree from "../Components/CategoryTree";
import type { Page } from "../types";

export default function CategoryLayout({ children }: { children: ReactNode }) {
  // Category pages also pass the category they show; the index page doesn't.
  const { categories, category } =
    usePage<
      Page<
        Larasell.Admin.Data.View.CategoriesPageData &
          Partial<Larasell.Admin.Data.View.CategoryPageData>
      >
    >().props;

  const treeScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(treeScroll);

  return (
    // Below lg the 3.5rem mobile nav sits above the layout, so it takes the rest of the viewport.
    <div className="flex h-[calc(100dvh-3.5rem)] lg:h-dvh">
      <section
        aria-labelledby="category-tree-heading"
        className="flex min-h-0 w-full max-w-80 shrink-0 flex-col border-r border-border"
      >
        <header className="flex h-14 shrink-0 items-center border-b border-border px-6">
          <h1 id="category-tree-heading" className="type-body-md text-mauve-950 dark:text-mauve-50">
            Categories
          </h1>
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
