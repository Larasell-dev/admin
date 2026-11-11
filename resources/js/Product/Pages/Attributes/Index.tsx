import { Link } from "@inertiajs/react";
import { Button, Icon } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import { Page } from "../../../Platform/Panel/Components/Page";
import SidebarLayout from "../../../Platform/Panel/Layouts/SidebarLayout";
import ResourceTable from "../../../Platform/Tables/Components/ResourceTable";

type Props = Larasell.Admin.Product.Data.AttributesPageData;

/** The catalog's attributes, which describe products and variants without creating variants. */
export default function Index({ attributes, createUrl }: Props) {
  return (
    <>
      <Page.Header
        title={attributes.title}
        actions={
          <Button size="sm" render={<Link href={createUrl} prefetch="hover" />}>
            <Icon icon={AddLine} />
            <fbt desc="Attributes page: header button that opens the form to add an attribute">
              Add attribute
            </fbt>
          </Button>
        }
      />
      <ResourceTable resource={attributes} />
    </>
  );
}

Index.layout = [SidebarLayout];
