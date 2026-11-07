import { Form } from "@inertiajs/react";
import { Field, Input, Section, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import { Page } from "../../Platform/Panel/Components/Page";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.ApiToken.Data.CreateApiTokenPageData;

export default function Create({ storeUrl, indexUrl, expiryOptions, defaultExpiresIn }: Props) {
  return (
    <>
      <Page.Header
        title={fbs(
          "New API token",
          "New API token page: title of the page with the form that creates a token for apps calling the store's API",
        )}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to API tokens",
            "New API token page: accessible name of the back arrow that returns to the list of API tokens",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          {({ errors }) => (
            <>
              <Section.Root>
                <Section.Header
                  title={fbs(
                    "General",
                    "New API token page: heading of the form section with the token's name, description and expiry",
                  )}
                  description={fbs(
                    "Create one token per app that calls the API, so each can be revoked on its own.",
                    "New API token page: advice below the heading of the form that creates a token",
                  )}
                />
                <Stack spacing={5}>
                  <Field
                    label={fbs(
                      "Name",
                      "New API token page: label of the field for the token's name, which tells tokens apart",
                    )}
                    name="name"
                    error={errors.name}
                  >
                    <Input autoComplete="off" />
                  </Field>
                  <Field
                    label={fbs(
                      "Description",
                      "New API token page: label of the optional field for a note on what the token is for",
                    )}
                    name="description"
                    error={errors.description}
                    required="optional"
                  >
                    <Input autoComplete="off" />
                  </Field>
                  <Field
                    label={fbs(
                      "Expires",
                      "New API token page: label of the select for how long the token stays valid",
                    )}
                    name="expires_in"
                    error={errors.expires_in}
                  >
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
