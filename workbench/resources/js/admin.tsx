import { Larasell } from "@larasell-dev/admin";

Larasell.booting(({ sections }) => {
  sections.register("product", "workbench-notes", () => import("./ProductNotesSection"));
});
