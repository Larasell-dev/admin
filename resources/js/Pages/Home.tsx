import { Link } from "@inertiajs/react";
import { Icon } from "@larasell-dev/ui";
import { FoldersLine, QuestionLine, ShoppingBagLine, UserAddLine } from "@larasell-dev/ui/icons";
import type { ComponentProps } from "react";
import { Page } from "../Components/Page";
import SidebarLayout from "../Layouts/SidebarLayout";

type Props = Larasell.Admin.Data.View.HomePageData;

type Item = {
  href: string;
  icon: ComponentProps<typeof Icon>["icon"];
  title: string;
  description: string;
  external?: boolean;
};

export default function Home({
  createProductUrl,
  createCategoryUrl,
  inviteAdminUrl,
  helpEmail,
}: Props) {
  const items: Item[] = [
    ...(createProductUrl
      ? [
          {
            href: createProductUrl,
            icon: ShoppingBagLine,
            title: "Create product",
            description: "Add your first product with its name, images and price.",
          },
        ]
      : []),
    {
      href: createCategoryUrl,
      icon: FoldersLine,
      title: "Create category",
      description: "Group your products so customers can browse your store.",
    },
    {
      href: inviteAdminUrl,
      icon: UserAddLine,
      title: "Invite other admins",
      description: "Give your team access to manage the store with you.",
    },
    {
      href: `mailto:${helpEmail}`,
      icon: QuestionLine,
      title: "Need help?",
      description: `Write to ${helpEmail} and I'll get back to you.`,
      external: true,
    },
  ];

  return (
    <>
      <Page.Header title="Dashboard" />
      <div className="min-h-0 flex-1 overflow-y-auto">
        <div className="flex min-h-full items-center justify-center p-6">
          <ul className="grid w-full max-w-3xl gap-4 md:grid-cols-2">
            {items.map((item) => {
              const content = (
                <>
                  <span className="flex shrink-0 text-3xl text-mauve-600 dark:text-mauve-300">
                    <Icon icon={item.icon} />
                  </span>
                  <span className="flex min-w-0 flex-1 flex-col gap-1">
                    <span className="type-body-md-strong text-mauve-950 dark:text-mauve-50">
                      {item.title}
                    </span>
                    <span className="type-body-md text-mauve-600 dark:text-mauve-400">
                      {item.description}
                    </span>
                  </span>
                </>
              );
              const className =
                "flex flex-1 items-start gap-4 p-5 outline-none hover:bg-mauve-50 focus-visible:focus-ring dark:hover:bg-mauve-900";

              return (
                <li key={item.title} className="flex">
                  {item.external ? (
                    <a href={item.href} className={className}>
                      {content}
                    </a>
                  ) : (
                    <Link href={item.href} prefetch="hover" className={className}>
                      {content}
                    </Link>
                  )}
                </li>
              );
            })}
          </ul>
        </div>
      </div>
    </>
  );
}

Home.layout = [SidebarLayout];
