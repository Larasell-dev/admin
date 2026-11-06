import { Link } from "@inertiajs/react";
import { Badge, EmptyState, Inset, Section } from "@larasell-dev/ui";
import { ShoppingBagLine } from "@larasell-dev/ui/icons";
import { cn } from "cn";
import { fbs } from "fbtee";
import type { ReactNode } from "react";

import { Page } from "../../Components/Page";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Order.Data.OrderPageData;
type Order = Larasell.Admin.Order.Data.OrderDetailData;
type AddressData = Larasell.Admin.Order.Data.AddressData;

/** An order as it was placed. Read only: orders record what was bought, so they aren't edited. */
export default function Show({ order, indexUrl, customerUrl }: Props) {
  return (
    <>
      <Page.Header
        title={`#${order.number}`}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to orders",
            "Order page: accessible name of the back arrow that returns to the list of orders",
          ),
        }}
      />
      <Page.Body width="wide">
        <div className="flex flex-col gap-6">
          <div className="flex items-center gap-2 type-body-md text-base-secondary">
            <Badge variant={order.status.variant}>{order.status.label}</Badge>
            <span>{order.placedAt}</span>
          </div>
          <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div className="flex min-w-0 flex-col gap-6">
              <Items order={order} />
              <Customer order={order} customerUrl={customerUrl} />
              <div className="grid gap-6 sm:grid-cols-2">
                <Address
                  title={fbs(
                    "Billing address",
                    "Order page: heading of the card with the address the order is billed to",
                  )}
                  address={order.billingAddress}
                />
                <Address
                  title={fbs(
                    "Shipping address",
                    "Order page: heading of the card with the address the order is shipped to",
                  )}
                  address={order.shippingAddress}
                />
              </div>
            </div>
            <div className="flex min-w-0 flex-col gap-6">
              <Summary order={order} />
              <Payments order={order} />
              <Card
                title={fbs(
                  "Shipping",
                  "Order page: heading of the card with the shipping method chosen for the order and its cost",
                )}
              >
                {order.shipping ? (
                  <dl>
                    <Row label={order.shipping.label}>{order.shipping.amount}</Row>
                  </dl>
                ) : (
                  <Muted>
                    <fbt desc="Order page: shown in the shipping card when the order isn't shipped">
                      No shipping
                    </fbt>
                  </Muted>
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
      <Section.Header
        title={fbs("Items", "Order page: heading of the section that lists the ordered products")}
      />
      <Section.Card>
        {/* Checkout always adds items; this only covers orders created some other way. */}
        {order.items.length === 0 ? (
          <div className="flex justify-center px-(--inset) py-12">
            <EmptyState
              icon={ShoppingBagLine}
              title={fbs(
                "No items",
                "Order page: shown in the section for ordered products when the order has none",
              )}
              description={fbs(
                "This order doesn't contain any items.",
                "Order page: below 'No items' in the section for ordered products",
              )}
            />
          </div>
        ) : (
          <ul className="divide-y divide-base-subtle">
            {order.items.map((item, index) => (
              <li key={index}>
                <Inset>
                  <div className="flex items-start justify-between gap-4 type-body-md">
                    <div className="min-w-0">
                      <div className="text-base-primary">{item.name}</div>
                      {item.variant && <Muted>{item.variant}</Muted>}
                    </div>
                    <div className="shrink-0 text-right">
                      <div className="text-base-primary tabular-nums">{item.total}</div>
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
    <Card
      title={fbs(
        "Summary",
        "Order page: heading of the card that adds up the order's subtotal, discount, shipping, taxes and total",
      )}
    >
      <dl className="flex flex-col gap-2">
        <Row
          label={fbs(
            "Subtotal",
            "Order summary: label of the sum of the ordered products, before discount, shipping and taxes",
          )}
        >
          {order.subtotal}
        </Row>
        {order.discount && (
          <Row label={fbs("Discount", "Order summary: label of the amount taken off the order")}>
            −{order.discount}
          </Row>
        )}
        {order.shipping && (
          <Row label={fbs("Shipping", "Order summary: label of the shipping cost of the order")}>
            {order.shipping.amount}
          </Row>
        )}
        {order.taxes.map((tax) => (
          <Row
            key={tax.label}
            label={
              order.taxIncluded
                ? fbs(
                    ["Incl. ", fbs.param("taxName", tax.label)],
                    "Order summary: label of a tax already included in the prices, with the tax's name filled in, e.g. 'Incl. VAT 19%'",
                  )
                : tax.label
            }
          >
            {tax.amount}
          </Row>
        ))}
        <Row
          label={fbs("Total", "Order summary: label of the amount the customer pays in total")}
          strong
        >
          {order.total}
        </Row>
      </dl>
    </Card>
  );
}

/** Each value under a label saying what it is, so long names and emails stay readable. */
function Customer({ order, customerUrl }: { order: Order; customerUrl: string | null }) {
  return (
    <Card title={fbs("Customer", "Order page: heading of the card with who placed the order")}>
      <Fields columns>
        <Field
          label={fbs("Name", "Order page: label of the name of the customer who placed the order")}
        >
          {customerUrl ? (
            <Link href={customerUrl} prefetch="hover" className="underline underline-offset-2">
              {order.customerName}
            </Link>
          ) : (
            order.customerName
          )}
        </Field>
        <Field
          label={fbs(
            "Email",
            "Order page: label of the email address of the customer who placed the order",
          )}
        >
          <a href={`mailto:${order.customerEmail}`} className="underline-offset-2 hover:underline">
            {order.customerEmail}
          </a>
        </Field>
        <Field
          label={fbs(
            "Phone",
            "Order page: label of the phone number of the customer who placed the order",
          )}
        >
          {order.customerPhone ?? (
            <Muted>
              <fbt desc="Order page: shown as the customer's phone number when they didn't give one">
                None
              </fbt>
            </Muted>
          )}
        </Field>
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
            <Field label={fbs("Name", "Order page, address card: label of the recipient's name")}>
              {address.name}
            </Field>
            {address.company && (
              <Field
                label={fbs("Company", "Order page, address card: label of the recipient's company")}
              >
                {address.company}
              </Field>
            )}
            <Field
              label={fbs(
                "Street",
                "Order page, address card: label of the street and house number lines of the address",
              )}
            >
              {address.street.map((line, index) => (
                <div key={index}>{line}</div>
              ))}
            </Field>
            <Field
              label={fbs(
                "Postcode and city",
                "Order page, address card: label of the address's postcode followed by its city",
              )}
            >
              {address.postcode} {address.city}
            </Field>
            {address.state && (
              <Field
                label={fbs(
                  "State or region",
                  "Order page, address card: label of the address's state, province or region",
                )}
              >
                {address.state}
              </Field>
            )}
            <Field
              label={fbs("Country", "Order page, address card: label of the address's country")}
            >
              {address.country}
            </Field>
          </Fields>
        </address>
      ) : (
        <Muted>
          <fbt desc="Order page: shown in a billing or shipping address card when the order has no such address">
            No address
          </fbt>
        </Muted>
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
      <dt className="type-label-sm text-base-secondary">{label}</dt>
      <dd className="text-base-primary">{children}</dd>
    </div>
  );
}

function Payments({ order }: { order: Order }) {
  return (
    <Card
      title={fbs(
        "Payment",
        "Order page: heading of the card that lists the payments made for the order",
      )}
    >
      {order.payments.length === 0 ? (
        <Muted>
          <fbt desc="Order page: shown in the payment card when nothing was paid for the order yet">
            No payments yet
          </fbt>
        </Muted>
      ) : (
        <ul className="flex flex-col gap-3">
          {order.payments.map((payment, index) => (
            <li key={index} className="flex flex-col gap-1 type-body-md">
              <div className="flex items-center justify-between gap-2">
                <span className="text-base-primary">{payment.method}</span>
                <Badge variant={payment.status.variant}>{payment.status.label}</Badge>
              </div>
              <div className="flex justify-between gap-2">
                <Muted>
                  {payment.reference ? (
                    <fbt desc="Order page, payment card: the payment provider's reference for a payment, e.g. 'Ref. pi_3MtwBw'">
                      Ref. <fbt:param name="paymentReference">{payment.reference}</fbt:param>
                    </fbt>
                  ) : (
                    <fbt desc="Order page, payment card: shown when a payment has no reference from the payment provider">
                      No reference
                    </fbt>
                  )}
                </Muted>
                <span className="text-base-primary tabular-nums">{payment.amount}</span>
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
        "flex justify-between gap-4 text-base-primary",
        strong ? "border-t border-base-subtle pt-3 type-body-md-strong" : "type-body-md",
      )}
    >
      <dt className={strong ? undefined : "text-base-secondary"}>{label}</dt>
      <dd className="tabular-nums">{children}</dd>
    </div>
  );
}

function Muted({ children }: { children: ReactNode }) {
  return <div className="type-body-md text-base-secondary">{children}</div>;
}

Show.layout = [SidebarLayout];
