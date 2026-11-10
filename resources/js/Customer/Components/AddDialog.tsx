import { Form } from "@inertiajs/react";
import { Button } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import type { ReactNode } from "react";
import { Slideover } from "../../Platform/Panel/Components/Slideover";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  action: string;
  submitLabel: string;
  children: (errors: Record<string, string>) => ReactNode;
};

/**
 * A slideover with a form that adds one item to a list, e.g. an address. Render it once, outside
 * the list and its empty state, and open it from either: it stays mounted when the first item
 * replaces the empty state, so it can animate out.
 */
export default function AddDialog({
  open,
  onOpenChange,
  title,
  description,
  action,
  submitLabel,
  children,
}: Props) {
  return (
    <Slideover.Root open={open} onOpenChange={onOpenChange}>
      <Form
        action={action}
        method="post"
        resetOnSuccess
        options={{ preserveScroll: true }}
        onSuccess={() => onOpenChange(false)}
        className="flex min-h-0 flex-1 flex-col"
      >
        {({ errors, processing }) => (
          <>
            <Slideover.Header title={title} description={description} />
            <Slideover.Content>
              <div className="grid items-start gap-4">{children(errors)}</div>
            </Slideover.Content>
            <Slideover.Footer>
              <Slideover.Actions>
                <Slideover.Close>
                  {fbs(
                    "Cancel",
                    "Customer page: button that closes an add slideover without adding anything",
                  )}
                </Slideover.Close>
                <Button type="submit" loading={processing}>
                  {submitLabel}
                </Button>
              </Slideover.Actions>
            </Slideover.Footer>
          </>
        )}
      </Form>
    </Slideover.Root>
  );
}
