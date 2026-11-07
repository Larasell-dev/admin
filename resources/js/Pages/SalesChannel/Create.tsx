import { Form } from "@inertiajs/react";
import { fbs } from "fbtee";
import { Page } from "../../Components/Page";
import SalesChannelForm from "../../Components/SalesChannel/SalesChannelForm";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.SalesChannel.Data.CreateSalesChannelPageData;

export default function Create({ storeUrl, types, locales, currencies }: Props) {
  return (
    <>
      <Page.Header
        title={fbs(
          "New sales channel",
          "New sales channel page: title of the page with the form that adds a sales channel, such as a storefront",
        )}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <SalesChannelForm types={types} locales={locales} currencies={currencies} />
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
