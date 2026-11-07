import { useFormContext } from "@inertiajs/react";
import { Field, Input, PasswordInput, Stack } from "@larasell-dev/ui";
import { fbs } from "fbtee";

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
          <Field
            label={fbs("Name", "Team member form: label of the field for the member's full name")}
            name="name"
            error={errors.name}
          >
            <Input defaultValue={member?.name} autoComplete="off" />
          </Field>

          <Field
            label={fbs(
              "Email",
              "Team member form: label of the field for the member's email address, which they sign in with",
            )}
            name="email"
            error={errors.email}
          >
            <Input type="email" defaultValue={member?.email} autoComplete="off" />
          </Field>
        </div>
      </div>

      <Field
        label={
          member
            ? fbs(
                "New password",
                "Team member form: label of the password field when editing an existing member, who keeps their password if it's left empty",
              )
            : fbs(
                "Password",
                "Team member form: label of the password field when adding a new member",
              )
        }
        name="password"
        error={errors.password}
        description={
          member
            ? fbs(
                "Leave empty to keep the current password.",
                "Team member form: hint below the new password field when editing an existing member",
              )
            : fbs(
                "At least 12 characters, with upper and lower case letters, a number and a symbol.",
                "Team member form: hint below the password field of a new member, listing the password rules",
              )
        }
      >
        {/* Someone else's password, so the browser shouldn't fill in the signed-in admin's own. */}
        <PasswordInput autoComplete="new-password" />
      </Field>
    </Stack>
  );
}
