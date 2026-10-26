import { router } from "@inertiajs/react";
import type { PaginationChange } from "@larasell-dev/ui";
import { DataList, EmptyState, Pagination, Table } from "@larasell-dev/ui";
import { GroupLine } from "@larasell-dev/ui/icons";
import { useState } from "react";
import { Page } from "../Components/Page";
import SidebarLayout from "../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.CustomersPageData;

export default function Customers({ customers }: Props) {
  const [isPaginating, setIsPaginating] = useState(false);

  const pagination = {
    page: customers.current_page,
    pageSize: customers.per_page,
    total: customers.total,
    pageSizeOptions: [25, 50, 100],
    disabled: isPaginating,
    onPaginationChange: ({ page, pageSize }: PaginationChange) =>
      router.get(
        window.location.pathname,
        { page, per_page: pageSize },
        {
          preserveScroll: true,
          onStart: () => setIsPaginating(true),
          onFinish: () => setIsPaginating(false),
        },
      ),
  };

  return (
    <>
      <Page.Header title="Customers" />

      {customers.total === 0 ? (
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={GroupLine}
            title="No customers yet"
            description="Customers appear here after they place their first order."
          />
        </div>
      ) : (
        <>
          <div className="flex min-h-0 flex-1 flex-col lg:hidden">
            <div className="min-h-0 flex-1 overflow-y-auto">
              <DataList.Root aria-label="Customers">
                {customers.data.map((customer) => (
                  <DataList.Item key={customer.id}>
                    <DataList.Title>{customer.name}</DataList.Title>
                    <DataList.Field label="Orders">{customer.ordersCount}</DataList.Field>
                    <DataList.Field label="Created">{customer.createdAt}</DataList.Field>
                  </DataList.Item>
                ))}
              </DataList.Root>
            </div>
            <div className="border-border shrink-0 border-t px-4 py-3">
              <Pagination {...pagination} />
            </div>
          </div>

          <div className="hidden min-h-0 flex-1 lg:block">
            <Table.Root aria-label="Customers">
              <Table.Content>
                <Table.Header>
                  <Table.Column>Name</Table.Column>
                  <Table.Column>Orders</Table.Column>
                  <Table.Column>Created</Table.Column>
                </Table.Header>
                <Table.Body>
                  {customers.data.map((customer) => (
                    <Table.Row key={customer.id}>
                      <Table.Cell>{customer.name}</Table.Cell>
                      <Table.Cell>{customer.ordersCount}</Table.Cell>
                      <Table.Cell>{customer.createdAt}</Table.Cell>
                    </Table.Row>
                  ))}
                </Table.Body>
              </Table.Content>
              <Table.Pagination {...pagination} />
            </Table.Root>
          </div>
        </>
      )}
    </>
  );
}

Customers.layout = [SidebarLayout];
