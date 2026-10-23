import { Dialog } from "@base-ui/react/dialog";
import { Link, router, usePage } from "@inertiajs/react";
import { Button, Icon, useScrollMaskScrollbar } from "@larasell-dev/ui";
import { AddLine, NodeTree } from "@larasell-dev/ui/icons";
import { cn } from "cn";
import { type ReactNode, type RefObject, useEffect, useId, useRef, useState } from "react";
import CategoryTree from "../Components/CategoryTree";
import Freeze from "../Components/Freeze";
import type { Page } from "../types";

// Pages of a single category also pass that category; the index and create pages don't.
type Props = Page<
  Larasell.Admin.Data.View.CategoriesPageData &
    Partial<Pick<Larasell.Admin.Data.View.CategoryPageData, "category">>
>;

export default function CategoryLayout({ children }: { children: ReactNode }) {
  const { categories, createUrl, category } = usePage<Props>().props;

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

  return (
    // Below lg the 3.5rem mobile nav sits above the layout, so it takes the rest of the viewport.
    <div className="flex h-[calc(100dvh-3.5rem)] lg:h-dvh">
      {/* Without categories there is no tree to show; the page offers to add the first one.
          Below lg the tree moves into a dialog, opened by CategoryTreeTrigger in the page header. */}
      {categories.length > 0 && (
        <>
          <TreePanel
            categories={categories}
            createUrl={createUrl}
            currentId={category?.id}
            className="hidden border-r border-border lg:flex"
          />
          <TreeDialog categories={categories} createUrl={createUrl} currentId={category?.id} />
        </>
      )}

      {/* Pages scroll their own content area so their header stays put, like the tree's. */}
      <div className="flex min-h-0 min-w-0 flex-1 flex-col">{children}</div>
    </div>
  );
}

// Links the trigger in the page header to the dialog here: Inertia remounts the page on every
// visit, so a dialog inside the page would vanish mid-navigation instead of animating out.
const treeDialog = Dialog.createHandle();

/** Opens the category tree below lg, where the layout hides it. Belongs in the page header. */
export function CategoryTreeTrigger() {
  return (
    <Dialog.Trigger
      handle={treeDialog}
      aria-label="Open categories"
      className="-mr-1.5 inline-flex size-8 shrink-0 cursor-pointer items-center justify-center text-mauve-950 outline-none hover:bg-mauve-100 focus-visible:focus-ring lg:hidden dark:text-mauve-100 dark:hover:bg-mauve-800 [&_svg]:size-5"
    >
      <Icon icon={NodeTree} />
    </Dialog.Trigger>
  );
}

function TreeDialog(props: Larasell.Admin.Data.View.CategoriesPageData & { currentId?: number }) {
  const { url } = usePage();
  // Remembers the page it was opened on, so it closes once a link in the tree leads elsewhere
  // but stays open while hiding, moving or adding products keeps the admin on the same page.
  const [openOn, setOpenOn] = useState<string | null>(null);
  const open = openOn === url;

  return (
    <Dialog.Root
      handle={treeDialog}
      open={open}
      onOpenChange={(open) => setOpenOn(open ? url : null)}
    >
      {/* Kept mounted, so the tree remembers its open categories between openings,
          like the panel beside the page does across visits. */}
      {/* Base UI's own invisible backdrop, which blocks the page, also starts below the
          mobile nav, so the nav stays usable while the dialog is open. */}
      <Dialog.Portal keepMounted className="[&>[data-base-ui-inert]]:top-14!">
        {/* Both start below the 3.5rem mobile nav, which stays visible above the dialog. */}
        <Dialog.Backdrop className="fixed inset-x-0 top-14 bottom-0 z-40 bg-[linear-gradient(rgb(0_0_0/10%),rgb(0_0_0/10%))] backdrop-blur-[2px] transition-opacity duration-300 data-ending-style:opacity-0 data-starting-style:opacity-0 lg:hidden" />
        <Dialog.Popup
          aria-label="Categories"
          className={cn(
            // Panel
            "fixed top-14 bottom-0 left-0 z-50 flex w-[calc(100%-3.5rem)] max-w-80 border-r border-border bg-white outline-none lg:hidden dark:bg-mauve-950",
            // Slides in from the left
            "transition-transform duration-300 data-ending-style:-translate-x-full data-starting-style:-translate-x-full",
            // Base UI leaves out the backdrop of a dialog opened from this one, such as the delete
            // confirmation, so the panel draws the same dimming over itself instead
            "after:pointer-events-none after:absolute after:inset-y-0 after:left-0 after:-right-px after:z-20 after:bg-[linear-gradient(rgb(0_0_0/10%),rgb(0_0_0/10%))] after:opacity-0 after:backdrop-blur-[2px] after:transition-opacity after:duration-300",
            "data-nested-dialog-open:after:opacity-100",
          )}
        >
          {/* Following a link closes the dialog in the same render that makes another category
              current, which would open it while the panel slides out. */}
          <Freeze frozen={!open}>
            <TreePanel {...props} className="flex flex-1" />
          </Freeze>
        </Dialog.Popup>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

// Its own component so the scroll mask is measured whenever the panel mounts, such as after
// the first category is created while the layout stays mounted.
function TreePanel({
  categories,
  createUrl,
  currentId,
  className,
}: Larasell.Admin.Data.View.CategoriesPageData & { currentId?: number; className: string }) {
  const treeScroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(treeScroll);
  useScrollMaskReset(treeScroll);
  // The dialog renders a second panel, so each needs its own heading id.
  const headingId = useId();

  return (
    <section
      aria-labelledby={headingId}
      className={`min-h-0 w-full max-w-80 shrink-0 flex-col ${className}`}
    >
      <header className="flex h-14 shrink-0 items-center justify-between border-b border-border pr-1.5 pl-6">
        <h1 id={headingId} className="type-body-md text-mauve-950 dark:text-mauve-50">
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
      <div ref={treeScroll} className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto pb-3">
        <CategoryTree categories={categories} labelledBy={headingId} currentId={currentId} />
      </div>
    </section>
  );
}

/**
 * The fade follows the scroll position through a scroll-driven animation. Once opening or
 * closing categories leaves nothing to scroll, its timeline goes inactive and the browser
 * keeps the last fade, so this restarts it whenever the element's content changes size.
 */
function useScrollMaskReset(ref: RefObject<HTMLElement | null>) {
  useEffect(() => {
    const element = ref.current;
    const content = element?.firstElementChild;
    if (!element || !content) return;
    const observer = new ResizeObserver(() => {
      element.style.animation = "none";
      void element.offsetHeight;
      element.style.animation = "";
    });
    observer.observe(content);
    return () => observer.disconnect();
  }, [ref]);
}
