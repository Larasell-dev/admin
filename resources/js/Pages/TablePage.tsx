import { Link } from "@inertiajs/react";
import { Button, EmptyState, Icon } from "@larasell-dev/ui";
import { InboxLine } from "@larasell-dev/ui/icons";
import { Page } from "../Components/Page";
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
      render={isSameSite(action.url) ? <Link href={action.url} /> : <a href={action.url} />}
    >
      {action.icon && <Icon icon={lineIcon(action.icon)} />}
      {action.label}
    </Button>
  );
}

export default function TablePage({ table }: Props) {
  const hasRows = table.pagination.total > 0;

  return (
    <>
      <Page.Header
        title={table.title}
        actions={
          // The empty state offers its own actions instead.
          hasRows &&
          table.headerActions.map((action) => (
            <TableActionLink key={action.label} action={action} size="sm" />
          ))
        }
      />

      {hasRows ? (
        <ResourceTable resource={table} />
      ) : (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={table.emptyState?.icon ? lineIcon(table.emptyState.icon) : InboxLine}
            title={table.emptyState?.title ?? `No ${table.title.toLowerCase()} yet`}
            description={table.emptyState?.description ?? undefined}
          >
            {table.emptyState?.actions.map((action) => (
              <TableActionLink key={action.label} action={action} />
            ))}
          </EmptyState>
        </div>
      )}
    </>
  );
}

TablePage.layout = [SidebarLayout];
