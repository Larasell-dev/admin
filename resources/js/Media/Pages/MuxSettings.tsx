import { Field as BaseField } from "@base-ui/react/field";
import { Form } from "@inertiajs/react";
import { Button, Checkbox, Field, Icon, Input, Section, Stack, useCopy } from "@larasell-dev/ui";
import { CheckLine, FileCopyLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import FormCheckbox from "../../Platform/Panel/Components/FormCheckbox";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";
import { useSettingsBackLink } from "../../Platform/Support/useSettingsBackLink";

type Props = { settings: Larasell.Admin.Media.Data.MuxSettingsData };

/** The hint under a secret's field: it's saved and stays unless something new is typed. */
function secretHint(saved: boolean): string | undefined {
  return saved
    ? fbs(
        "Saved. Leave empty to keep it.",
        "Mux settings page: hint below a secret's field when a secret is saved already, which the page never shows",
      )
    : undefined;
}

export default function MuxSettings({ settings }: Props) {
  const { copied, copy } = useCopy();

  return (
    <>
      <Page.Header
        backLink={useSettingsBackLink()}
        title={fbs("Mux", "Mux settings page: title, the name of the video service")}
      />
      <Page.Body>
        <Form
          action={settings.updateUrl}
          method="put"
          options={{ preserveScroll: true }}
          // Typed secrets are saved; empty fields keep them, so they never come back to the page.
          resetOnSuccess={["token_secret", "webhook_secret", "signing_key"]}
          setDefaultsOnSuccess
        >
          {({ errors }) => (
            <Stack spacing={8}>
              <Section.Root>
                <Section.Header
                  title={fbs(
                    "Access token",
                    "Mux settings page: heading of the section with the Mux API access token",
                  )}
                  description={fbs(
                    "Create one in Mux under Settings → Access tokens, with Mux Video read and write.",
                    "Mux settings page: advice below the access token heading on where to make one in Mux",
                  )}
                />
                <Stack spacing={5}>
                  <Field
                    label={fbs(
                      "Token ID",
                      "Mux settings page: label of the field for the access token's ID",
                    )}
                    name="token_id"
                    error={errors.token_id}
                  >
                    <Input defaultValue={settings.tokenId ?? ""} autoComplete="off" />
                  </Field>
                  <Field
                    label={fbs(
                      "Token secret",
                      "Mux settings page: label of the field for the access token's secret",
                    )}
                    name="token_secret"
                    error={errors.token_secret}
                    description={secretHint(settings.hasTokenSecret)}
                  >
                    <Input type="password" autoComplete="new-password" />
                  </Field>
                </Stack>
              </Section.Root>

              <Section.Root>
                <Section.Header
                  title={fbs(
                    "Webhook",
                    "Mux settings page: heading of the section about the webhook Mux calls when a video is ready",
                  )}
                  description={fbs(
                    "Add this URL as a webhook in Mux, so videos turn ready here once Mux finished them.",
                    "Mux settings page: advice below the webhook heading",
                  )}
                />
                <Stack spacing={5}>
                  <div className="flex items-center gap-2">
                    <code className="min-w-0 flex-1 border border-base-subtle bg-base-canvas p-3 type-mono-md break-all text-base-primary select-all">
                      {settings.webhookUrl}
                    </code>
                    <Button
                      variant="secondary"
                      size="sm"
                      onClick={() => void copy(settings.webhookUrl)}
                    >
                      {copied ? (
                        <fbt desc="Mux settings page: copy button's text right after it copied the webhook URL">
                          Copied
                        </fbt>
                      ) : (
                        <fbt desc="Mux settings page: button that copies the webhook URL to the clipboard">
                          Copy
                        </fbt>
                      )}
                      <Icon icon={copied ? CheckLine : FileCopyLine} />
                    </Button>
                  </div>
                  <Field
                    label={fbs(
                      "Signing secret",
                      "Mux settings page: label of the field for the secret Mux signs its webhooks with",
                    )}
                    name="webhook_secret"
                    error={errors.webhook_secret}
                    description={
                      secretHint(settings.hasWebhookSecret) ??
                      fbs(
                        "Mux shows it next to the webhook. Without it, webhooks are turned away.",
                        "Mux settings page: hint below the webhook signing secret field when none is saved",
                      )
                    }
                  >
                    <Input type="password" autoComplete="new-password" />
                  </Field>
                </Stack>
              </Section.Root>

              <Section.Root>
                <Section.Header
                  title={fbs(
                    "Signing key",
                    "Mux settings page: heading of the section with the key private videos are signed with",
                  )}
                  description={fbs(
                    "Private videos only play with signed links. Create a key in Mux under Settings → Signing keys.",
                    "Mux settings page: advice below the signing key heading",
                  )}
                />
                <Stack spacing={5}>
                  <Field
                    label={fbs(
                      "Key ID",
                      "Mux settings page: label of the field for the signing key's ID",
                    )}
                    name="signing_key_id"
                    error={errors.signing_key_id}
                    required="optional"
                  >
                    <Input defaultValue={settings.signingKeyId ?? ""} autoComplete="off" />
                  </Field>
                  <Field
                    label={fbs(
                      "Private key",
                      "Mux settings page: label of the field for the signing key's private key, as Mux hands it out",
                    )}
                    name="signing_key"
                    error={errors.signing_key}
                    required="optional"
                    description={secretHint(settings.hasSigningKey)}
                  >
                    <Input render={<textarea rows={4} spellCheck={false} autoComplete="off" />} />
                  </Field>
                </Stack>
              </Section.Root>

              <Section.Root>
                <Section.Header
                  title={fbs(
                    "Videos",
                    "Mux settings page: heading of the section on which videos go to Mux",
                  )}
                />
                <BaseField.Root name="use_for_videos" className="relative">
                  <Checkbox.Label>
                    <FormCheckbox
                      name="use_for_videos"
                      value="1"
                      uncheckedValue="0"
                      defaultChecked={settings.useForVideos}
                    />
                    <fbt desc="Mux settings page: label of the checkbox that sends videos uploaded from now on to Mux">
                      Upload new videos to Mux
                    </fbt>
                  </Checkbox.Label>
                </BaseField.Root>
              </Section.Root>
              <UnsavedChangesToast />
            </Stack>
          )}
        </Form>
      </Page.Body>
    </>
  );
}

MuxSettings.layout = [SidebarLayout];
