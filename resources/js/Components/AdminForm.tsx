import { useFormContext } from "@inertiajs/react";
import { Field, Input, PasswordInput, Stack } from "@larasell-dev/ui";

type Admin = Pick<Larasell.Admin.Data.ViewModel.AdminData, "name" | "email">;

/**
 * The fields of an admin. It must sit inside an Inertia `<Form>`, which submits them and
 * provides their errors. Without an admin it starts empty and needs a password; with one,
 * an empty password keeps theirs.
 */
export default function AdminForm({ admin }: { admin?: Admin }) {
  const errors = useFormContext()?.errors ?? {};

  return (
    <Stack spacing={5}>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Name" name="name" error={errors.name}>
            <Input defaultValue={admin?.name} autoComplete="off" />
          </Field>

          <Field label="Email" name="email" error={errors.email}>
            <Input type="email" defaultValue={admin?.email} autoComplete="off" />
          </Field>
        </div>
      </div>

      <Field
        label={admin ? "New password" : "Password"}
        name="password"
        error={errors.password}
        description={
          admin
            ? "Leave empty to keep the current password."
            : "At least 12 characters, with upper and lower case letters, a number and a symbol."
        }
      >
        {/* Someone else's password, so the browser shouldn't fill in the signed-in admin's own. */}
        <PasswordInput autoComplete="new-password" />
      </Field>
    </Stack>
  );
}
