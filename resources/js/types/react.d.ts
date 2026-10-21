import "react";

// Lets `style` set CSS custom properties, such as `--tree-level`, without a cast. This only
// affects this package's own sources: it ships built assets, not these types.
declare module "react" {
  interface CSSProperties {
    [property: `--${string}`]: string | number | undefined;
  }
}
