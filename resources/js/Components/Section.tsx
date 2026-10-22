import { Stack } from "@larasell-dev/ui";
import { createContext, type ReactNode, useContext, useId } from "react";

/** The id of the section's title, so the section is labelled by its header. */
const TitleId = createContext<string | undefined>(undefined);

/** A part of a page: a header, then its content, either as is or in a Section.Card. */
function Root({ children }: { children: ReactNode }) {
  const titleId = useId();

  return (
    <TitleId value={titleId}>
      <section aria-labelledby={titleId} className="flex flex-col gap-5">
        {children}
      </section>
    </TitleId>
  );
}

/** The section's title and description, flush with the content's edges, and an optional action on the right. */
function Header({
  title,
  description,
  action,
}: {
  title: ReactNode;
  description?: ReactNode;
  action?: ReactNode;
}) {
  return (
    // Tall enough for a button in the action slot, so adding or removing one doesn't shift the content.
    <div className="flex min-h-10.5 items-end justify-between gap-4">
      <div className="flex flex-col gap-0.5">
        <h3 id={useContext(TitleId)} className="type-label-md text-mauve-950 dark:text-mauve-50">
          {title}
        </h3>
        {description && (
          <p className="type-body-sm text-mauve-600 dark:text-mauve-400">{description}</p>
        )}
      </div>
      {action && <div className="shrink-0">{action}</div>}
    </div>
  );
}

/**
 * A bordered box without padding, so content like a table can run to its edges. Wrap content
 * in <Inset> to pad it; --inset sets how far.
 */
function Card({ children }: { children: ReactNode }) {
  return (
    <div className="border border-border bg-white [--inset:1.25rem] dark:bg-mauve-950">
      {children}
    </div>
  );
}

export const Section = { Root, Header, Card };

/** Stacks sections with the space between them. */
export function SectionStack({ children }: { children: ReactNode }) {
  return <Stack spacing={16}>{children}</Stack>;
}
