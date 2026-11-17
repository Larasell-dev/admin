import { Form } from "@inertiajs/react";
import { Button, Icon, Section, SectionStack } from "@larasell-dev/ui";
import { DeleteBinLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import BrandForm from "../Components/BrandForm";
import ContentLocaleSelect from "../../Platform/ContentLocale/Components/ContentLocaleSelect";
import ConfirmDialog from "../../Platform/Panel/Components/ConfirmDialog";
import { Page } from "../../Platform/Panel/Components/Page";
import PersistentAlertDialog from "../../Platform/Panel/Components/PersistentAlertDialog";
import UnsavedChangesToast from "../../Platform/Panel/Components/UnsavedChangesToast";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";
import type { SharedProps } from "../../types";

type Props = Larasell.Admin.Brand.Data.BrandPageData & Pick<SharedProps, "contentLocale">;

export default function Show({ brand, updateUrl, destroyUrl, indexUrl, contentLocale }: Props) {
  const name = brand.name ?? brand.fallbackName ?? brand.slug;

  return (
    <>
      <Page.Header
        title={name}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to brands",
            "Brand page: accessible name of the back arrow to the brands",
          ),
        }}
        actions={<ContentLocaleSelect contentLocale={contentLocale} />}
      />
      <Page.Body>
        <SectionStack>
          <Form
            // Remounts with the new texts when the language changes.
            key={`${brand.id}-${contentLocale?.current}`}
            action={updateUrl}
            method="put"
            setDefaultsOnSuccess
            options={{ preserveScroll: true }}
          >
            <Section.Root>
              <Section.Header
                title={fbs("General", "Brand page: heading of the brand's fields")}
                description={fbs(
                  "The name products are marketed under.",
                  "Brand page: explains the section with the brand's fields",
                )}
              />
              <BrandForm brand={brand} />
            </Section.Root>
            <UnsavedChangesToast />
          </Form>
          <Section.Root>
            <Section.Header
              title={fbs("Delete brand", "Brand page: heading of the delete section")}
              description={
                brand.products > 0
                  ? fbs(
                      [
                        "Used by ",
                        fbs.plural("product", brand.products, {
                          many: "products",
                          showCount: "yes",
                          name: "products",
                        }),
                        ". Give them another brand first.",
                      ],
                      "Brand page: explains why a brand products use can't be deleted",
                    )
                  : fbs("No product uses it.", "Brand page: explains that the brand can be deleted")
              }
            />
            <div>
              <PersistentAlertDialog.Root>
                <PersistentAlertDialog.Trigger
                  render={<Button variant="destructive" disabled={brand.products > 0} />}
                >
                  <Icon icon={DeleteBinLine} />
                  <fbt desc="Brand page: button that asks to delete the brand">Delete</fbt>
                </PersistentAlertDialog.Trigger>
                <ConfirmDialog.Popup
                  title={fbs("Delete brand", "Brand page: title of the delete dialog")}
                  description={fbs(
                    [fbs.param("name", name), " is deleted. This can't be undone."],
                    "Brand page: delete dialog text",
                  )}
                  action={destroyUrl}
                  confirmationText={brand.slug}
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
