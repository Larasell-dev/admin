import { Link } from "@inertiajs/react";
import { Button, Icon } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import { Page } from "../../Components/Page";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.AdminsPageData;

export default function Index({ admins, createUrl }: Props) {
  return (
    <>
      <Page.Header
        title={admins.title}
        actions={
          <Button size="sm" render={<Link href={createUrl} prefetch="hover" />}>
            <Icon icon={AddLine} />
            Add admin
          </Button>
        }
      />
      <ResourceTable resource={admins} />
    </>
  );
}

Index.layout = [SidebarLayout];
