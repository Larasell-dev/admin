import { expect, it } from "vitest";
import { sharedModules } from "./sharedModules";

// The admin Vite plugin generates the stand-in modules that extensions import from this file,
// so every export of the panel's copies is available to them. Update it with `npx vitest -u`.
it("lists every export that extensions can import from the panel", async () => {
  const exports = Object.fromEntries(
    Object.entries(sharedModules).map(([id, module]) => [
      id,
      Object.keys(module)
        .filter((name) => /^[A-Za-z_$][\w$]*$/.test(name))
        .sort(),
    ]),
  );

  await expect(`${JSON.stringify(exports, null, 2)}\n`).toMatchFileSnapshot("../vite/exports.json");
});

it("shares the panel's own extension API", () => {
  expect(Object.keys(sharedModules["@larasell-dev/admin"]).sort()).toEqual([
    "Larasell",
    "Page",
    "ResourceTable",
    "SidebarLayout",
  ]);
});
