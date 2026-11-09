import { Link } from "@inertiajs/react";
import { Button, Icon } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import { Page } from "../../Platform/Panel/Components/Page";
import ResourceTable from "../../Platform/Tables/Components/ResourceTable";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";
import { useSettingsBackLink } from "../../Platform/Support/useSettingsBackLink";

type Props = Larasell.Admin.Team.Data.TeamPageData;

export default function Index({ members, createUrl }: Props) {
  return (
    <>
      <Page.Header
        backLink={useSettingsBackLink()}
        title={members.title}
        actions={
          <Button size="sm" render={<Link href={createUrl} prefetch="hover" />}>
            <Icon icon={AddLine} />
            <fbt desc="Team page: header button that opens the form to add an admin to the panel">
              Add member
            </fbt>
          </Button>
        }
      />
      <ResourceTable resource={members} />
    </>
  );
}

Index.layout = [SidebarLayout];
