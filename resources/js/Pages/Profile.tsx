import { Form } from '@inertiajs/react';
import { Button, Container, Field, Input, Stack } from '@larasell-dev/ui';
import SidebarLayout from '../Layouts/SidebarLayout';

export default function Profile({
    admin,
    updateUrl,
}: {
    admin: { name: string; email: string };
    updateUrl: string;
}) {
    return (
        <Container>
            <div className="max-w-sm py-10">
                <Stack spacing={8}>
                    <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">Profile</h1>

                    <Form action={updateUrl} method="put">
                        {({ errors, processing, recentlySuccessful }) => (
                            <Stack spacing={6}>
                                <Stack spacing={5}>
                                    <Field label="Name" name="name" error={errors.name}>
                                        <Input defaultValue={admin.name} autoComplete="name" />
                                    </Field>

                                    <Field label="Email" name="email" error={errors.email}>
                                        <Input type="email" defaultValue={admin.email} autoComplete="email" />
                                    </Field>
                                </Stack>

                                <Stack spacing={2}>
                                    <Button type="submit" loading={processing}>
                                        Save
                                    </Button>
                                    {recentlySuccessful && (
                                        <p className="type-body-sm text-mauve-600 dark:text-mauve-400">Saved.</p>
                                    )}
                                </Stack>
                            </Stack>
                        )}
                    </Form>
                </Stack>
            </div>
        </Container>
    );
}

Profile.layout = [SidebarLayout];
