import { usePage } from "@inertiajs/react";
import type { ReactNode } from "react";
import { ErrorBoundary } from "../Components/ErrorBoundary";
import CommandMenu from "../Components/CommandMenu/CommandMenu";
import { SearchCommands } from "../Components/CommandMenu/searchCommands";
import MobileNav from "../Components/Shell/MobileNav";
import SessionExpiredDialog from "../Components/Shell/SessionExpiredDialog";
import Sidebar from "../Components/Shell/Sidebar";
import { SkipLink } from "../Components/Shell/SkipLink";
import { UnsavedChangesToastHost } from "../Components/UnsavedChangesToast";

export default function SidebarLayout({ children }: { children: ReactNode }) {
  const { url } = usePage();

  return (
    <div className="flex min-h-dvh flex-col bg-base-canvas lg:flex-row">
      <SkipLink />
      <div className="sticky top-0 z-10 lg:hidden">
        <MobileNav />
      </div>
      <div className="sticky top-0 hidden h-dvh lg:block">
        <Sidebar />
      </div>
      {/* Fills the viewport below the mobile nav, so pages stack a fixed header above
          content that scrolls on its own. Pages taller than that, like the profile, scroll here. */}
      <main
        id="main-content"
        tabIndex={-1}
        className="flex h-[calc(100dvh-var(--mobile-nav-height))] min-w-0 flex-col overflow-y-auto focus:outline-none lg:h-dvh lg:flex-1"
      >
        {/* Around the page only, so the sidebar and command menu stay usable to go elsewhere,
            which shows the page again. */}
        <ErrorBoundary
          resetKey={url}
          frame={(crashed) => (
            <div className="flex flex-1 items-center justify-center px-6 py-12">{crashed}</div>
          )}
        >
          {children}
        </ErrorBoundary>
      </main>
      {/* Outside the page, so a toast can animate out after its page is gone. */}
      <UnsavedChangesToastHost />
      <SessionExpiredDialog />
      {/* Outside the page too, so it stays open while the page changes underneath. */}
      <CommandMenu />
      <SearchCommands />
    </div>
  );
}
