export function SkipLink() {
  return (
    <a
      href="#main-content"
      className="focus-visible:focus-ring sr-only focus:not-sr-only focus:fixed focus:top-0 focus:left-0 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:type-body-md-strong focus:text-mauve-950 dark:focus:bg-mauve-950 dark:focus:text-mauve-100"
    >
      <fbt desc="Link for keyboard and screen reader users, shown on focus at the top of every page, that jumps past the navigation to the page's content">
        Skip to main content
      </fbt>
    </a>
  );
}
