import "../css/app.css";
import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { ToastProvider } from "@larasell-dev/ui";
import { commands } from "./Platform/Panel/Components/CommandMenu/commands";
import { contentLocaleCommand } from "./Platform/Panel/Components/CommandMenu/contentLocaleCommand";
import { goToCommand } from "./Platform/Panel/Components/CommandMenu/goToCommand";
import { logOutCommand } from "./Platform/Panel/Components/CommandMenu/logOutCommand";
import { themeCommand } from "./Platform/Panel/Components/CommandMenu/themeCommand";
import { interfaceLocaleCommand } from "./Platform/Panel/Components/CommandMenu/interfaceLocaleCommand";
import { ThemeChangedToasts } from "./Platform/Panel/Components/Shell/ThemeChangedToast";
import { extensions, Larasell, SectionForm } from "./extensions";
import { sharedModules } from "./sharedModules";
import { AppHotkeysProvider } from "./Platform/Support/hotkeys";
import { setUpInterfaceLocale } from "./Platform/Support/interfaceLocale";
import { setUpLive } from "./Platform/Support/live";

type PageModule = { default: ResolvedComponent };

const pages = import.meta.glob<PageModule>([
  "./*/Pages/*.tsx",
  "./Platform/*/Pages/*.tsx",
  "!**/*.test.tsx",
]);

async function resolvePage(name: string): Promise<ResolvedComponent> {
  const page = await extensions.resolve(name);

  if (page) {
    return page;
  }

  const loader = pages[`./${name}.tsx`];

  if (!loader) {
    throw new Error(`Admin page [${name}] not found.`);
  }

  return (await loader()).default;
}

declare global {
  interface Window {
    /** Internal: the admin Vite plugin's stand-in modules read the shared modules from here. */
    LarasellAdmin: {
      modules: typeof sharedModules;
      boot: () => void;
    };
  }
}

window.LarasellAdmin = {
  modules: sharedModules,
  boot() {
    // Before the extensions' commands, which the menu lists after the panel's.
    commands.register(
      goToCommand,
      themeCommand,
      interfaceLocaleCommand,
      contentLocaleCommand,
      logOutCommand,
    );
    // The panel's own sections, which the server adds like an extension's.
    Larasell.booting(({ sections }) => {
      sections.register(
        SectionForm.Product,
        "seo",
        () => import("./Seo/Components/ProductUrlsSection"),
      );
      sections.register(
        SectionForm.Category,
        "seo",
        () => import("./Seo/Components/CategoryUrlsSection"),
      );
    });
    extensions.boot();

    // The translations load before the first render, so the panel never shows in English first.
    void setUpInterfaceLocale().then(() =>
      createInertiaApp({
        serverHead: true,
        live: setUpLive(),
        resolve: async (name, page) => {
          // Alongside the page, so the sections' fields are there when its form mounts.
          const sections = Array.isArray(page?.props.sections) ? page.props.sections : [];
          const [component] = await Promise.all([
            resolvePage(name),
            extensions.loadSections(sections),
          ]);

          return component;
        },
        progress: {
          color: "var(--color-progress)",
        },
        setup({ el, App, props }) {
          createRoot(el).render(
            <AppHotkeysProvider>
              <ToastProvider>
                <App {...props} />
                <ThemeChangedToasts />
              </ToastProvider>
            </AppHotkeysProvider>,
          );
        },
      }),
    );
  },
};
