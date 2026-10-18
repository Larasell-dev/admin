import type { ReactNode } from 'react';
import MobileNav from '../Components/MobileNav';
import Sidebar from '../Components/Sidebar';
import { SkipLink } from '../Components/SkipLink';

export default function SidebarLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-dvh flex-col bg-white lg:flex-row dark:bg-mauve-950">
      <SkipLink />
      <div className="sticky top-0 z-10 lg:hidden">
        <MobileNav />
      </div>
      <div className="sticky top-0 hidden h-dvh lg:block">
        <Sidebar />
      </div>
      <main id="main-content" tabIndex={-1} className="min-w-0 flex-1 focus:outline-none">
        {children}
      </main>
    </div>
  );
}
