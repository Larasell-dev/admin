import { Dialog } from "@base-ui/react/dialog";
import { Button, Icon, IconButton } from "@larasell-dev/ui";
import { CloseLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { ReactNode } from "react";

type RootProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** Header, Content and Footer, in that order. */
  children: ReactNode;
};

/**
 * A panel that slides in from the right and fills the viewport's height, leaving some space
 * around it, e.g. for a form that adds an item to a page. A dialog underneath: it traps focus and
 * closes with Escape or a click outside.
 */
function Root({ open, onOpenChange, children }: RootProps) {
  return (
    <Dialog.Root open={open} onOpenChange={onOpenChange}>
      <Dialog.Portal>
        <Dialog.Backdrop className="bg-base-overlay backdrop-blur-overlay fixed inset-0 transition-opacity duration-200 data-ending-style:opacity-0 data-starting-style:opacity-0" />
        <Dialog.Popup className="border-base-subtle bg-base-elevated shadow-elevated bg-clip-padding fixed top-3 right-3 bottom-3 flex w-[calc(100%-1.5rem)] max-w-lg flex-col border outline-none transition-[translate,opacity] duration-200 ease-out data-ending-style:translate-x-8 data-ending-style:opacity-0 data-starting-style:translate-x-8 data-starting-style:opacity-0">
          {children}
        </Dialog.Popup>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

/** The title, an optional description and a button that closes the slideover. */
function Header({ title, description }: { title: ReactNode; description?: ReactNode }) {
  return (
    <div className="flex items-start gap-4 px-6 pt-6 pb-4">
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <Dialog.Title className="type-headline-md text-base-primary text-balance">
          {title}
        </Dialog.Title>
        {description && (
          <Dialog.Description className="type-body-md text-base-secondary text-pretty">
            {description}
          </Dialog.Description>
        )}
      </div>
      <Dialog.Close
        render={
          <IconButton
            variant="tertiary"
            size="sm"
            aria-label={fbs("Close", "Slideover: accessible name of the button that closes it")}
          >
            <Icon icon={CloseLine} />
          </IconButton>
        }
      />
    </div>
  );
}

/** What the slideover is for; scrolls when it doesn't fit. */
function Content({ children }: { children: ReactNode }) {
  return <div className="min-h-0 flex-1 overflow-y-auto px-6 pb-6">{children}</div>;
}

/** Stays at the bottom, below the scrolling content, a level lower than it. */
function Footer({ children }: { children: ReactNode }) {
  return (
    <div className="border-base-subtle bg-base-section shrink-0 border-t px-6 py-4">{children}</div>
  );
}

/** The footer's buttons, aligned to the end with consistent spacing. */
function Actions({ children }: { children: ReactNode }) {
  return <div className="flex items-center justify-end gap-2">{children}</div>;
}

/** Closes the slideover without doing anything. */
function Close({ children }: { children: ReactNode }) {
  return <Dialog.Close render={<Button variant="secondary" />}>{children}</Dialog.Close>;
}

export const Slideover = { Root, Header, Content, Footer, Actions, Close };
