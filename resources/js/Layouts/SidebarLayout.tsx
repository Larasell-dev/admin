import type { ReactNode } from 'react';
import Sidebar from '../Components/Sidebar';

export default function SidebarLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-dvh bg-white dark:bg-mauve-950">
            <div className="sticky top-0 h-dvh">
                <Sidebar />
            </div>
            <main className="min-w-0 flex-1">{children}</main>
        </div>
    );
}
