import fbtee from "@nkzw/vite-plugin-fbtee";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { playwright } from "@vitest/browser-playwright";
import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    projects: [
      {
        plugins: [fbtee(), react(), tailwindcss()],
        test: {
          name: "frontend",
          include: ["resources/js/**/*.test.{ts,tsx}"],
          setupFiles: ["resources/js/vitest.setup.ts"],
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
    ],
  },
});
