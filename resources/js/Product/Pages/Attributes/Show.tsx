import { Form } from "@inertiajs/react";
import { Button, Icon, Section, SectionStack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import AttributeForm from "../../Components/AttributeForm";
import AttributeValues from "../../Components/AttributeValues";
import ContentLocaleSelect from "../../../Platform/ContentLocale/Components/ContentLocaleSelect";
import ConfirmDialog from "../../../Platform/Panel/Components/ConfirmDialog";
import { Page } from "../../../Platform/Panel/Components/Page";
import PersistentAlertDialog from "../../../Platform/Panel/Components/PersistentAlertDialog";
import UnsavedChangesToast from "../../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";
import type { SharedProps } from "../../../types";

type Props = Larasell.Admin.Product.Data.AttributePageData & Pick<SharedProps, "contentLocale">;

/** "34 products and 1 variant" style usage, for the delete dialogs. */
function usage(products: number, variants: number): string {
  return fbs(
    [
      fbs.plural("product", products, { many: "products", showCount: "yes", name: "products" }),
      " and ",
      fbs.plural("variant", variants, { many: "variants", showCount: "yes", name: "variants" }),
    ],
    "Attribute page: how many products and variants use an attribute or value, inside a delete dialog's text",
  );
}

export default function Show(props: Props) {
  const { attribute, values, scopes, updateUrl, destroyUrl, indexUrl } = props;

  return (
    <>
      <Page.Header
        title={attribute.name ?? attribute.fallbackName ?? ""}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to attributes",
            "Attribute page: accessible name of the back arrow to the list of attributes",
          ),
        }}
        actions={<ContentLocaleSelect contentLocale={props.contentLocale} />}
      />
      <Page.Body>
        <SectionStack>
          <Form
            // Remounts with the new values when the language changes.
            key={`${attribute.id}-${props.contentLocale?.current}`}
            action={updateUrl}
            method="put"
            setDefaultsOnSuccess
            options={{ preserveScroll: true }}
          >
            <SectionStack>
              <Section.Root>
                <Section.Header
                  title={fbs("General", "Attribute page: heading of the attribute's settings")}
                  description={fbs(
                    "How the attribute is named, and what it describes.",
                    "Attribute page: explains the section with the attribute's settings",
                  )}
                />
                <AttributeForm attribute={attribute} scopes={scopes} />
              </Section.Root>
              <AttributeValues
                // Starts again from what was saved once the server sends other values.
                key={values.map((value) => `${value.id}:${value.slug}:${value.name}`).join()}
                values={values}
                type={attribute.type}
              />
            </SectionStack>
            <UnsavedChangesToast />
          </Form>
          <Section.Root>
            <Section.Header
              title={fbs("Delete attribute", "Attribute page: heading of the delete section")}
              description={fbs(
                "Its values are deleted with it, and products and variants lose them.",
                "Attribute page: explains the section with the button that deletes the attribute",
              )}
            />
            <div>
              <PersistentAlertDialog.Root>
                <PersistentAlertDialog.Trigger render={<Button variant="destructive" />}>
                  <Icon icon={DeleteBinLine} />
                  <fbt desc="Attribute page: button that asks to delete the attribute">Delete</fbt>
                </PersistentAlertDialog.Trigger>
                <ConfirmDialog.Popup
                  title={fbs("Delete attribute", "Attribute page: title of the delete dialog")}
                  description={
                    attribute.products + attribute.variants > 0
                      ? fbs(
                          [
                            fbs.param("name", attribute.name ?? attribute.fallbackName ?? ""),
                            " is used by ",
                            fbs.param("usage", usage(attribute.products, attribute.variants)),
                            ". They lose its values. This can't be undone.",
                          ],
                          "Attribute page: delete dialog text for an attribute in use",
                        )
                      : fbs(
                          [
                            "No product or variant uses ",
                            fbs.param("name", attribute.name ?? attribute.fallbackName ?? ""),
                            ". This can't be undone.",
                          ],
                          "Attribute page: delete dialog text for an unused attribute",
                        )
                  }
                  action={destroyUrl}
                  // Typed only while it's in use; the server checks it too.
                  confirmationText={
                    attribute.products + attribute.variants > 0 ? attribute.slug : undefined
                  }
                />
              </PersistentAlertDialog.Root>
            </div>
          </Section.Root>
        </SectionStack>
      </Page.Body>
    </>
  );
}

Show.layout = [SidebarLayout];
