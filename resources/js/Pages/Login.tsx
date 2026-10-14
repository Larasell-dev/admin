import { Form } from '@inertiajs/react';

export default function Login({ loginUrl }: { loginUrl: string }) {
    return (
        <main>
            <h1>Sign in</h1>

            <Form action={loginUrl} method="post" resetOnSuccess={['password']}>
                {({ errors, processing }) => (
                    <>
                        <label htmlFor="email">Email</label>
                        <input id="email" type="email" name="email" autoComplete="username" />
                        {errors.email && <p>{errors.email}</p>}

                        <label htmlFor="password">Password</label>
                        <input id="password" type="password" name="password" autoComplete="current-password" />
                        {errors.password && <p>{errors.password}</p>}

                        <button type="submit" disabled={processing}>
                            Sign in
                        </button>
                    </>
                )}
            </Form>
        </main>
    );
}
