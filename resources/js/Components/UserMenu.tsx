import { Avatar } from "@base-ui/react/avatar";
import { Menu } from "@base-ui/react/menu";
import { Link, router, usePage } from "@inertiajs/react";
import { Icon } from "@larasell-dev/ui";
import { ExpandUpDownLine, LogoutBoxRLine, UserLine } from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import type { ComponentProps } from "react";
import { assertPresent } from "ts-extras";
import type { Page } from "../types";
import { overlayExclusive } from "../utils/hotkeys";
import { initials } from "../utils/initials";

export default function UserMenu() {
  const { admin, profileUrl, logoutUrl } = usePage<Page>().props;
  assertPresent(admin, "The user menu needs a signed-in admin.");

  return (
    <Menu.Root>
      <Menu.Trigger className="flex w-full border-t border-border cursor-pointer h-[59px] items-center gap-3 px-6 text-left outline-none hover:bg-mauve-50 focus-visible:focus-ring focus-visible:-outline-offset-2 data-popup-open:bg-mauve-50 dark:hover:bg-mauve-900 dark:data-popup-open:bg-mauve-900">
        <Avatar.Root className="inline-flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mauve-100 type-body-md text-mauve-700 dark:bg-mauve-800 dark:text-mauve-200">
          {admin.avatarUrl && (
            <Avatar.Image src={admin.avatarUrl} alt="" className="size-full object-cover" />
          )}
          <Avatar.Fallback>{initials(admin.name)}</Avatar.Fallback>
        </Avatar.Root>
        <span className="flex min-w-0 flex-1 flex-col">
          <span className="truncate type-body-md-strong text-mauve-950 dark:text-mauve-50">
            {admin.name}
          </span>
          <span className="truncate type-body-sm text-mauve-500 dark:text-mauve-400">
            {admin.email}
          </span>
        </span>
        <span className="flex shrink-0 text-base text-mauve-500 dark:text-mauve-400">
          <Icon icon={ExpandUpDownLine} />
        </span>
      </Menu.Trigger>

      <Menu.Portal>
        <Menu.Positioner
          side="top"
          align="end"
          sideOffset={6}
          alignOffset={12}
          className="z-50 w-58"
        >
          <Menu.Popup className="origin-(--transform-origin) border border-border bg-white bg-clip-padding shadow-lg outline-none transition-[opacity,translate] duration-150 data-ending-style:translate-y-1 data-ending-style:opacity-0 data-starting-style:translate-y-1 data-starting-style:opacity-0 dark:bg-mauve-900">
            <HotkeysProvider scope="user-menu" exclusive={overlayExclusive}>
              <UserMenuItem icon={UserLine} render={<Link href={profileUrl} />}>
                Profile
              </UserMenuItem>
              <UserMenuItem icon={LogoutBoxRLine} onClick={() => router.post(logoutUrl)}>
                Log out
              </UserMenuItem>
            </HotkeysProvider>
          </Menu.Popup>
        </Menu.Positioner>
      </Menu.Portal>
    </Menu.Root>
  );
}

function UserMenuItem({
  icon,
  children,
  ...props
}: Omit<Menu.Item.Props, "className"> & Pick<ComponentProps<typeof Icon>, "icon">) {
  return (
    <Menu.Item
      {...props}
      className="flex w-full cursor-pointer items-center gap-2 px-3.5 py-2.5 type-body-md text-mauve-950 outline-none select-none hover:bg-mauve-50 data-highlighted:bg-mauve-100 dark:text-mauve-100 dark:hover:bg-mauve-700 dark:data-highlighted:bg-mauve-800"
    >
      <span className="flex text-base text-mauve-500 dark:text-mauve-400">
        <Icon icon={icon} />
      </span>
      {children}
    </Menu.Item>
  );
}
