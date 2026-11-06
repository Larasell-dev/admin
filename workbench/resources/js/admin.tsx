import { Larasell, SectionForm } from "@larasell-dev/admin";

Larasell.booting(({ sections }) => {
  sections.register(SectionForm.Product, "workbench-notes", () => import("./ProductNotesSection"));
});
