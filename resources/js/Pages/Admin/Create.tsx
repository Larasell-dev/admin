import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import AdminForm from "../../Components/Team/AdminForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Team.Data.CreateAdminPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header title="New admin" backLink={{ href: indexUrl, label: "Back to admins" }} />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <Section.Root>
            <Section.Header title="General" description="Who the admin is and how they sign in." />
            <AdminForm />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
