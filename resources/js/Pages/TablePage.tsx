import { EmptyState } from "@larasell-dev/ui";
import { InboxLine } from "@larasell-dev/ui/icons";
import { Head } from "@inertiajs/react";
import ResourceTable from "../Components/ResourceTable";
import { navigationIcon } from "../utils/navigationIcon";
import SidebarLayout from "../Layouts/SidebarLayout";
import type { TableResource } from "../types";

type Props = { table: TableResource };

/** Empty states use the line style of an icon, like inactive navigation items. */
const emptyStateIcon = (name: string) => navigationIcon(name, false);

export default function TablePage({ table }: Props) {
  return (
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <Head title={table.title} />
      <header className="border-border flex h-14 shrink-0 items-center border-b px-6">
        <h1 className="type-body-md text-mauve-950 dark:text-mauve-50">{table.title}</h1>
      </header>

      {table.pagination.total === 0 ? (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={table.emptyState?.icon ? emptyStateIcon(table.emptyState.icon) : InboxLine}
            title={table.emptyState?.title ?? `No ${table.title.toLowerCase()} yet`}
            description={table.emptyState?.description ?? undefined}
          />
        </div>
      ) : (
        <ResourceTable resource={table} />
      )}
    </div>
  );
}

TablePage.layout = [SidebarLayout];
