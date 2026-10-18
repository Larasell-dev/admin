import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.tsx'],
      publicDirectory: 'public',
      buildDirectory: 'vendor/larasell-admin',
      hotFile: 'public/hot',
      fonts: [
        bunny('Inter', {
          alias: 'sans',
          weights: [400, 500],
        }),
        bunny('Geist', {
          alias: 'heading',
          weights: [500, 600],
        }),
        bunny('JetBrains Mono', {
          alias: 'mono',
          weights: [400],
        }),
      ],
    }),
    react(),
    tailwindcss(),
  ],
});
