import { cn } from "cn";

export function SkipLink() {
  return (
    <a
      href="#main-content"
      className={cn(
        // Hidden until focused
        "focus-visible:focus-ring sr-only focus:not-sr-only",
        // Shown at the top left
        "focus:fixed focus:top-0 focus:left-0 focus:z-[100] focus:bg-base-canvas focus:px-4 focus:py-2 focus:type-body-md-strong focus:text-base-primary",
      )}
    >
      <fbt desc="Link for keyboard and screen reader users, shown on focus at the top of every page, that jumps past the navigation to the page's content">
        Skip to main content
      </fbt>
    </a>
  );
}
