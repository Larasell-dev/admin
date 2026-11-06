import { defineConfig } from "vite";
import { larasell } from "../resources/vite/plugin";

/**
 * The playground's own admin extension, built the way a plugin builds one:
 * `npm run build:workbench`, then `vendor/bin/testbench larasell:link`.
 */
export default defineConfig({
  root: import.meta.dirname,
  plugins: [larasell({ name: "workbench", input: ["resources/js/admin.tsx"] })],
});
