import { useFormContext } from "@inertiajs/react";
import { Field, Input, PasswordInput, Stack } from "@larasell-dev/ui";

type Member = Pick<Larasell.Admin.Team.Data.AdminData, "name" | "email">;

/**
 * The fields of a team member. It must sit inside an Inertia `<Form>`, which submits them and
 * provides their errors. Without a member it starts empty and needs a password; with one,
 * an empty password keeps theirs.
 */
export default function MemberForm({ member }: { member?: Member }) {
  const errors = useFormContext()?.errors ?? {};

  return (
    <Stack spacing={5}>
      <div className="@container">
        <div className="grid items-start gap-5 @md:grid-cols-2">
          <Field label="Name" name="name" error={errors.name}>
            <Input defaultValue={member?.name} autoComplete="off" />
          </Field>

          <Field label="Email" name="email" error={errors.email}>
            <Input type="email" defaultValue={member?.email} autoComplete="off" />
          </Field>
        </div>
      </div>

      <Field
        label={member ? "New password" : "Password"}
        name="password"
        error={errors.password}
        description={
          member
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
