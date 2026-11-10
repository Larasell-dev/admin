import { Link } from "@inertiajs/react";
import { Button, ButtonGroup, EmptyState, Icon, IconButton, Menu } from "@larasell-dev/ui";
import { ArrowDownSLine, InboxLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { Page } from "../../Panel/Components/Page";
import ResourceTable from "../Components/ResourceTable";
import { isSameSite } from "../../Support/isSameSite";
import { navigationIcon } from "../../Support/navigationIcon";
import SidebarLayout from "../../Panel/Layouts/SidebarLayout";
import type { TableAction, TableActionGroup, TableResource } from "../../../types";

type Props = { table: TableResource };

/** Empty states and actions use the line style of an icon, like inactive navigation items. */
const lineIcon = (name: string) => navigationIcon(name, false);

/** Labels are unique across a list and its groups, so a group is keyed by its first. */
const itemKey = (item: TableAction | TableActionGroup) =>
  "actions" in item ? item.actions[0].label : item.label;

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

/** The group's first action is its button; the others are in a menu next to it. */
function TableActionButtons({ item, size }: { item: TableAction | TableActionGroup; size?: "sm" }) {
  if (!("actions" in item)) {
    return <TableActionLink action={item} size={size} />;
  }

  const [first, ...rest] = item.actions;

  return (
    <ButtonGroup label={first.label} size={size}>
      <TableActionLink action={first} />
      <Menu.Root>
        <Menu.Trigger
          render={
            <IconButton
              aria-label={fbs(
                ["More actions like ", fbs.param("action", first.label)],
                "List page: accessible name of the arrow button next to an action, which opens a menu of related actions, e.g. 'More actions like Add product'",
              )}
            >
              <Icon icon={ArrowDownSLine} />
            </IconButton>
          }
        />
        <Menu.Popup>
          {rest.map((action) => (
            <Menu.Item
              key={action.label}
              icon={action.icon ? lineIcon(action.icon) : undefined}
              render={isSameSite(action.url) ? <Link href={action.url} /> : <a href={action.url} />}
            >
              {action.label}
            </Menu.Item>
          ))}
        </Menu.Popup>
      </Menu.Root>
    </ButtonGroup>
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
          table.headerActions.map((item) => (
            <TableActionButtons key={itemKey(item)} item={item} size="sm" />
          ))
        }
      />

      {hasRows ? (
        <ResourceTable resource={table} />
      ) : (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={table.emptyState?.icon ? lineIcon(table.emptyState.icon) : InboxLine}
            title={
              table.emptyState?.title ??
              // Not built from the table's title, whose case and grammar vary by language.
              fbs(
                "Nothing here yet",
                "List page: shown instead of an empty list whose table names no empty state of its own",
              )
            }
            description={table.emptyState?.description ?? undefined}
          >
            {table.emptyState?.actions.map((item) => (
              <TableActionButtons key={itemKey(item)} item={item} />
            ))}
          </EmptyState>
        </div>
      )}
    </>
  );
}

TablePage.layout = [SidebarLayout];
