import { Form } from "@inertiajs/react";
import { Field, Input, Section, Stack } from "@larasell-dev/ui";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.ApiTokens.CreateApiTokenPageData;

export default function Create({ storeUrl, indexUrl, expiryOptions, defaultExpiresIn }: Props) {
  return (
    <>
      <Page.Header
        title="New API token"
        backLink={{ href: indexUrl, label: "Back to API tokens" }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          {({ errors }) => (
            <>
              <Section.Root>
                <Section.Header
                  title="General"
                  description="Create one token per app that calls the API, so each can be revoked on its own."
                />
                <Stack spacing={5}>
                  <Field label="Name" name="name" error={errors.name}>
                    <Input autoComplete="off" />
                  </Field>
                  <Field
                    label="Description"
                    name="description"
                    error={errors.description}
                    required="optional"
                  >
                    <Input autoComplete="off" />
                  </Field>
                  <Field label="Expires" name="expires_in" error={errors.expires_in}>
                    {/* The UI has no select yet; Input renders a native one with the field's label and styles. */}
                    <Input
                      defaultValue={defaultExpiresIn}
                      render={
                        <select>
                          {expiryOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                              {option.label}
                            </option>
                          ))}
                        </select>
                      }
                    />
                  </Field>
                </Stack>
              </Section.Root>
              <UnsavedChangesToast />
            </>
          )}
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
