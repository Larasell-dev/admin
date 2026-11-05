import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import MemberForm from "../../Components/Team/MemberForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Team.Data.CreateMemberPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header title="New member" backLink={{ href: indexUrl, label: "Back to team" }} />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <Section.Root>
            <Section.Header title="General" description="Who the member is and how they sign in." />
            <MemberForm />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
