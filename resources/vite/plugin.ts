import path from 'node:path';
import { fileURLToPath } from 'node:url';
import type { Plugin } from 'vite';

export function admin(): Plugin {
    const runtime = path.resolve(path.dirname(fileURLToPath(import.meta.url)), 'runtime');

    const shims: Record<string, string> = {
        react: path.join(runtime, 'react.ts'),
        'react/jsx-runtime': path.join(runtime, 'jsx-runtime.ts'),
        'react/jsx-dev-runtime': path.join(runtime, 'jsx-dev-runtime.ts'),
        '@inertiajs/react': path.join(runtime, 'inertia.ts'),
        'larasell-admin/layouts': path.join(runtime, 'layouts.ts'),
        'larasell-admin/register': path.join(runtime, 'register-pages.ts'),
    };

    return {
        name: 'larasell-admin',
        enforce: 'pre',
        resolveId(source, importer) {
            if (
                importer === undefined ||
                !importer.includes(`${path.sep}resources${path.sep}js${path.sep}admin${path.sep}`)
            ) {
                return null;
            }

            return shims[source] ?? null;
        },
    };
}
