import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { playwright } from "@vitest/browser-playwright";
import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    projects: [
      {
        plugins: [react(), tailwindcss()],
        test: {
          name: "frontend",
          include: ["resources/js/**/*.test.{ts,tsx}"],
          browser: {
            enabled: true,
            provider: playwright(),
            headless: true,
            instances: [{ browser: "chromium" }],
          },
        },
      },
      {
        // The admin Vite plugin runs in Node, where extensions build with it.
        test: {
          name: "vite",
          include: ["resources/vite/**/*.test.ts"],
          environment: "node",
        },
      },
      {
        // Oxlint's RuleTester runs the linter natively, so these tests run in Node.
        test: {
          name: "lint",
          include: ["lint/**/*.test.ts"],
          environment: "node",
        },
      },
    ],
  },
});
