import { recommended } from "@larasell-dev/ui/eslint";
import { defineConfig } from "oxlint";

export default defineConfig({
  extends: [recommended],
  plugins: ["eslint", "typescript", "react", "react-perf", "vitest"],
  // Translators only see the text and its description, so descriptions must say where it shows.
  jsPlugins: ["@nkzw/eslint-plugin-fbtee"],
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
  ignorePatterns: ["public/**", "vendor/**", "workbench/**"],
});
