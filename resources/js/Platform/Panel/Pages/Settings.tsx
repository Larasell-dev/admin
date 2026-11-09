import { Link, usePage } from "@inertiajs/react";
import { Icon } from "@larasell-dev/ui";
import { fbs } from "fbtee";
import type { SharedProps } from "../../../types";
import { navigationIcon } from "../../Support/navigationIcon";
import { Page } from "../Components/Page";
import SidebarLayout from "../Layouts/SidebarLayout";

/** The settings index: a card for each settings page, the panel's own and plugins' alike. */
export default function Settings() {
  const { settingsPages } = usePage<SharedProps>().props;

  return (
    <>
      <Page.Header
        title={fbs("Settings", "Settings page: title of the page that lists every settings page")}
      />
      <div className="min-h-0 flex-1 overflow-y-auto p-6">
        {/* Pulls the cards out by their padding, so their icons line up with the header title and sit as far from the top as from the left. */}
        <ul className="-mx-5 -mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {settingsPages.map((page) => (
            <li key={page.id} className="flex">
              <Link
                href={page.url}
                prefetch="hover"
                className="flex flex-1 items-start gap-4 p-5 outline-none hover:bg-state-hover focus-visible:focus-ring"
              >
                <span className="flex shrink-0 text-base-secondary">
                  <Icon icon={navigationIcon(page.icon, false)} size={6} />
                </span>
                <span className="flex min-w-0 flex-1 flex-col gap-1">
                  <span className="type-body-md-strong text-base-primary">{page.label}</span>
                  {page.description && (
                    <span className="type-body-md text-pretty text-base-secondary">
                      {page.description}
                    </span>
                  )}
                </span>
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </>
  );
}

Settings.layout = [SidebarLayout];
