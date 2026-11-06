import type { SectionComponentProps } from "@larasell-dev/admin";
import { Field, Input, Section } from "@larasell-dev/ui";

type Props = { note: string | null };

/**
 * A plugin's section on the product page: a note for the team that the store never shows. The
 * playground isn't translated, so its text is plain English.
 */
export default function ProductNotesSection({ props, name, error }: SectionComponentProps<Props>) {
  return (
    <Section.Root>
      <Section.Header
        title="Notes for your team"
        description="Only admins see these. They're saved with the product."
      />
      <Field label="Internal note" name={name("note")} error={error("note")}>
        <Input defaultValue={props.note ?? undefined} autoComplete="off" />
      </Field>
    </Section.Root>
  );
}
