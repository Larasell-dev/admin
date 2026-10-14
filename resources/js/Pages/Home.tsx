import { Form, Link, usePage } from '@inertiajs/react';

export default function Home({
    admin,
    logoutUrl,
}: {
    admin: { name: string; email: string };
    logoutUrl: string;
}) {
    const { navigation } = usePage<{
        navigation: { label: string; url: string }[];
    }>().props;

    return (
        <main>
            <h1>Admin</h1>
            <nav>
                {navigation.map((item) => (
                    <Link key={item.url} href={item.url}>
                        {item.label}
                    </Link>
                ))}
            </nav>
            <p>
                {admin.name} ({admin.email})
            </p>

            <Form action={logoutUrl} method="post">
                <button type="submit">Log out</button>
            </Form>
        </main>
    );
}
