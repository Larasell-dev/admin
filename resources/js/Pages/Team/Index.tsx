import { Link } from "@inertiajs/react";
import { Button, Icon } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import { Page } from "../../Components/Page";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Team.Data.TeamPageData;

export default function Index({ members, createUrl }: Props) {
  return (
    <>
      <Page.Header
        title={members.title}
        actions={
          <Button size="sm" render={<Link href={createUrl} prefetch="hover" />}>
            <Icon icon={AddLine} />
            Add member
          </Button>
        }
      />
      <ResourceTable resource={members} />
    </>
  );
}

Index.layout = [SidebarLayout];
