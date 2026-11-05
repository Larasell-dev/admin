import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import MemberForm from "../../Components/Team/MemberForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Team.Data.EditMemberPageData;

export default function Edit({ member, updateUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header title={member.name} backLink={{ href: indexUrl, label: "Back to team" }} />
      <Page.Body>
        <Form
          // Inertia keeps this page mounted when moving between members; remounting resets the fields.
          key={updateUrl}
          action={updateUrl}
          method="put"
          // The saved values become the new baseline, so the form is no longer dirty. The password
          // isn't sent back, so it empties again.
          setDefaultsOnSuccess
          resetOnSuccess={["password"]}
        >
          <Section.Root>
            <Section.Header title="General" description="Who the member is and how they sign in." />
            <MemberForm member={member} />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Edit.layout = [SidebarLayout];
