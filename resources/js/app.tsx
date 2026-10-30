import "../css/app.css";
import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { ToastProvider } from "@larasell-dev/ui";
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
