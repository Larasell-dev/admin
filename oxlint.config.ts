import { recommended } from "@larasell-dev/ui/eslint";
import { defineConfig } from "oxlint";

export default defineConfig({
  extends: [recommended],
  plugins: ["eslint", "typescript", "react", "react-perf", "vitest"],
  options: {
    typeAware: true,
  },
  categories: {
    correctness: "error",
  },
  ignorePatterns: ["public/**", "vendor/**", "workbench/**"],
});
