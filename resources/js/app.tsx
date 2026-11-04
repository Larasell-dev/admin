import "../css/app.css";
import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { ToastProvider } from "@larasell-dev/ui";
import { commands } from "./Components/CommandMenu/commands";
import { goToCommand, logOutCommand } from "./Components/CommandMenu/navigationCommands";
import { themeCommand } from "./Components/CommandMenu/themeCommand";
import { ThemeChangedToasts } from "./Components/ThemeChangedToast";
import { extensions } from "./extensions";
import { sharedModules } from "./sharedModules";
import { AppHotkeysProvider } from "./utils/hotkeys";

type PageModule = { default: ResolvedComponent };

const pages = import.meta.glob<PageModule>("./Pages/**/*.tsx");

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
    commands.register(goToCommand, themeCommand, logOutCommand);
    extensions.boot();

    void createInertiaApp({
      serverHead: true,
      resolve: async (name) => {
        const page = await extensions.resolve(name);

        if (page) {
          return page;
        }

        const loader = pages[`./Pages/${name}.tsx`];

        if (!loader) {
          throw new Error(`Admin page [${name}] not found.`);
        }

        return (await loader()).default;
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
    });
  },
};
