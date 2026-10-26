import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import AdminForm from "../../Components/AdminForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.EditAdminPageData;

export default function Edit({ editedAdmin, updateUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={editedAdmin.name}
        backLink={{ href: indexUrl, label: "Back to admins" }}
      />
      <Page.Body>
        <Form
          // Inertia keeps this page mounted when moving between admins; remounting resets the fields.
          key={updateUrl}
          action={updateUrl}
          method="put"
          // The saved values become the new baseline, so the form is no longer dirty. The password
          // isn't sent back, so it empties again.
          setDefaultsOnSuccess
          resetOnSuccess={["password"]}
        >
          <Section.Root>
            <Section.Header title="General" description="Who the admin is and how they sign in." />
            <AdminForm admin={editedAdmin} />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Edit.layout = [SidebarLayout];
