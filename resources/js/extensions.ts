import type { ComponentType } from "react";

export type PageModule = { default: ComponentType<any> };

export type PageLoader = () => Promise<PageModule>;

export type BootingContext = {
  pages: {
    /**
     * Adds a page that controllers render with Inertia::render($name). Prefix the name with the
     * extension's name, e.g. "acme-wishlists::Wishlist/Show", so extensions can't clash. The
     * loader runs when the page is first opened: pass () => import("./Pages/Wishlist/Show").
     */
    register(name: string, loader: PageLoader): void;
  };
};

/**
 * What extensions and app pages hook into, and the panel boots: callbacks passed to
 * Larasell.booting() run just before Inertia renders the first page.
 */
export function createExtensions() {
  const pages: Record<string, PageLoader> = {};
  const callbacks: Array<(context: BootingContext) => void> = [];
  const context: BootingContext = {
    pages: {
      register(name, loader) {
        pages[name] = loader;
      },
    },
  };
  let booted = false;

  return {
    Larasell: {
      booting(callback: (context: BootingContext) => void): void {
        if (booted) {
          callback(context);
        } else {
          callbacks.push(callback);
        }
      },
    },
    boot(): void {
      booted = true;
      callbacks.splice(0).forEach((callback) => callback(context));
    },
    async resolve(name: string): Promise<ComponentType<any> | undefined> {
      const loader = pages[name];

      return loader ? (await loader()).default : undefined;
    },
  };
}

export const extensions = createExtensions();

export const { Larasell } = extensions;
