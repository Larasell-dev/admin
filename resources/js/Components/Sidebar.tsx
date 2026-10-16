import { Link, usePage } from '@inertiajs/react';
import { arrayFirst, assertDefined } from 'ts-extras';
import type { Page } from '../types';
import Logo from './Logo';
import UserMenu from './UserMenu';

export default function Sidebar() {
    const { props, url } = usePage<Page>();
    const currentPath = url.split('?')[0];
    const home = arrayFirst(props.navigation);
    assertDefined(home, 'The admin panel needs at least one navigation item.');

    return (
        <aside className="flex h-dvh w-64 shrink-0 flex-col border-r border-mauve-200 bg-white dark:border-mauve-800 dark:bg-mauve-950">
            <div className="flex items-center border-b border-mauve-200 px-6 py-4 dark:border-mauve-800">
                <Link href={home.url} aria-label="Home" className="inline-block outline-none focus-visible:focus-ring">
                    <Logo variant="wordmark" className="h-7 w-auto text-mauve-950 dark:text-white" />
                </Link>
            </div>

            <nav className="flex flex-1 flex-col overflow-y-auto pb-3">
                {props.navigation.map((item) => {
                    const active = new URL(item.url, window.location.origin).pathname === currentPath;

                    return (
                        <Link
                            key={item.url}
                            href={item.url}
                            aria-current={active ? 'page' : undefined}
                            className={`type-body-md px-6 py-2 outline-none focus-visible:focus-ring focus-visible:-outline-offset-2 ${
                                active
                                    ? 'bg-mauve-100 text-mauve-950 dark:bg-mauve-900 dark:text-mauve-50'
                                    : 'text-mauve-600 hover:bg-mauve-50 hover:text-mauve-950 dark:text-mauve-400 dark:hover:bg-mauve-900 dark:hover:text-mauve-50'
                            }`}
                        >
                            {item.label}
                        </Link>
                    );
                })}
            </nav>

            <UserMenu />
        </aside>
    );
}
