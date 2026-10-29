import { mkdtemp, mkdir, readFile, readdir, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { build, type Plugin } from "vite";
import { afterEach, describe, expect, it } from "vitest";
import { admin, larasell } from "./plugin";

let root: string;

afterEach(async () => {
  if (root) await rm(root, { recursive: true, force: true });
});

async function fixture(files: Record<string, string>) {
  root = await mkdtemp(path.join(tmpdir(), "larasell-extension-"));

  for (const [file, contents] of Object.entries(files)) {
    await mkdir(path.dirname(path.join(root, file)), { recursive: true });
    await writeFile(path.join(root, file), contents);
  }
}

async function builtAssets(name: string) {
  const dir = path.join(root, "public/vendor", name, "assets");
  const files = await readdir(dir);

  return Promise.all(
    files.map(async (file) => ({ file, contents: await readFile(path.join(dir, file), "utf8") })),
  );
}

describe("larasell()", () => {
  it("builds an extension against the panel's copies of shared modules", async () => {
    await fixture({
      "resources/js/admin.ts": `
        import { Larasell } from "@larasell-dev/admin";
        Larasell.booting(({ pages }) => pages.register("acme-wishlists::Show", () => import("./Show")));
      `,
      "resources/js/Show.tsx": `
        import { useReducer } from "react";
        import { Badge } from "@larasell-dev/ui";
        import { Page, SidebarLayout } from "@larasell-dev/admin";
        export default function Show() {
          const [count, increment] = useReducer((n: number) => n + 1, 0);
          return <SidebarLayout><Page.Body><Badge onClick={increment}>{count}</Badge></Page.Body></SidebarLayout>;
        }
      `,
    });

    await build({
      root,
      logLevel: "silent",
      plugins: [larasell({ name: "acme-wishlists", input: ["resources/js/admin.ts"] })],
    });

    const assets = await builtAssets("acme-wishlists");
    const code = assets.map(({ contents }) => contents).join("\n");

    expect(code).toMatch(/LarasellAdmin\.modules\[.@larasell-dev\/admin.\]/);
    expect(code).toMatch(/LarasellAdmin\.modules(\.react\b|\[.react.\])/);
    expect(code).toMatch(/LarasellAdmin\.modules\[.@larasell-dev\/ui.\]/);
    // React itself isn't bundled: its source would add well over 10 KB.
    expect(code.length).toBeLessThan(10_000);
    // The page is a chunk of its own, loaded when it's opened.
    expect(assets.filter(({ file }) => file.endsWith(".js"))).toHaveLength(2);
  }, 30_000);

  it("gives extension stylesheets the panel's theme without its base styles", async () => {
    await fixture({
      "resources/css/admin.css": `@import "@larasell-dev/admin/extension.css";\n@source "../js";\n`,
      "resources/js/admin.ts": `document.body.className = "flex-col text-muted";`,
    });

    await build({
      root,
      logLevel: "silent",
      plugins: [
        larasell({
          name: "acme-wishlists",
          input: ["resources/js/admin.ts", "resources/css/admin.css"],
        }),
      ],
    });

    const css = (await builtAssets("acme-wishlists")).find(({ file }) => file.endsWith(".css"));

    expect(css?.contents).toContain("@layer components");
    expect(css?.contents).toMatch(/\.flex-col\{/);
    // Tailwind's preflight is the panel's; repeating it would reset the panel's own styles.
    expect(css?.contents).not.toContain("box-sizing:border-box");
  }, 30_000);
});

describe("admin()", () => {
  const plugin = admin() as Plugin & {
    resolveId: (source: string, importer?: string) => string | null;
    load: (id: string) => string | null;
  };

  async function standIn(source: string, modules: Record<string, object>) {
    const id = plugin.resolveId(source, "/app/resources/js/admin/app.tsx");
    (globalThis as any).window = { LarasellAdmin: { modules } };

    try {
      return await import(`data:text/javascript,${encodeURIComponent(plugin.load(id!)!)}`);
    } finally {
      delete (globalThis as any).window;
    }
  }

  it("lets pages from a dev server render in the panel's production build, which has no jsxDEV", async () => {
    const jsx = (type: string, props: object, key?: string) => ({ jsx: [type, props, key] });
    const jsxs = (type: string, props: object, key?: string) => ({ jsxs: [type, props, key] });

    const { jsxDEV } = await standIn("react/jsx-dev-runtime", {
      "react/jsx-dev-runtime": { Fragment: "fragment", jsxDEV: undefined },
      "react/jsx-runtime": { Fragment: "fragment", jsx, jsxs },
    });

    expect(jsxDEV("p", { children: "a" }, "key", false)).toEqual({
      jsx: ["p", { children: "a" }, "key"],
    });
    expect(jsxDEV("p", { children: ["a", "b"] }, undefined, true)).toEqual({
      jsxs: ["p", { children: ["a", "b"] }, undefined],
    });
  });

  it("points imports in an app's admin pages at the panel", () => {
    expect(plugin.resolveId("react", "/app/resources/js/admin/pages/Report.tsx")).toBeTruthy();
    expect(plugin.resolveId("@larasell-dev/admin", "/app/resources/js/admin/app.tsx")).toBeTruthy();
  });

  it("leaves the rest of the app's frontend alone", () => {
    expect(plugin.resolveId("react", "/app/resources/js/app.tsx")).toBeNull();
  });
});
