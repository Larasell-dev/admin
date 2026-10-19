import { DataList, Table } from "@larasell-dev/ui";
import type { TableResource } from "../types";

type Props = {
  resource: TableResource;
  "aria-label"?: string;
};

/** Renders a PHP-defined table: a data list on small screens, the UI table from `lg` up. */
export default function ResourceTable({ resource, "aria-label": ariaLabel }: Props) {
  const label = ariaLabel ?? resource.title;
  const [primary, ...rest] = resource.columns;

  return (
    <>
      <div className="min-h-0 flex-1 overflow-y-auto lg:hidden">
        <DataList.Root aria-label={label}>
          {resource.rows.map((row) => (
            <DataList.Item key={row.id}>
              {primary && <DataList.Title>{row[primary.name]}</DataList.Title>}
              {rest.map((column) => (
                <DataList.Field key={column.name} label={column.label}>
                  {row[column.name]}
                </DataList.Field>
              ))}
            </DataList.Item>
          ))}
        </DataList.Root>
      </div>

      <div className="hidden min-h-0 flex-1 lg:block">
        <Table.Root aria-label={label}>
          <Table.Content>
            <Table.Header>
              {resource.columns.map((column) => (
                <Table.Column key={column.name}>{column.label}</Table.Column>
              ))}
            </Table.Header>
            <Table.Body>
              {resource.rows.map((row) => (
                <Table.Row key={row.id}>
                  {resource.columns.map((column) => (
                    <Table.Cell key={column.name}>{row[column.name]}</Table.Cell>
                  ))}
                </Table.Row>
              ))}
            </Table.Body>
          </Table.Content>
        </Table.Root>
      </div>
    </>
  );
}
