import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import MemberForm from "../../Components/Team/MemberForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Team.Data.CreateMemberPageData;

export default function Create({ storeUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={fbs(
          "New member",
          "New team member page: title of the page with the form that adds an admin to the panel",
        )}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to team",
            "New team member page: accessible name of the back arrow that returns to the list of team members",
          ),
        }}
      />
      <Page.Body>
        <Form action={storeUrl} method="post">
          <Section.Root>
            <Section.Header
              title={fbs(
                "General",
                "New team member page: heading of the form section with the team member's name, email and password",
              )}
              description={fbs(
                "Who the member is and how they sign in.",
                "New team member page: explains the form section with the team member's name, email and password",
              )}
            />
            <MemberForm />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Create.layout = [SidebarLayout];
