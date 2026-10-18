import type { Icon } from "@larasell-dev/ui";
import * as icons from "@larasell-dev/ui/icons";
import type { ComponentProps } from "react";
import { assertDefined } from "ts-extras";

type IconData = ComponentProps<typeof Icon>["icon"];

/**
 * Resolves a NavIcon case name such as "FileList3" to its filled variant for the
 * active item and its line variant otherwise. Icons that come in one style only,
 * such as "KanbanView", resolve to that style.
 *
 * Nav items name their icon at runtime, so this imports the whole icon set
 * instead of letting the bundler keep only the icons it sees imported.
 */
export function navigationIcon(name: string, active: boolean): IconData {
  const set: Record<string, IconData | undefined> = icons;
  const icon = set[`${name}${active ? "Fill" : "Line"}`] ?? set[name];
  assertDefined(icon, `Navigation icon [${name}] is not exported by @larasell-dev/ui/icons.`);

  return icon;
}
