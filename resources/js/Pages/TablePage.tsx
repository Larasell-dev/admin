import { Link } from "@inertiajs/react";
import { Button, EmptyState, Icon } from "@larasell-dev/ui";
import { InboxLine } from "@larasell-dev/ui/icons";
import ResourceTable from "../Components/ResourceTable";
import { isSameSite } from "../utils/isSameSite";
import { navigationIcon } from "../utils/navigationIcon";
import SidebarLayout from "../Layouts/SidebarLayout";
import type { TableAction, TableResource } from "../types";

type Props = { table: TableResource };

/** Empty states and actions use the line style of an icon, like inactive navigation items. */
const lineIcon = (name: string) => navigationIcon(name, false);

function TableActionLink({ action, size }: { action: TableAction; size?: "sm" }) {
  return (
    <Button
      size={size}
      nativeButton={false}
      // Base UI marks non-native buttons as buttons; this navigates, so it stays a link.
      role="link"
      render={isSameSite(action.url) ? <Link href={action.url} /> : <a href={action.url} />}
    >
      {action.icon && <Icon icon={lineIcon(action.icon)} />}
      {action.label}
    </Button>
  );
}

export default function TablePage({ table }: Props) {
  const isEmpty = table.pagination.total === 0;

  return (
    // BEST-BEFORE 2026-11-01: Find out why pages size themselves with this calc (3.5rem is the
    // mobile nav's h-14 header) and whether the layout should own it; pages repeat it today.
    <div className="flex h-[calc(100dvh-3.5rem)] flex-col lg:h-dvh">
      <header className="border-border flex h-14 shrink-0 items-center gap-4 border-b px-6">
        <h1 className="type-body-md text-mauve-950 dark:text-mauve-50 flex-1 truncate">
          {table.title}
        </h1>
        {/* The empty state offers its own actions instead. */}
        {!isEmpty &&
          table.headerActions.map((action) => (
            <TableActionLink key={action.url} action={action} size="sm" />
          ))}
      </header>

      {isEmpty ? (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={table.emptyState?.icon ? lineIcon(table.emptyState.icon) : InboxLine}
            title={table.emptyState?.title ?? `No ${table.title.toLowerCase()} yet`}
            description={table.emptyState?.description ?? undefined}
          >
            {table.emptyState?.actions.map((action) => (
              <TableActionLink key={action.url} action={action} />
            ))}
          </EmptyState>
        </div>
      ) : (
        <ResourceTable resource={table} />
      )}
    </div>
  );
}

TablePage.layout = [SidebarLayout];
