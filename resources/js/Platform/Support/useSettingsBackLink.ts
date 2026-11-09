import { usePage } from "@inertiajs/react";
import { fbs } from "fbtee";
import type { SharedProps } from "../../types";

/** The header's back link from a settings page, plugins' included, to the settings index. */
export function useSettingsBackLink() {
  return {
    href: usePage<SharedProps>().props.settingsUrl,
    label: fbs(
      "Back to settings",
      "Settings pages: accessible name of the back arrow in the header that goes to the list of all settings",
    ),
  };
}
