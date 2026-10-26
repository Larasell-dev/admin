import { Drawer } from "@base-ui/react/drawer";
import { Link, router, usePage } from "@inertiajs/react";
import { Icon, IconButton } from "@larasell-dev/ui";
import { MenuLine } from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { useEffect, useState } from "react";
import { arrayFirst, assertDefined } from "ts-extras";
import type { Page } from "../types";
import { overlayExclusive } from "../utils/hotkeys";
import Logo from "./Logo";
import Sidebar from "./Sidebar";

export default function MobileNav() {
  const { props } = usePage<Page>();
  const home = arrayFirst(props.navigation);
  assertDefined(home, "The admin panel needs at least one navigation item.");
  const { open, setOpen, frozen } = useDrawer();

  return (
    <Drawer.Root open={open} onOpenChange={setOpen} swipeDirection="left">
      <header className="box-border h-(--mobile-nav-height) border-b border-border bg-white dark:bg-mauve-950">
        <div className="flex h-full items-center justify-between px-6">
          <Link
            href={home.url}
            aria-label="Home"
            className="inline-block outline-none focus-visible:focus-ring"
          >
            <Logo variant="wordmark" className="h-6 w-auto text-mauve-950 dark:text-white" />
          </Link>
          <Drawer.Trigger
            render={
              <IconButton variant="tertiary" size="sm" aria-label="Open menu">
                <Icon icon={MenuLine} />
              </IconButton>
            }
          />
        </div>
      </header>

      <Drawer.Portal>
        <Drawer.Backdrop className="fixed inset-0 z-40 bg-[linear-gradient(rgb(0_0_0/10%),rgb(0_0_0/10%))] backdrop-blur-[2px] transition-opacity duration-300 data-ending-style:opacity-0 data-starting-style:opacity-0" />
        <Drawer.Viewport className="fixed inset-0 z-50 pointer-events-none">
          {/* The sidebar extends a screen width to the left, so dragging the panel right keeps its background, border and highlight. */}
          <Drawer.Popup
            aria-label="Navigation"
            className="pointer-events-auto h-full w-fit translate-x-(--drawer-swipe-movement-x) outline-none transition-transform duration-300 data-ending-style:-translate-x-full data-starting-style:-translate-x-full data-swiping:duration-0 [&_aside]:-ml-[100vw] [&_aside]:w-[calc(100vw+16rem)] [&_aside>div]:pl-[calc(100vw+1.5rem)] [&_nav_a]:pl-[calc(100vw+1.5rem)] [&_aside>button]:pl-[calc(100vw+1.5rem)]"
          >
            <HotkeysProvider scope="mobile-nav" exclusive={overlayExclusive} enabled={open}>
              <Sidebar frozen={frozen} />
            </HotkeysProvider>
          </Drawer.Popup>
        </Drawer.Viewport>
      </Drawer.Portal>
    </Drawer.Root>
  );
}

/**
 * The drawer's open state, closing once the next page has loaded. `frozen` holds the sidebar
 * from the start of a visit, before the page changes, until the drawer has slid out.
 */
function useDrawer() {
  const [open, setOpen] = useState(false);
  const [visiting, setVisiting] = useState(false);

  useEffect(() => router.on("success", () => setOpen(false)), []);
  useEffect(() => router.on("start", () => setVisiting(true)), []);
  useEffect(() => router.on("finish", () => setVisiting(false)), []);

  return { open, setOpen, frozen: !open || visiting };
}
