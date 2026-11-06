import "../css/app.css";
import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { ToastProvider } from "@larasell-dev/ui";
import { commands } from "./Components/CommandMenu/commands";
import { contentLocaleCommand } from "./Components/CommandMenu/contentLocaleCommand";
import { goToCommand } from "./Components/CommandMenu/goToCommand";
import { logOutCommand } from "./Components/CommandMenu/logOutCommand";
import { themeCommand } from "./Components/CommandMenu/themeCommand";
import { interfaceLocaleCommand } from "./Components/CommandMenu/interfaceLocaleCommand";
import { ThemeChangedToasts } from "./Components/Shell/ThemeChangedToast";
import { extensions } from "./extensions";
import { sharedModules } from "./sharedModules";
import { AppHotkeysProvider } from "./utils/hotkeys";
import { setUpInterfaceLocale } from "./utils/interfaceLocale";

type PageModule = { default: ResolvedComponent };

const pages = import.meta.glob<PageModule>("./Pages/**/*.tsx");

async function resolvePage(name: string): Promise<ResolvedComponent> {
  const page = await extensions.resolve(name);

  if (page) {
    return page;
  }

  const loader = pages[`./Pages/${name}.tsx`];

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
    extensions.boot();

    // The translations load before the first render, so the panel never shows in English first.
    void setUpInterfaceLocale().then(() =>
      createInertiaApp({
        serverHead: true,
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
