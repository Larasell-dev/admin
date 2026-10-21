import { useScrollMaskScrollbar } from "@larasell-dev/ui";
import { useRef } from "react";
import CategoryLayout from "../Layouts/CategoryLayout";
import SidebarLayout from "../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CategoryPageData;

export default function Category({ category }: Props) {
  const contentScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(contentScroll);

  return (
    <>
      <header className="flex h-14 shrink-0 items-center border-b border-border px-6">
        <h2 className="type-body-md text-mauve-950 dark:text-mauve-50">{category.name}</h2>
      </header>
      <div
        ref={contentScroll}
        className="scroll-mask-y-from-95% min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-6 type-body-md text-mauve-600 dark:text-mauve-400"
      />
    </>
  );
}

Category.layout = [SidebarLayout, CategoryLayout];
