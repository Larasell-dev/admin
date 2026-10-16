import { Avatar } from '@base-ui/react/avatar';
import { Field as BaseField } from '@base-ui/react/field';
import { Form, useHttp, usePage } from '@inertiajs/react';
import { Button, Container, Error, Field, Input, Stack } from '@larasell-dev/ui';
import { useRef, useState } from 'react';
import { assertDefined } from 'ts-extras';
import SidebarLayout from '../Layouts/SidebarLayout';
import type { Page, ProfilePageProps } from '../types';

export default function Profile({ admin, updateUrl }: ProfilePageProps) {
    const [avatarKey, setAvatarKey] = useState(0);

    return (
        <Container>
            <div className="max-w-sm py-10">
                <Stack spacing={8}>
                    <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">Profile</h1>

                    <Form
                        action={updateUrl}
                        method="put"
                        // Remounting the avatar input clears its selection once the upload is saved.
                        onSuccess={() => setAvatarKey((key) => key + 1)}
                    >
                        {({ errors, processing, recentlySuccessful }) => (
                            <Stack spacing={6}>
                                <AvatarInput
                                    key={avatarKey}
                                    error={errors.avatar}
                                />

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

type AvatarChange =
    | { type: 'unchanged' }
    | { type: 'uploaded'; path: string; url: string }
    | { type: 'removed' };

function AvatarInput({ error }: { error?: string }) {
    const { admin, avatarUploadUrl } = usePage<Page<ProfilePageProps>>().props;

    const fileInput = useRef<HTMLInputElement>(null);
    const upload = useHttp<{ avatar: File | null }, { path: string; url: string }>({ avatar: null });

    const [change, setChange] = useState<AvatarChange>({ type: 'unchanged' });
    const currentUrl =
        change.type === 'uploaded' ? change.url : change.type === 'removed' ? null : admin.avatarUrl;

    const avatarError = upload.errors.avatar ?? error;

    // The file is stored right away; saving the form is what assigns it to the profile.
    async function selectFile(file: File | undefined) {
        assertDefined(file, 'A file must be selected to upload an avatar.');

        upload.transform(() => ({ avatar: file }));

        try {
            setChange({ type: 'uploaded', ...(await upload.post(avatarUploadUrl)) });
        } catch {
            // Validation errors are shown from upload.errors.
        } finally {
            if (fileInput.current) fileInput.current.value = '';
        }
    }

    function removeAvatar() {
        upload.clearErrors();
        setChange({ type: 'removed' });
    }

    return (
        <BaseField.Root name="avatar" invalid={!!avatarError} className="flex flex-col gap-1.5">
            <input type="hidden" name="avatar" value={change.type === 'uploaded' ? change.path : ''} />
            <input type="hidden" name="remove_avatar" value={change.type === 'removed' ? '1' : '0'} />

            <div className="flex items-center gap-4">
                <Avatar.Root className="inline-flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mauve-100 type-body-md text-mauve-700 dark:bg-mauve-800 dark:text-mauve-200">
                    {currentUrl && (
                        <Avatar.Image src={currentUrl} alt="" className="size-full object-cover" />
                    )}
                    <Avatar.Fallback>{initials(admin.name)}</Avatar.Fallback>
                </Avatar.Root>

                <input
                    ref={fileInput}
                    id="avatar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="sr-only"
                    onChange={(event) => selectFile(event.target.files?.[0])}
                />
                <Button type="button" variant="secondary" loading={upload.processing} onClick={() => fileInput.current?.click()}>
                    Change picture
                </Button>
                {currentUrl && (
                    <Button type="button" variant="tertiary" onClick={removeAvatar}>
                        Remove
                    </Button>
                )}
            </div>
            {avatarError && <Error match>{avatarError}</Error>}
        </BaseField.Root>
    );
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}
