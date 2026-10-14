import { createInertiaApp } from '@inertiajs/react';
import * as Inertia from '@inertiajs/react';
import * as React from 'react';
import * as JsxDevRuntime from 'react/jsx-dev-runtime';
import * as JsxRuntime from 'react/jsx-runtime';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.tsx');

type RegisteredPage = { default: unknown } | (() => Promise<{ default: unknown }>);

declare global {
    interface Window {
        LarasellAdmin: {
            React: typeof React;
            JsxRuntime: typeof JsxRuntime;
            JsxDevRuntime: typeof JsxDevRuntime;
            Inertia: typeof Inertia;
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
                    return page;
                }

                const loader = pages[`./Pages/${name}.tsx`];

                if (!loader) {
                    throw new Error(`Admin page [${name}] not found.`);
                }

                return loader();
            },
            setup({ el, App, props }) {
                createRoot(el).render(<App {...props} />);
            },
        });
    },
};
