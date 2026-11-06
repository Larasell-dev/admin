import type { ComponentType } from "react";
import { type CommandDefinition, commands } from "./Components/CommandMenu/commands";

export type PageModule = { default: ComponentType<any> };

export type PageLoader = () => Promise<PageModule>;

/** What the panel gives a section's component, besides the props its server side gave. */
export type SectionComponentProps<Props = unknown> = {
  /** What the section's server side returned from props(). */
  props: Props;
  /** The name of one of the section's fields: name("note") is "sections[acme-notes][note]". */
  name: (field: string) => string;
  /** The error of one of the section's fields, e.g. error("note"), from its rules or listener. */
  error: (field: string) => string | undefined;
};

export type SectionModule = { default: ComponentType<SectionComponentProps<any>> };

export type SectionLoader = () => Promise<SectionModule>;

export type BootingContext = {
  pages: {
    /**
     * Adds a page that controllers render with Inertia::render($name). Prefix the name with the
     * extension's name, e.g. "acme-wishlists::Wishlist/Show", so extensions can't clash. The
     * loader runs when the page is first opened: pass () => import("./Pages/Wishlist/Show").
     */
    register(name: string, loader: PageLoader): void;
  };
  sections: {
    /**
     * Adds the component of a section that the server added to a page with Sections::add($page,
     * $key). It renders inside the page's form, after the page's own sections, so its fields
     * are saved with the page. The loader runs when a page with the section is first opened.
     */
    register(page: string, key: string, loader: SectionLoader): void;
  };
  commands: {
    /**
     * Adds commands to the command menu (Mod+K) on every page. Prefix their ids with the
     * extension's name, e.g. "acme-wishlists.export", so extensions can't clash. A command's
     * `use` hook can read the page to leave itself out where it doesn't apply.
     */
    register(...definitions: CommandDefinition[]): void;
  };
};

/**
 * What extensions and app pages hook into, and the panel boots: callbacks passed to
 * Larasell.booting() run just before Inertia renders the first page.
 */
export function createExtensions() {
  const pages: Record<string, PageLoader> = {};
  const sectionLoaders: Record<string, SectionLoader> = {};
  const sectionComponents: Record<string, ComponentType<SectionComponentProps>> = {};
  const sectionId = (page: string, key: string) => `${page}/${key}`;
  const callbacks: Array<(context: BootingContext) => void> = [];
  const context: BootingContext = {
    pages: {
      register(name, loader) {
        pages[name] = loader;
      },
    },
    sections: {
      register(page, key, loader) {
        sectionLoaders[sectionId(page, key)] = loader;
      },
    },
    commands: {
      register(...definitions) {
        commands.register(...definitions);
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
    /**
     * Loads the components of a page's sections before the page renders, since its form takes
     * the fields it starts with as its unchanged values when it mounts.
     */
    async loadSections(sections: ReadonlyArray<{ page: string; key: string }>): Promise<void> {
      await Promise.all(
        sections.map(async ({ page, key }) => {
          const id = sectionId(page, key);
          const loader = sectionLoaders[id];

          if (loader && !sectionComponents[id]) {
            sectionComponents[id] = (await loader()).default;
          }
        }),
      );
    },
    /** A section's component once loadSections() loaded it; none when no extension registered it. */
    section(page: string, key: string): ComponentType<SectionComponentProps> | undefined {
      return sectionComponents[sectionId(page, key)];
    },
  };
}

export const extensions = createExtensions();

export const { Larasell } = extensions;
