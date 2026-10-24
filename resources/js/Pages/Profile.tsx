import { Avatar } from "@base-ui/react/avatar";
import { Field as BaseField } from "@base-ui/react/field";
import { Radio } from "@base-ui/react/radio";
import { RadioGroup } from "@base-ui/react/radio-group";
import { Form, useFormContext, useHttp, usePage } from "@inertiajs/react";
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
import { useEffect, useId, useRef, useState } from "react";
import { assertDefined } from "ts-extras";
import UnsavedChangesToast from "../Components/UnsavedChangesToast";
import SidebarLayout from "../Layouts/SidebarLayout";
import { initials } from "../utils/initials";
import { setTheme, storedTheme, type Theme } from "../utils/theme";
import type { Page } from "../types";

type ProfilePageProps = Larasell.Admin.Data.View.ProfilePageData;

export default function Profile({ admin, updateUrl }: ProfilePageProps) {
  return (
    <Container>
      <div className="max-w-lg py-10">
        <Stack spacing={8}>
          <h1 className="type-headline-lg text-mauve-950 dark:text-mauve-50">Profile</h1>

          <SectionStack>
            <Form
              key={admin.avatarUrl ?? "none"}
              action={updateUrl}
              method="put"
              setDefaultsOnSuccess
              className="max-w-sm"
            >
              <ProfileFields />
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
        <Field label="Name" name="name" error={errors.name}>
          <Input defaultValue={admin.name} autoComplete="name" />
        </Field>

        <Field label="Email" name="email" error={errors.email}>
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

  useEffect(() => {
    avatarPath.current?.dispatchEvent(new Event("input", { bubbles: true }));
  }, [change]);

  useEffect(() => {
    const form = avatarPath.current?.form;
    if (!form) return;
    const discard = () => {
      upload.clearErrors();
      setChange({ type: "unchanged" });
    };
    form.addEventListener("reset", discard);
    return () => form.removeEventListener("reset", discard);
  }, [upload]);

  // The file is stored right away; saving the form is what assigns it to the profile.
  async function selectFile(file: File | undefined) {
    assertDefined(file, "A file must be selected to upload an avatar.");

    upload.transform(() => ({ avatar: file }));

    try {
      setChange({ type: "uploaded", ...(await upload.post(avatarUploadUrl)) });
    } catch {
      // Validation errors are shown from upload.errors.
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
        <Button
          type="button"
          variant="secondary"
          loading={upload.processing}
          onClick={() => fileInput.current?.click()}
        >
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

const themes: { value: Theme; label: string }[] = [
  { value: "light", label: "Light" },
  { value: "dark", label: "Dark" },
  { value: "system", label: "System" },
];

function PreferenceSection() {
  const [theme, setThemeState] = useState<Theme>(storedTheme);

  function selectTheme(value: Theme) {
    setTheme(value);
    setThemeState(value);
  }

  return (
    <Section.Root>
      <Section.Header
        title="Preference"
        description="Choose how the admin panel looks on this device."
      />
      <RadioGroup
        aria-label="Theme"
        value={theme}
        onValueChange={selectTheme}
        className="grid grid-cols-2 gap-8 sm:grid-cols-3"
      >
        {themes.map(({ value, label }) => (
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
              {label}
            </span>
          </Radio.Root>
        ))}
      </RadioGroup>
    </Section.Root>
  );
}

const previewColors = {
  light: {
    background: "var(--color-white)",
    sidebar: "var(--color-mauve-100)",
    line: "var(--color-mauve-300)",
    card: "var(--color-mauve-50)",
    border: "var(--color-mauve-200)",
  },
  dark: {
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
      {theme === "system" ? (
        <>
          <defs>
            <clipPath id={clipId}>
              <rect x="60" y="0" width="60" height="80" />
            </clipPath>
          </defs>
          <PreviewUi colors={previewColors.light} />
          <g clipPath={`url(#${clipId})`}>
            <PreviewUi colors={previewColors.dark} />
          </g>
        </>
      ) : (
        <PreviewUi colors={previewColors[theme]} />
      )}
    </svg>
  );
}

function PreviewUi({ colors }: { colors: (typeof previewColors)["light"] }) {
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
