import "../../../../css/app.css";
import { router } from "@inertiajs/react";
import { type MockInstance, afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import ExportsToast from "./ExportsToast";

type Export = Larasell.Admin.Platform.Audit.Export.Data.AuditLogExportData;

function anExport(overrides: Partial<Export>): Export {
  return {
    id: 1,
    status: "ready",
    records: 12,
    createdAt: "2026-11-09T14:00:00+00:00",
    expiresAt: "2026-11-10T14:00:00+00:00",
    downloadUrl: "/admin/settings/audit-log/exports/01?signature=x",
    deleteUrl: "/admin/settings/audit-log/exports/01",
    ...overrides,
  };
}

let destroy: MockInstance<typeof router.delete>;

beforeEach(() => {
  destroy = vi.spyOn(router, "delete").mockImplementation(() => {});
});

afterEach(() => {
  vi.restoreAllMocks();
});

/** The options the toast passed to its delete request, to answer it as the server would. */
const request = () => destroy.mock.calls[0]![1]!;

async function confirmDelete(typed: string) {
  await render(<ExportsToast exports={[anExport({})]} />);
  await toast().getByRole("button", { name: "Show exports" }).click();
  await toast().getByRole("button", { name: "Delete export" }).click();
  await page.getByRole("alertdialog").getByRole("textbox").fill(typed);
  await page.getByRole("alertdialog").getByRole("button", { name: "Delete" }).click();
}

const toast = () => page.getByRole("region", { name: "Exports" });

describe("ExportsToast", () => {
  it("shows nothing without exports", async () => {
    // Act
    await render(<ExportsToast exports={[]} />);

    // Assert
    await expect.element(toast()).not.toBeInTheDocument();
  });

  it("sums up how many exports there are and how many are in progress, collapsed", async () => {
    // Act
    await render(
      <ExportsToast
        exports={[
          anExport({ id: 3, status: "pending", records: null, downloadUrl: null }),
          anExport({ id: 2, status: "pending", records: null, downloadUrl: null }),
          anExport({ id: 1 }),
        ]}
      />,
    );

    // Assert
    await expect.element(toast()).toHaveTextContent("2 of 3 exports in progress");
    await expect.element(toast().getByRole("list")).not.toBeInTheDocument();
  });

  it("lists each export, newest first, once expanded", async () => {
    // Arrange
    await render(
      <ExportsToast
        exports={[
          anExport({ id: 2, status: "pending", records: null, downloadUrl: null }),
          anExport({ id: 1, records: 100018 }),
          anExport({ id: 0, status: "failed", records: null, downloadUrl: null }),
        ]}
      />,
    );

    // Act
    await toast().getByRole("button", { name: "Show exports" }).click();

    // Assert
    const items = toast().getByRole("listitem");
    await expect.element(items.nth(0).getByLabelText("Preparing export")).toBeVisible();
    await expect
      .element(items.nth(1).getByRole("link", { name: "Download 100018 records" }))
      .toHaveAttribute("href", "/admin/settings/audit-log/exports/01?signature=x");
    await expect.element(items.nth(1).getByRole("link")).toHaveTextContent("");
    await expect.element(items.nth(2).getByRole("img", { name: "Export failed" })).toBeVisible();
    await expect
      .element(toast().getByRole("button", { name: "Hide exports" }))
      .toHaveAttribute("aria-expanded", "true");
  });

  it("says when every export is ready", async () => {
    // Act
    await render(<ExportsToast exports={[anExport({})]} />);

    // Assert
    await expect.element(toast()).toHaveTextContent("1 export ready");
  });
});

it("asks to type a confirmation before deleting an export", async () => {
  // Arrange
  await render(
    <ExportsToast exports={[anExport({ deleteUrl: "/admin/settings/audit-log/exports/01" })]} />,
  );
  await toast().getByRole("button", { name: "Show exports" }).click();

  // Act
  await toast().getByRole("button", { name: "Delete export" }).click();

  // Assert
  const dialog = page.getByRole("alertdialog");
  await expect.element(dialog.getByLabelText('Type "Delete export" to confirm')).toBeVisible();
  await expect.element(dialog.getByRole("button", { name: "Delete" })).toBeEnabled();
});

it("deletes an export once the admin confirms, with what they typed", async () => {
  // Act
  await confirmDelete("Delete export");
  request().onStart?.({} as never);
  request().onSuccess?.({} as never);
  request().onFinish?.({} as never);

  // Assert
  expect(destroy).toHaveBeenCalledWith(
    "/admin/settings/audit-log/exports/01",
    expect.objectContaining({ data: { confirmation: "Delete export" } }),
  );
  await expect.element(page.getByRole("alertdialog")).not.toBeInTheDocument();
});

it("keeps the dialog open with why, when the server refuses the delete", async () => {
  // Act
  await confirmDelete("delete");
  request().onStart?.({} as never);
  request().onError?.({ confirmation: 'Type "Delete export" to confirm.' });
  request().onFinish?.({} as never);

  // Assert
  const dialog = page.getByRole("alertdialog");
  await expect.element(dialog.getByText('Type "Delete export" to confirm.')).toBeVisible();
  await expect.element(dialog.getByRole("button", { name: "Delete" })).toBeEnabled();
});
