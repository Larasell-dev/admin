import { Link } from "@inertiajs/react";
import { Icon } from "@larasell-dev/ui";
import { FoldersLine, QuestionLine, ShoppingBagLine, UserAddLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { ComponentProps } from "react";
import { Page } from "../Components/Page";
import SidebarLayout from "../Layouts/SidebarLayout";

type Props = Larasell.Admin.Platform.Panel.Data.HomePageData;

export default function Home({
  createProductUrl,
  createCategoryUrl,
  inviteMemberUrl,
  helpEmail,
}: Props) {
  return (
    <>
      <Page.Header
        title={fbs(
          "Dashboard",
          "Home page: title of the panel's start page, which suggests first steps for setting up the store",
        )}
      />
      <div className="min-h-0 flex-1 overflow-y-auto">
        <div className="flex min-h-full items-center justify-center p-6">
          <ul className="grid w-full max-w-3xl gap-4 md:grid-cols-2">
            {createProductUrl && (
              <OnboardingLink
                href={createProductUrl}
                icon={ShoppingBagLine}
                title={fbs(
                  "Create product",
                  "Home page: title of the suggested first step that opens the form to create a product",
                )}
                description={fbs(
                  "Add your first product with its name, images and price.",
                  "Home page: explains the suggested first step that opens the form to create a product",
                )}
              />
            )}
            {createCategoryUrl && (
              <OnboardingLink
                href={createCategoryUrl}
                icon={FoldersLine}
                title={fbs(
                  "Create category",
                  "Home page: title of the suggested first step that opens the form to create a product category",
                )}
                description={fbs(
                  "Group your products so customers can browse your store.",
                  "Home page: explains the suggested first step that opens the form to create a product category",
                )}
              />
            )}
            <OnboardingLink
              href={inviteMemberUrl}
              icon={UserAddLine}
              title={fbs(
                "Invite your team",
                "Home page: title of the suggested step that opens the form to add another admin to the panel",
              )}
              description={fbs(
                "Give your team access to manage the store with you.",
                "Home page: explains the suggested step that opens the form to add another admin to the panel",
              )}
            />
            <OnboardingLink
              href={`mailto:${helpEmail}`}
              icon={QuestionLine}
              title={fbs(
                "Need help?",
                "Home page: title of the link that writes an email to the panel's support contact",
              )}
              description={fbs(
                ["Write to ", fbs.param("helpEmail", helpEmail), " and I'll get back to you."],
                "Home page: below 'Need help?', invites the admin to email the panel's support contact, whose email address is filled in",
              )}
              external
            />
          </ul>
        </div>
      </div>
    </>
  );
}

Home.layout = [SidebarLayout];

type OnboardingLinkProps = {
  href: string;
  icon: ComponentProps<typeof Icon>["icon"];
  title: string;
  description: string;
  external?: boolean;
};

function OnboardingLink({ href, icon, title, description, external }: OnboardingLinkProps) {
  const className =
    "flex flex-1 items-start gap-4 p-5 outline-none hover:bg-state-hover focus-visible:focus-ring";
  const content = (
    <>
      <span className="flex shrink-0 text-base-secondary">
        <Icon icon={icon} size={8} />
      </span>
      <span className="flex min-w-0 flex-1 flex-col gap-1">
        <span className="type-body-md-strong text-base-primary">{title}</span>
        <span className="type-body-md text-base-secondary">{description}</span>
      </span>
    </>
  );

  return (
    <li className="flex">
      {external ? (
        <a href={href} className={className}>
          {content}
        </a>
      ) : (
        <Link href={href} prefetch="hover" className={className}>
          {content}
        </Link>
      )}
    </li>
  );
}
