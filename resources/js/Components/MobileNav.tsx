import { Drawer } from '@base-ui/react/drawer';
import { Link, router, usePage } from '@inertiajs/react';
import { Container } from '@larasell-dev/ui';
import { useEffect, useState } from 'react';
import { arrayFirst, assertDefined } from 'ts-extras';
import type { Page } from '../types';
import Logo from './Logo';
import Sidebar from './Sidebar';

export default function MobileNav() {
    const { props } = usePage<Page>();
    const home = arrayFirst(props.navigation);
    assertDefined(home, 'The admin panel needs at least one navigation item.');
    const [open, setOpen] = useState(false);

    useEffect(() => router.on('success', () => setOpen(false)), []);

    return (
        <Drawer.Root open={open} onOpenChange={setOpen} swipeDirection="left">
            <header className="border-b border-mauve-200 bg-white dark:border-mauve-800 dark:bg-mauve-950">
                <Container DANGEROUS_className="flex items-center justify-between py-3">
                    <Link href={home.url} aria-label="Home" className="inline-block outline-none focus-visible:focus-ring">
                        <Logo variant="wordmark" className="h-6 w-auto text-mauve-950 dark:text-white" />
                    </Link>
                    <Drawer.Trigger
                        aria-label="Open menu"
                        className="-mr-1.5 inline-flex size-8 cursor-pointer items-center justify-center text-mauve-950 outline-none hover:bg-mauve-100 focus-visible:focus-ring dark:text-mauve-100 dark:hover:bg-mauve-800"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="size-5" aria-hidden="true">
                            <path strokeLinecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </Drawer.Trigger>
                </Container>
            </header>

            <Drawer.Portal>
                <Drawer.Backdrop className="fixed inset-0 z-40 bg-mauve-950/40 transition-opacity duration-300 data-ending-style:opacity-0 data-starting-style:opacity-0" />
                <Drawer.Viewport className="fixed inset-0 z-50 pointer-events-none">
                    {/* The sidebar extends a screen width to the left, so dragging the panel right keeps its background, border and highlight. */}
                    <Drawer.Popup
                        aria-label="Navigation"
                        className="pointer-events-auto h-full w-fit translate-x-(--drawer-swipe-movement-x) outline-none transition-transform duration-300 data-ending-style:-translate-x-full data-starting-style:-translate-x-full data-swiping:duration-0 [&_aside]:-ml-[100vw] [&_aside]:w-[calc(100vw+16rem)] [&_aside>div]:pl-[calc(100vw+1.5rem)] [&_nav_a]:pl-[calc(100vw+1.5rem)]"
                    >
                        <Sidebar />
                    </Drawer.Popup>
                </Drawer.Viewport>
            </Drawer.Portal>
        </Drawer.Root>
    );
}
