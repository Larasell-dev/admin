import { Link } from "@inertiajs/react";
import { Button, Icon } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import ModuleHeader from "../../Components/ModuleHeader";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.AdminsPageData;

export default function Index({ admins, createUrl }: Props) {
  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <ModuleHeader
        title={admins.title}
        suffix={
          <Button
            size="sm"
            nativeButton={false}
            // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
            role="link"
            render={<Link href={createUrl} prefetch="hover" />}
          >
            <Icon icon={AddLine} />
            Add admin
          </Button>
        }
      />
      <ResourceTable resource={admins} />
    </div>
  );
}

Index.layout = [SidebarLayout];
