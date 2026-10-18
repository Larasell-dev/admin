import type { ComponentType } from 'react';

type AdminWindow = Window & {
  LarasellAdmin: {
    registerPages: (pages: Record<string, { default: ComponentType }>) => void;
  };
};

function add(pages: Record<string, ComponentType>): void;
function add(name: string, component: ComponentType): void;
function add(nameOrPages: string | Record<string, ComponentType>, component?: ComponentType): void {
  const pages = typeof nameOrPages === 'string' ? { [nameOrPages]: component as ComponentType } : nameOrPages;

  (window as unknown as AdminWindow).LarasellAdmin.registerPages(
    Object.fromEntries(Object.entries(pages).map(([name, page]) => [name, { default: page }])),
  );
}

export const page = { add };
