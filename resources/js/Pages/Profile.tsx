import { Avatar } from "@base-ui/react/avatar";
import { Field as BaseField } from "@base-ui/react/field";
import { Radio } from "@base-ui/react/radio";
import { RadioGroup } from "@base-ui/react/radio-group";
import { Form, router, useFormContext, useHttp, usePage } from "@inertiajs/react";
import {
  Button,
  Container,
  Error,
  Field,
  Input,
  Section,
  SectionStack,
  Stack,
} from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { useEffect, useId, useRef, useState } from "react";
import { assertDefined } from "ts-extras";
import UnsavedChangesToast, { useUnsavedChanges } from "../Components/UnsavedChangesToast";
import SidebarLayout from "../Layouts/SidebarLayout";
import { initials } from "../utils/initials";
import { setTheme, Theme, themeLabel, useTheme } from "../utils/theme";
import type { Page } from "../types";

type ProfilePageProps = Larasell.Admin.Team.Data.ProfilePageData;

export default function Profile({ admin, updateUrl }: ProfilePageProps) {
  return (
    <Container>
      <div className="max-w-lg py-10">
        <Stack spacing={8}>
          <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">
            <fbt desc="Profile page: heading of the page where the admin edits their own account">
              Profile
            </fbt>
          </h1>

          <SectionStack>
            <Form
              key={admin.avatarUrl ?? "none"}
              action={updateUrl}
              method="put"
              setDefaultsOnSuccess
              className="max-w-sm"
            >
              <Section.Root>
                <Section.Header
                  title={fbs(
                    "General",
                    "Profile page: heading of the section with the admin's name, email address and picture",
                  )}
                  description={fbs(
                    "Your name, email address, and profile picture.",
                    "Profile page: explains what the General section holds",
                  )}
                />
                <ProfileFields />
              </Section.Root>
              <UnsavedChangesToast />
            </Form>
            <PreferenceSection />
          </SectionStack>
        </Stack>
      </div>
    </Container>
  );
}

function ProfileFields() {
  const { admin } = usePage<Page<ProfilePageProps>>().props;
  const errors = useFormContext()?.errors ?? {};

  return (
    <Stack spacing={6}>
      <AvatarInput error={errors.avatar} />

      <Stack spacing={5}>
        <Field
          label={fbs("Name", "Profile page: label of the field for the admin's own full name")}
          name="name"
          error={errors.name}
        >
          <Input defaultValue={admin.name} autoComplete="name" />
        </Field>

        <Field
          label={fbs("Email", "Profile page: label of the field for the admin's own email address")}
          name="email"
          error={errors.email}
        >
          <Input type="email" defaultValue={admin.email} autoComplete="email" />
        </Field>
      </Stack>
    </Stack>
  );
}

Profile.layout = [SidebarLayout];

type AvatarChange =
  | { type: "unchanged" }
  | { type: "uploaded"; path: string; url: string }
  | { type: "removed" };

function AvatarInput({ error }: { error?: string }) {
  const { admin, avatarUploadUrl } = usePage<Page<ProfilePageProps>>().props;

  const fileInput = useRef<HTMLInputElement>(null);
  const upload = useHttp<{ avatar: File | null }, { path: string; url: string }>({ avatar: null });

  const [change, setChange] = useState<AvatarChange>({ type: "unchanged" });
  const currentUrl =
    change.type === "uploaded" ? change.url : change.type === "removed" ? null : admin.avatarUrl;

  const avatarError = upload.errors.avatar ?? error;
  const avatarPath = useRef<HTMLInputElement>(null);
  const cancelUpload = upload.cancel;
  const clearUploadErrors = upload.clearErrors;

  useEffect(() => {
    avatarPath.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [change]);

  // Once the profile is saved, a rejected picture no longer matters.
  useEffect(() => router.on("success", () => clearUploadErrors()), [clearUploadErrors]);

  // Cancelling the changes also stops an upload, so its picture doesn't come back afterwards.
  useEffect(() => {
    const form = avatarPath.current?.form;
    if (!form) return;
    const discard = () => {
      cancelUpload();
      clearUploadErrors();
      setChange({ type: "unchanged" });
    };
    form.addEventListener("reset", discard);
    return () => form.removeEventListener("reset", discard);
  }, [cancelUpload, clearUploadErrors]);

  // Stops the upload when the page goes away.
  useEffect(() => () => cancelUpload(), [cancelUpload]);

  // The file is stored right away; saving the form is what assigns it to the profile.
  async function selectFile(file: File | undefined) {
    assertDefined(file, "A file must be selected to upload an avatar.");

    const failed = (status?: number) =>
      upload.setError(
        "avatar",
        status === 413
          ? fbs(
              "The picture is larger than the server accepts.",
              "Profile page: error after uploading a profile picture whose file is too large",
            )
          : fbs(
              "The picture couldn't be uploaded. Try again.",
              "Profile page: error after a profile picture upload failed, e.g. without a network",
            ),
      );
    upload.transform(() => ({ avatar: file }));

    try {
      // Undefined when the picture is invalid; upload.errors says why.
      const uploaded = await upload.post(avatarUploadUrl, {
        onHttpException: ({ status }) => failed(status),
        onNetworkError: () => failed(),
      });
      if (uploaded) setChange({ type: "uploaded", ...uploaded });
    } catch {
      // Said why above, unless the upload was cancelled, which needs no reason.
    } finally {
      if (fileInput.current) fileInput.current.value = "";
    }
  }

  function removeAvatar() {
    upload.clearErrors();
    setChange({ type: "removed" });
  }

  return (
    <BaseField.Root name="avatar" invalid={!!avatarError} className="flex flex-col gap-1.5">
      <input
        ref={avatarPath}
        type="hidden"
        name="avatar"
        value={change.type === "uploaded" ? change.path : ""}
      />
      <input type="hidden" name="remove_avatar" value={change.type === "removed" ? "1" : "0"} />

      <div className="flex items-center gap-4" aria-busy={upload.processing}>
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
        <Button
          type="button"
          variant="secondary"
          loading={upload.processing}
          onClick={() => fileInput.current?.click()}
        >
          <fbt desc="Profile page: button that opens the file picker for a new profile picture">
            Change picture
          </fbt>
        </Button>
        {currentUrl && (
          <Button type="button" variant="tertiary" onClick={removeAvatar}>
            <fbt desc="Profile page: button that removes the admin's profile picture">Remove</fbt>
          </Button>
        )}
      </div>
      {avatarError && <Error match>{avatarError}</Error>}
    </BaseField.Root>
  );
}

function PreferenceSection() {
  const theme = useTheme();

  return (
    <Section.Root>
      <Section.Header
        title={fbs(
          "Preference",
          "Profile page: heading of the section with the theme and the interface language",
        )}
        description={fbs(
          "Choose how the admin panel looks on this device.",
          "Profile page: explains what the Preference section holds; the choices only apply to this browser",
        )}
      />
      <RadioGroup
        aria-label={fbs(
          "Theme",
          "Profile page: accessible name of the group of light, dark and system theme choices",
        )}
        value={theme}
        onValueChange={setTheme}
        className="grid grid-cols-2 gap-8 sm:grid-cols-3"
      >
        {Object.values(Theme).map((value) => (
          <Radio.Root
            key={value}
            value={value}
            className="group flex cursor-pointer flex-col gap-2 outline-none select-none"
          >
            <ThemePreview theme={value} />
            <span className="flex items-center gap-2 type-label-md text-mauve-950 dark:text-mauve-50">
              <span className="inline-flex size-4 shrink-0 items-center justify-center rounded-full border border-border bg-white group-data-checked:border-mauve-950 group-data-checked:bg-mauve-950 dark:bg-mauve-900 dark:group-data-checked:border-mauve-100 dark:group-data-checked:bg-mauve-100">
                <Radio.Indicator className="size-1.5 rounded-full bg-white dark:bg-mauve-950" />
              </span>
              {themeLabel(value)}
            </span>
          </Radio.Root>
        ))}
      </RadioGroup>
      <InterfaceLocaleField />
    </Section.Root>
  );
}

/**
 * Picks the language of the panel itself, not of the content, like the command menu does. The
 * server reloads the page in it, so it is disabled while the profile has unsaved changes.
 */
function InterfaceLocaleField() {
  const { interfaceLocale } = usePage<Page<ProfilePageProps>>().props;
  const unsavedChanges = useUnsavedChanges();

  if (!interfaceLocale || interfaceLocale.options.length < 2) return null;

  return (
    <div className="max-w-sm pt-8">
      <Field
        label={fbs(
          "Language",
          "Profile page: label of the select for the language the admin panel's own text is shown in",
        )}
        name="interface_locale"
        description={
          unsavedChanges
            ? fbs(
                "Save or discard your changes first",
                "Profile page: why the language select is disabled while the profile has unsaved changes",
              )
            : undefined
        }
      >
        <Input
          defaultValue={interfaceLocale.current}
          disabled={unsavedChanges}
          render={
            // On the select, which types its own change events; Input types them for an input.
            <select
              onChange={(event) =>
                router.put(interfaceLocale.switchUrl, { locale: event.currentTarget.value })
              }
            >
              {interfaceLocale.options.map((option) => (
                <option key={option.value} value={option.value} lang={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          }
        />
      </Field>
    </div>
  );
}

const previewColors = {
  [Theme.Light]: {
    background: "var(--color-white)",
    sidebar: "var(--color-mauve-100)",
    line: "var(--color-mauve-300)",
    card: "var(--color-mauve-50)",
    border: "var(--color-mauve-200)",
  },
  [Theme.Dark]: {
    background: "var(--color-mauve-950)",
    sidebar: "var(--color-mauve-900)",
    line: "var(--color-mauve-700)",
    card: "var(--color-mauve-900)",
    border: "var(--color-mauve-800)",
  },
};

/** A small sketch of the admin panel in the theme's colors; system shows light and dark halves. */
function ThemePreview({ theme }: { theme: Theme }) {
  const clipId = useId();

  return (
    <svg
      viewBox="0 0 120 80"
      aria-hidden
      className="block h-auto w-full group-focus-visible:focus-ring"
    >
      {theme === Theme.System ? (
        <>
          <defs>
            <clipPath id={clipId}>
              <rect x="60" y="0" width="60" height="80" />
            </clipPath>
          </defs>
          <PreviewUi colors={previewColors[Theme.Light]} />
          <g clipPath={`url(#${clipId})`}>
            <PreviewUi colors={previewColors[Theme.Dark]} />
          </g>
        </>
      ) : (
        <PreviewUi colors={previewColors[theme]} />
      )}
    </svg>
  );
}

function PreviewUi({ colors }: { colors: (typeof previewColors)[keyof typeof previewColors] }) {
  return (
    <>
      <rect width="120" height="80" fill={colors.background} />
      <rect width="30" height="80" fill={colors.sidebar} />
      <rect x="6" y="8" width="10" height="4" fill="var(--color-primary-500)" />
      <rect x="6" y="20" width="18" height="3" fill={colors.line} />
      <rect x="6" y="28" width="14" height="3" fill={colors.line} />
      <rect x="6" y="36" width="16" height="3" fill={colors.line} />
      <rect x="38" y="8" width="40" height="5" fill={colors.line} />
      <rect x="38.5" y="20.5" width="73" height="51" fill={colors.card} stroke={colors.border} />
      <rect x="44" y="28" width="36" height="3" fill={colors.line} />
      <rect x="44" y="36" width="56" height="3" fill={colors.line} />
      <rect x="44" y="44" width="48" height="3" fill={colors.line} />
      <rect x="44" y="58" width="16" height="6" fill="var(--color-primary-500)" />
    </>
  );
}
