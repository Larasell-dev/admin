import { readFileSync } from "node:fs";
import { createRequire } from "node:module";
import path from "node:path";
import { fileURLToPath } from "node:url";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import laravel from "laravel-vite-plugin";
import type { Plugin, PluginOption } from "vite";

const dir = path.dirname(fileURLToPath(import.meta.url));

/** Every export of the panel's shared modules, by module; resources/js/sharedModules.test.ts keeps it current. */
const sharedExports: Record<string, string[]> = JSON.parse(
  readFileSync(path.join(dir, "exports.json"), "utf8"),
);

const prefix = "\0larasell-admin:";

/**
 * Points imports of React, Inertia, @larasell-dev/ui and @larasell-dev/admin at the panel's own
 * copies, which it shares on window.LarasellAdmin.modules, so bundles hold only their own code.
 */
function sharedModules(applies: (importer: string) => boolean): Plugin {
  return {
    name: "larasell-admin:shared-modules",
    enforce: "pre",
    resolveId(source, importer) {
      if (!(source in sharedExports) || importer === undefined || !applies(importer)) {
        return null;
      }

      return prefix + source;
    },
    load(id) {
      if (!id.startsWith(prefix)) {
        return null;
      }

      const source = id.slice(prefix.length);

      if (source === "react/jsx-dev-runtime") {
        // A dev server compiles JSX to jsxDEV(), which the panel's production build leaves out.
        return [
          `const shared = window.LarasellAdmin.modules["react/jsx-dev-runtime"];`,
          `const runtime = window.LarasellAdmin.modules["react/jsx-runtime"];`,
          "export const Fragment = shared.Fragment;",
          "export const jsxDEV = shared.jsxDEV ?? ((type, props, key, isStatic) =>",
          "  (isStatic ? runtime.jsxs : runtime.jsx)(type, props, key));",
        ].join("\n");
      }

      const names = sharedExports[source];
      const named = names.filter((name) => name !== "default");

      return [
        `const shared = window.LarasellAdmin.modules[${JSON.stringify(source)}];`,
        names.includes("default") ? "export default shared.default;" : "",
        named.length > 0 ? `export const { ${named.join(", ")} } = shared;` : "",
      ].join("\n");
    },
  };
}

/**
 * For an app's own admin pages in resources/js/admin, built by the app's Vite config next to its
 * storefront, which keeps its own React.
 */
export function admin(): Plugin {
  const pages = `${path.sep}resources${path.sep}js${path.sep}admin${path.sep}`;

  return sharedModules((importer) => importer.includes(pages));
}

/**
 * Builds an extension's admin assets into public/vendor/{name}, where the extension's service
 * provider registers them with AdminPanel::vite() under the same name.
 */
export function larasell(options: { name: string; input: string[] }): PluginOption[] {
  return [
    sharedModules(() => true),
    {
      name: "larasell-admin:extension-css",
      config: () => ({
        resolve: {
          alias: {
            "@larasell-dev/admin/extension.css": path.join(dir, "extension.css"),
            // The UI kit only exports styles.css, which would also generate all of its utilities.
            "@larasell-dev/ui/theme.css": path.join(
              path.dirname(createRequire(import.meta.url).resolve("@larasell-dev/ui/package.json")),
              "src/theme.css",
            ),
          },
        },
      }),
    },
    {
      // React Refresh can't patch the panel's prebuilt production React, so reload instead.
      name: "larasell-admin:reload-scripts",
      handleHotUpdate({ file, server }) {
        if (/\.[jt]sx?$/.test(file)) {
          server.ws.send({ type: "full-reload" });

          return [];
        }
      },
    },
    laravel({
      input: options.input,
      publicDirectory: "public",
      buildDirectory: `vendor/${options.name}`,
      hotFile: "public/hot",
    }),
    react(),
    tailwindcss(),
  ];
}
