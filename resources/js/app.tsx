import '../css/app.css';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import * as Inertia from '@inertiajs/react';
import * as UI from '@larasell-dev/ui';
import * as React from 'react';
import * as JsxDevRuntime from 'react/jsx-dev-runtime';
import * as JsxRuntime from 'react/jsx-runtime';
import { createRoot } from 'react-dom/client';
import SidebarLayout from './Layouts/SidebarLayout';

type PageModule = { default: ResolvedComponent };

type RegisteredPage = PageModule | (() => Promise<PageModule>);

const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx');

declare global {
    interface Window {
        LarasellAdmin: {
            React: typeof React;
            JsxRuntime: typeof JsxRuntime;
            JsxDevRuntime: typeof JsxDevRuntime;
            Inertia: typeof Inertia;
            UI: typeof UI;
            Layouts: {
                SidebarLayout: typeof SidebarLayout;
            };
            pages: Record<string, RegisteredPage>;
            registerPages: (pages: Record<string, RegisteredPage>) => void;
            boot: () => void;
        };
    }
}

const registered: Record<string, RegisteredPage> = {};

window.LarasellAdmin = {
    React,
    JsxRuntime,
    JsxDevRuntime,
    Inertia,
    UI,
    Layouts: {
        SidebarLayout,
    },
    pages: registered,
    registerPages(next) {
        Object.assign(registered, next);
    },
    boot() {
        void createInertiaApp({
            title: (title) => (title ? `${title} - Admin` : 'Admin'),
            resolve: async (name) => {
                const page = registered[name];

                if (page) {
                    const module = typeof page === 'function' ? await page() : page;

                    return module.default;
                }

                const loader = pages[`./Pages/${name}.tsx`];

                if (!loader) {
                    throw new Error(`Admin page [${name}] not found.`);
                }

                return (await loader()).default;
            },
            progress: {
                color: 'var(--color-progress)',
            },
            setup({ el, App, props }) {
                createRoot(el).render(<App {...props} />);
            },
        });
    },
};
