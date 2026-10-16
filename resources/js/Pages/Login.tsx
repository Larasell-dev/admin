import { Form } from '@inertiajs/react';
import { Button, Container, Field, Input, Stack } from '@larasell-dev/ui';

export default function Login({ loginUrl }: { loginUrl: string }) {
    return (
        <main className="flex min-h-dvh items-center bg-white dark:bg-mauve-950">
            <Container>
                <div className="mx-auto w-full max-w-sm">
                    <Stack spacing={8}>
                        <Stack spacing={2}>
                            <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">Sign in</h1>
                            <p className="type-body-md text-mauve-600 dark:text-mauve-400">
                                Enter your credentials to access the admin panel.
                            </p>
                        </Stack>

                        <Form action={loginUrl} method="post" resetOnSuccess={['password']}>
                            {({ errors, processing }) => (
                                <Stack spacing={6}>
                                    <Stack spacing={5}>
                                        <Field label="Email" name="email" error={errors.email}>
                                            <Input type="email" autoComplete="username" />
                                        </Field>

                                        <Field label="Password" name="password" error={errors.password}>
                                            <Input type="password" autoComplete="current-password" />
                                        </Field>
                                    </Stack>

                                    <Button type="submit" loading={processing} DANGEROUS_className="w-full">
                                        Sign in
                                    </Button>
                                </Stack>
                            )}
                        </Form>
                    </Stack>
                </div>
            </Container>
        </main>
    );
}
