import { Field as BaseField } from "@base-ui/react/field";
import { Checkbox, Error } from "@larasell-dev/ui";
import FormCheckbox from "./FormCheckbox";

/** Whether a category or product shows in the store, submitted as its `status`. */
export default function VisibilityField({
  defaultVisible,
  error,
}: {
  defaultVisible: boolean;
  error?: string;
}) {
  return (
    <BaseField.Root
      name="status"
      invalid={!!error}
      // Keeps the checkbox's absolutely positioned hidden input inside the scrolling content.
      className="relative flex flex-col gap-1.5"
    >
      <Checkbox.Label>
        <FormCheckbox
          name="status"
          value="visible"
          uncheckedValue="hidden"
          defaultChecked={defaultVisible}
        />
        Visible in the store
      </Checkbox.Label>
      {error && <Error match>{error}</Error>}
    </BaseField.Root>
  );
}
