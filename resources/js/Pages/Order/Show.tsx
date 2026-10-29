import { Link } from "@inertiajs/react";
import { Badge, EmptyState, Inset, Section } from "@larasell-dev/ui";
import { ShoppingBagLine } from "@larasell-dev/ui/icons";
import { cn } from "cn";
import type { ReactNode } from "react";

import { Page } from "../../Components/Page";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.Orders.OrderPageData;
type Order = Larasell.Admin.Data.ViewModel.Orders.OrderDetailData;
type AddressData = Larasell.Admin.Data.ViewModel.Orders.AddressData;

/** An order as it was placed. Read only: orders record what was bought, so they aren't edited. */
export default function Show({ order, indexUrl, customerUrl }: Props) {
  return (
    <>
      <Page.Header
        title={`#${order.number}`}
        backLink={{ href: indexUrl, label: "Back to orders" }}
      />
      <Page.Body width="wide">
        <div className="flex flex-col gap-6">
          <div className="flex items-center gap-2 type-body-md text-mauve-600 dark:text-mauve-400">
            <Badge variant={order.status.variant}>{order.status.label}</Badge>
            <span>{order.placedAt}</span>
          </div>
          <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div className="flex min-w-0 flex-col gap-6">
              <Items order={order} />
              <Customer order={order} customerUrl={customerUrl} />
              <div className="grid gap-6 sm:grid-cols-2">
                <Address title="Billing address" address={order.billingAddress} />
                <Address title="Shipping address" address={order.shippingAddress} />
              </div>
            </div>
            <div className="flex min-w-0 flex-col gap-6">
              <Summary order={order} />
              <Payments order={order} />
              <Card title="Shipping">
                {order.shipping ? (
                  <dl>
                    <Row label={order.shipping.label}>{order.shipping.amount}</Row>
                  </dl>
                ) : (
                  <Muted>No shipping</Muted>
                )}
              </Card>
            </div>
          </div>
        </div>
      </Page.Body>
    </>
  );
}

function Items({ order }: { order: Order }) {
  return (
    <Section.Root>
      <Section.Header title="Items" />
      <Section.Card>
        {/* Checkout always adds items; this only covers orders created some other way. */}
        {order.items.length === 0 ? (
          <div className="flex justify-center px-(--inset) py-12">
            <EmptyState
              icon={ShoppingBagLine}
              title="No items"
              description="This order doesn't contain any items."
            />
          </div>
        ) : (
          <ul className="divide-y divide-border">
            {order.items.map((item, index) => (
              <li key={index}>
                <Inset>
                  <div className="flex items-start justify-between gap-4 type-body-md">
                    <div className="min-w-0">
                      <div className="text-mauve-950 dark:text-mauve-50">{item.name}</div>
                      {item.variant && <Muted>{item.variant}</Muted>}
                    </div>
                    <div className="shrink-0 text-right">
                      <div className="text-mauve-950 tabular-nums dark:text-mauve-50">
                        {item.total}
                      </div>
                      <Muted>
                        {item.quantity} × {item.unitPrice}
                      </Muted>
                    </div>
                  </div>
                </Inset>
              </li>
            ))}
          </ul>
        )}
      </Section.Card>
    </Section.Root>
  );
}

function Summary({ order }: { order: Order }) {
  return (
    <Card title="Summary">
      <dl className="flex flex-col gap-2">
        <Row label="Subtotal">{order.subtotal}</Row>
        {order.discount && <Row label="Discount">−{order.discount}</Row>}
        {order.shipping && <Row label="Shipping">{order.shipping.amount}</Row>}
        {order.taxes.map((tax) => (
          <Row key={tax.label} label={order.taxIncluded ? `Incl. ${tax.label}` : tax.label}>
            {tax.amount}
          </Row>
        ))}
        <Row label="Total" strong>
          {order.total}
        </Row>
      </dl>
    </Card>
  );
}

/** Each value under a label saying what it is, so long names and emails stay readable. */
function Customer({ order, customerUrl }: { order: Order; customerUrl: string | null }) {
  return (
    <Card title="Customer">
      <Fields columns>
        <Field label="Name">
          {customerUrl ? (
            <Link href={customerUrl} prefetch="hover" className="underline underline-offset-2">
              {order.customerName}
            </Link>
          ) : (
            order.customerName
          )}
        </Field>
        <Field label="Email">
          <a href={`mailto:${order.customerEmail}`} className="underline-offset-2 hover:underline">
            {order.customerEmail}
          </a>
        </Field>
        <Field label="Phone">{order.customerPhone ?? <Muted>None</Muted>}</Field>
      </Fields>
    </Card>
  );
}

function Address({ title, address }: { title: string; address: AddressData | null }) {
  return (
    <Card title={title} stretch>
      {address ? (
        <address className="not-italic">
          <Fields>
            <Field label="Name">{address.name}</Field>
            {address.company && <Field label="Company">{address.company}</Field>}
            <Field label="Street">
              {address.street.map((line, index) => (
                <div key={index}>{line}</div>
              ))}
            </Field>
            <Field label="Postcode and city">
              {address.postcode} {address.city}
            </Field>
            {address.state && <Field label="State or region">{address.state}</Field>}
            <Field label="Country">{address.country}</Field>
          </Fields>
        </address>
      ) : (
        <Muted>No address</Muted>
      )}
    </Card>
  );
}

function Fields({ columns = false, children }: { columns?: boolean; children: ReactNode }) {
  return (
    <dl className={cn("grid gap-4 type-body-md wrap-anywhere", columns && "sm:grid-cols-3")}>
      {children}
    </dl>
  );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex min-w-0 flex-col gap-0.5">
      <dt className="type-label-sm text-mauve-600 dark:text-mauve-400">{label}</dt>
      <dd className="text-mauve-950 dark:text-mauve-50">{children}</dd>
    </div>
  );
}

function Payments({ order }: { order: Order }) {
  return (
    <Card title="Payment">
      {order.payments.length === 0 ? (
        <Muted>No payments yet</Muted>
      ) : (
        <ul className="flex flex-col gap-3">
          {order.payments.map((payment, index) => (
            <li key={index} className="flex flex-col gap-1 type-body-md">
              <div className="flex items-center justify-between gap-2">
                <span className="text-mauve-950 dark:text-mauve-50">{payment.method}</span>
                <Badge variant={payment.status.variant}>{payment.status.label}</Badge>
              </div>
              <div className="flex justify-between gap-2">
                <Muted>{payment.reference ? `Ref. ${payment.reference}` : "No reference"}</Muted>
                <span className="text-mauve-950 tabular-nums dark:text-mauve-50">
                  {payment.amount}
                </span>
              </div>
            </li>
          ))}
        </ul>
      )}
    </Card>
  );
}

/**
 * A titled section with its content in a card. `stretch` grows the card to the section's height,
 * so cards side by side in a grid row, like the two addresses, end level.
 */
function Card({
  title,
  stretch = false,
  children,
}: {
  title: string;
  stretch?: boolean;
  children: ReactNode;
}) {
  const card = (
    <Section.Card>
      <Inset>{children}</Inset>
    </Section.Card>
  );

  return (
    <Section.Root>
      <Section.Header title={title} />
      {/* A one-cell grid stretches its item, and Section.Card takes no class to grow itself. */}
      {stretch ? <div className="grid flex-1">{card}</div> : card}
    </Section.Root>
  );
}

function Row({
  label,
  strong,
  children,
}: {
  label: string;
  strong?: boolean;
  children: ReactNode;
}) {
  return (
    <div
      className={cn(
        "flex justify-between gap-4 text-mauve-950 dark:text-mauve-50",
        strong ? "border-t border-border pt-3 type-body-md-strong" : "type-body-md",
      )}
    >
      <dt className={strong ? undefined : "text-mauve-600 dark:text-mauve-400"}>{label}</dt>
      <dd className="tabular-nums">{children}</dd>
    </div>
  );
}

function Muted({ children }: { children: ReactNode }) {
  return <div className="type-body-md text-mauve-600 dark:text-mauve-400">{children}</div>;
}

Show.layout = [SidebarLayout];
