import { useFormContext } from "@inertiajs/react";
import { Field, Input } from "@larasell-dev/ui";
import { fbs } from "fbtee";

type Customer = Pick<Larasell.Admin.Customer.Data.CustomerDetailData, "firstName" | "lastName">;

/**
 * The fields of a customer. It must sit inside an Inertia `<Form>`, which submits them and
 * provides their errors. Without a customer it starts empty.
 */
export default function CustomerForm({ customer }: { customer?: Customer }) {
  const errors = useFormContext()?.errors ?? {};

  return (
    <div className="@container">
      <div className="grid items-start gap-5 @md:grid-cols-2">
        <Field
          label={fbs(
            "First name",
            "Customer form: label of the field for the customer's first name",
          )}
          name="first_name"
          error={errors.first_name}
        >
          <Input defaultValue={customer?.firstName} autoComplete="off" />
        </Field>

        <Field
          label={fbs("Last name", "Customer form: label of the field for the customer's last name")}
          name="last_name"
          error={errors.last_name}
        >
          <Input defaultValue={customer?.lastName} autoComplete="off" />
        </Field>
      </div>
    </div>
  );
}
