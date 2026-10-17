import type { ComponentType, ReactNode } from 'react';

type AdminWindow = Window & {
    LarasellAdmin: {
        Layouts: {
            SidebarLayout: ComponentType<{ children: ReactNode }>;
        };
    };
};

export const SidebarLayout = (window as unknown as AdminWindow).LarasellAdmin.Layouts.SidebarLayout;
