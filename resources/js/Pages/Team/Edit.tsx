import { Form } from "@inertiajs/react";
import { Section } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import MemberForm from "../../Components/Team/MemberForm";
import { Page } from "../../Components/Page";
import UnsavedChangesToast from "../../Components/UnsavedChangesToast";
import SidebarLayout from "../../Layouts/SidebarLayout";

type Props = Larasell.Admin.Team.Data.EditMemberPageData;

export default function Edit({ member, updateUrl, indexUrl }: Props) {
  return (
    <>
      <Page.Header
        title={member.name}
        backLink={{
          href: indexUrl,
          label: fbs(
            "Back to team",
            "Team member page: accessible name of the back arrow that returns to the list of team members",
          ),
        }}
      />
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
            <Section.Header
              title={fbs(
                "General",
                "Team member page: heading of the form section with the team member's name, email and password",
              )}
              description={fbs(
                "Who the member is and how they sign in.",
                "Team member page: explains the form section with the team member's name, email and password",
              )}
            />
            <MemberForm member={member} />
          </Section.Root>
          <UnsavedChangesToast />
        </Form>
      </Page.Body>
    </>
  );
}

Edit.layout = [SidebarLayout];
