import { Link } from "@inertiajs/react";
import { Icon, IconButton, useScrollMaskScrollbar } from "@larasell-dev/ui";
import { ArrowLeftLine } from "@larasell-dev/ui/icons";
import { createContext, type ReactNode, type RefObject, useRef } from "react";

type HeaderProps = {
  title: string;
  /**
   * Renders a back arrow before the title; `label` names it for screen readers. `mobileOnly`
   * hides it from lg up, where the sidebar offers the way back.
   */
  backLink?: { href: string; label: string; mobileOnly?: boolean };
  /** Rendered at the end of the header, e.g. page actions. */
  actions?: ReactNode;
};

/** The bar at the top of a page with its title. */
function Header({ title, backLink, actions }: HeaderProps) {
  return (
    <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border px-6">
      <div className="flex min-w-0 flex-1 items-center gap-2">
        {backLink && (
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={backLink.label}
            nativeButton={false}
            // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
            role="link"
            render={<Link href={backLink.href} prefetch="hover" />}
            DANGEROUS_className={backLink.mobileOnly ? "lg:hidden" : undefined}
          >
            <Icon icon={ArrowLeftLine} />
          </IconButton>
        )}
        <h1 className="truncate type-body-md text-mauve-950 dark:text-mauve-50">{title}</h1>
      </div>
      {actions}
    </header>
  );
}

/** The scroll container of the Body around it, if any. UnsavedChangesToast centers in it. */
export const PageBodyContext = createContext<RefObject<HTMLElement | null> | null>(null);

/** The page's content below its Header, scrolling on its own so the header stays put. */
function Body({ children }: { children: ReactNode }) {
  const scroll = useRef<HTMLDivElement>(null);
  useScrollMaskScrollbar(scroll);

  return (
    <PageBodyContext value={scroll}>
      {/* The bottom padding lets the last field and its error scroll above the unsaved changes toast. */}
      <div
        ref={scroll}
        className="scroll-mask-y-from-95% min-h-0 flex-1 overflow-y-auto px-6 pt-6 pb-28"
      >
        <div className="max-w-2xl">{children}</div>
      </div>
    </PageBodyContext>
  );
}

/**
 * The building blocks of a page in the admin panel. The layout sizes the page to the viewport,
 * so a Header and a Body (or anything else that fills the rest, like a table) stack in it.
 */
export const Page = { Header, Body };
