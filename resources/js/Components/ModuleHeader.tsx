import { Link } from "@inertiajs/react";
import { Icon, IconButton } from "@larasell-dev/ui";
import { ArrowLeftLine } from "@larasell-dev/ui/icons";
import type { ReactNode } from "react";

type Props = {
  title: string;
  /** Renders a back arrow before the title; `label` names it for screen readers. */
  backLink?: { href: string; label: string };
  /** Rendered at the end of the header, e.g. page actions. */
  suffix?: ReactNode;
};

export default function ModuleHeader({ title, backLink, suffix }: Props) {
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
          >
            <Icon icon={ArrowLeftLine} />
          </IconButton>
        )}
        <h1 className="truncate type-body-md text-mauve-950 dark:text-mauve-50">{title}</h1>
      </div>
      {suffix}
    </header>
  );
}
