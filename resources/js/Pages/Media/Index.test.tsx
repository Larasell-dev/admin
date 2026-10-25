import "../../../css/app.css";
import { App, router } from "@inertiajs/react";
import { afterEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { MediaTableResource } from "../../Components/Media/MediaTable";
import Index from "./Index";

function renderMedia(total: number) {
  const table: MediaTableResource = {
    title: "Media",
    backLink: null,
    emptyState: { title: "No media yet", description: null, icon: null },
    columns: [
      { name: "name", label: "Name", type: "text" },
      { name: "type", label: "Type", type: "text" },
    ],
    rows:
      total === 0
        ? []
        : [
            {
              id: "folder-1",
              name: "Campaigns",
              type: "Folder",
              _actions: [],
              _link: "/admin/media/folders/1",
              _type: "folder",
              _deleteUrl: "/admin/media/folders/1",
            },
            {
              id: "product-image-4",
              name: "apple.jpg",
              type: "Product image",
              _actions: [],
              _link: "https://cdn.example.com/products/apple.jpg",
              _type: "product-image",
              _deleteUrl: "/admin/media/product-images/4",
            },
          ],
    pagination: { page: 1, pageSize: 25, total },
  };
  const initialPage = {
    component: "Media/Index",
    url: "/admin/media",
    version: null,
    props: {
      table,
      navigation: [
        {
          id: "media",
          label: "Media",
          url: `${window.location.origin}/admin/media`,
          icon: "Image",
          children: [],
        },
      ],
      admin: { name: "Ada Lovelace", email: "ada@example.com", avatarUrl: null },
      profileUrl: "/admin/profile",
      logoutUrl: "/admin/logout",
      panelUrl: "/admin",
    },
  };

  return render(
    <App
      initialPage={initialPage as never}
      initialComponent={Index}
      resolveComponent={() => Index}
    />,
  );
}

afterEach(() => {
  vi.restoreAllMocks();
});

type VisitCallbacks = {
  onStart?: () => void;
  onSuccess?: () => unknown;
  onError?: (errors: Record<string, string>) => void;
  onFinish?: () => void;
};

it("links folders to their page and product images to their file", async () => {
  await renderMedia(2);

  await expect
    .element(page.getByRole("link", { name: "Campaigns" }))
    .toHaveAttribute("href", "/admin/media/folders/1");
  await expect
    .element(page.getByRole("link", { name: "apple.jpg" }))
    .toHaveAttribute("href", "https://cdn.example.com/products/apple.jpg");
});

/** Stubs the request, handing back the callbacks to play the server's part. */
function stubDelete() {
  const destroy = vi.spyOn(router, "delete").mockImplementation(() => {});
  return { destroy, callbacks: () => destroy.mock.calls[0][1] as VisitCallbacks };
}

it("deletes a folder with its contents after confirming", async () => {
  const { destroy, callbacks } = stubDelete();
  await renderMedia(2);

  await page.getByRole("button", { name: "Actions for Campaigns" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();

  const dialog = page.getByRole("alertdialog", { name: "Delete folder" });
  await expect.element(dialog).toBeVisible();
  await expect.element(dialog.getByText(/everything in it/)).toBeVisible();
  expect(destroy).not.toHaveBeenCalled();

  await dialog.getByRole("textbox", { name: 'Type "Campaigns" to confirm' }).fill("Campaigns");
  await dialog.getByRole("button", { name: "Delete" }).click();
  expect(destroy.mock.calls[0][0]).toBe("/admin/media/folders/1");
  expect(destroy.mock.calls[0][1]).toMatchObject({ data: { confirmation: "Campaigns" } });

  callbacks().onStart?.();
  await expect
    .element(dialog.getByRole("button", { name: "Delete" }))
    .toHaveAttribute("aria-busy", "true");

  await callbacks().onSuccess?.();
  callbacks().onFinish?.();
  await expect.element(page.getByRole("alertdialog")).not.toBeInTheDocument();
});

it("deletes a product image after confirming", async () => {
  const { destroy } = stubDelete();
  await renderMedia(2);

  await page.getByRole("button", { name: "Actions for apple.jpg" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();

  const dialog = page.getByRole("alertdialog", { name: "Delete product image" });
  await expect.element(dialog.getByText(/removed from every product/)).toBeVisible();

  await dialog.getByRole("textbox", { name: 'Type "apple.jpg" to confirm' }).fill("apple.jpg");
  await dialog.getByRole("button", { name: "Delete" }).click();
  expect(destroy.mock.calls[0][0]).toBe("/admin/media/product-images/4");
  expect(destroy.mock.calls[0][1]).toMatchObject({ data: { confirmation: "apple.jpg" } });
});

it("deletes nothing when cancelling", async () => {
  const { destroy } = stubDelete();
  await renderMedia(2);

  await page.getByRole("button", { name: "Actions for Campaigns" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();
  await page.getByRole("button", { name: "Cancel" }).click();

  await expect.element(page.getByRole("alertdialog")).not.toBeInTheDocument();
  expect(destroy).not.toHaveBeenCalled();
});

it("keeps the dialog open with the server's error when the name doesn't match", async () => {
  const { callbacks } = stubDelete();
  await renderMedia(2);

  await page.getByRole("button", { name: "Actions for Campaigns" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();
  const dialog = page.getByRole("alertdialog", { name: "Delete folder" });
  await dialog.getByRole("textbox", { name: 'Type "Campaigns" to confirm' }).fill("campaigns");
  await dialog.getByRole("button", { name: "Delete" }).click();

  callbacks().onStart?.();
  callbacks().onError?.({ confirmation: 'Type "Campaigns" to confirm.' });
  callbacks().onFinish?.();

  await expect.element(dialog.getByText('Type "Campaigns" to confirm.')).toBeVisible();
  await expect.element(dialog).toBeVisible();
});

it("starts with an empty name for the next deletion", async () => {
  stubDelete();
  await renderMedia(2);

  await page.getByRole("button", { name: "Actions for Campaigns" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();
  await page.getByRole("textbox", { name: 'Type "Campaigns" to confirm' }).fill("Camp");
  await page.getByRole("button", { name: "Cancel" }).click();
  await expect.element(page.getByRole("alertdialog")).not.toBeInTheDocument();

  await page.getByRole("button", { name: "Actions for apple.jpg" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();
  await expect
    .element(page.getByRole("textbox", { name: 'Type "apple.jpg" to confirm' }))
    .toHaveValue("");
});
