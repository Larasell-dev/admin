import { Link } from "@inertiajs/react";
import { Button, Icon } from "@larasell-dev/ui";
import { AddLine } from "@larasell-dev/ui/icons";
import ResourceTable from "../../Components/ResourceTable";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.AdminsPageData;

export default function Index({ admins, createUrl }: Props) {
  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="flex h-14 shrink-0 items-center gap-4 border-b border-border px-6">
        <h2 className="flex-1 truncate type-body-md text-mauve-950 dark:text-mauve-50">
          {admins.title}
        </h2>
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
      </header>
      <ResourceTable resource={admins} />
    </div>
  );
}

Index.layout = [SidebarLayout];
