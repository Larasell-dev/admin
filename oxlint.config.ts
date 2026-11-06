import { recommended } from "@larasell-dev/ui/eslint";
import { defineConfig } from "oxlint";

export default defineConfig({
  extends: [recommended],
  plugins: ["eslint", "typescript", "react", "react-perf", "vitest"],
  // Translators only see the text and its description, so descriptions must say where it shows.
  jsPlugins: ["@nkzw/eslint-plugin-fbtee", "./lint/eslint/larasell-admin.mjs"],
  options: {
    typeAware: true,
  },
  categories: {
    correctness: "error",
  },
  rules: {
    "@nkzw/fbtee/no-empty-strings": "error",
    "@nkzw/fbtee/no-unhelpful-desc": "error",
  },
  overrides: [
    {
      files: ["**/*.test.ts", "**/*.test.tsx"],
      rules: {
        // Tests mark what they set up, exercise and check, like the Pest tests.
        "larasell-admin/arrange-act-assert": "error",
      },
    },
  ],
  ignorePatterns: ["public/**", "vendor/**", "workbench/**"],
});
