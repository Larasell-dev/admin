import "../../../css/app.css";
import { App, router } from "@inertiajs/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import type { MediaTableResource } from "../../Components/Media/MediaTable";
import Index from "./Index";

function renderMedia(total: number | null) {
  const table: MediaTableResource | null =
    total === null
      ? null
      : {
          title: "Media",
          backLink: null,
          emptyState: { title: "No media yet", description: null, icon: null },
          columns: [
            { name: "name", label: "Name", type: "text" },
            { name: "added_at", label: "Added at", type: "text" },
          ],
          rows:
            total === 0
              ? []
              : [
                  {
                    id: "folder-1",
                    name: "Campaigns",
                    added_at: "2026-10-20T09:30:00+00:00",
                    _actions: [],
                    _link: "/admin/media/folders/1",
                    _type: "folder",
                    _images: {
                      name: { type: "icon", icon: "Folder", style: null, alt: null, title: null },
                    },
                    _deleteUrl: "/admin/media/folders/1",
                  },
                  {
                    id: "product-image-4",
                    name: "apple.jpg",
                    added_at: null,
                    _actions: [],
                    _link: "https://cdn.example.com/products/apple.jpg",
                    _type: "product-image",
                    _images: {
                      name: {
                        type: "url",
                        url: "https://cdn.example.com/products/apple.jpg",
                        style: "square",
                        alt: null,
                        title: null,
                      },
                    },
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
      createFolderUrl: "/admin/media/folders",
      uploadUrl: "/admin/media/product-images",
      folderId: 7,
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

const uploadButtons = () => page.getByRole("button", { name: "Upload" }).elements();

it("offers to upload a file or create a folder in the header", async () => {
  await renderMedia(1);

  // With rows there is no empty state, so the only buttons are the header's.
  await expect.element(page.getByRole("button", { name: "Upload" })).toBeVisible();
  expect(uploadButtons()).toHaveLength(1);

  await page.getByRole("button", { name: "More actions" }).click();
  await expect.element(page.getByRole("menuitem", { name: "New folder" })).toBeVisible();
});

it("offers the same buttons in the empty state", async () => {
  await renderMedia(0);

  await expect.element(page.getByText("No media yet")).toBeVisible();
  expect(uploadButtons()).toHaveLength(2);

  await page.getByRole("button", { name: "More actions" }).last().click();
  await expect.element(page.getByRole("menuitem", { name: "New folder" })).toBeVisible();
});

it("explains a folder that does not exist, without offering to add media", async () => {
  await renderMedia(null);

  await expect
    .element(page.getByRole("heading", { level: 1, name: "Folder not found" }))
    .toBeVisible();
  await expect.element(page.getByText("This folder doesn't exist anymore")).toBeVisible();
  expect(uploadButtons()).toHaveLength(0);
  expect(page.getByRole("button", { name: "More actions" }).elements()).toHaveLength(0);
});

it("asks for the new folder's name in a dialog", async () => {
  await renderMedia(1);

  await page.getByRole("button", { name: "More actions" }).click();
  await page.getByRole("menuitem", { name: "New folder" }).click();

  const dialog = page.getByRole("dialog", { name: "New folder" });
  await expect.element(dialog).toBeVisible();
  await expect.element(dialog.getByRole("textbox", { name: "Name" })).toHaveFocus();
  await expect.element(dialog.getByRole("button", { name: "Create" })).toBeVisible();

  await dialog.getByRole("button", { name: "Cancel" }).click();
  await expect.element(page.getByRole("dialog")).not.toBeInTheDocument();
});

it("opens the same dialog from the empty state", async () => {
  await renderMedia(0);

  await page.getByRole("button", { name: "More actions" }).last().click();
  await page.getByRole("menuitem", { name: "New folder" }).click();

  await expect.element(page.getByRole("dialog", { name: "New folder" })).toBeVisible();
});

afterEach(() => {
  vi.restoreAllMocks();
});

type VisitCallbacks = {
  onStart?: () => void;
  onSuccess?: () => unknown;
  onError?: (errors: Record<string, string>) => void;
  onFinish?: () => void;
};

/** Stubs the request, handing back what was sent and the callbacks to play the server's part. */
function stubPost() {
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  return {
    post,
    callbacks: () => post.mock.calls[0][2] as VisitCallbacks,
  };
}

async function openNewFolderDialog() {
  await page.getByRole("button", { name: "More actions" }).first().click();
  await page.getByRole("menuitem", { name: "New folder" }).click();
  return page.getByRole("dialog", { name: "New folder" });
}

it("creates the folder inside the current one and closes the dialog", async () => {
  const { post, callbacks } = stubPost();
  await renderMedia(1);

  const dialog = await openNewFolderDialog();
  await dialog.getByRole("textbox", { name: "Name" }).fill("Lookbook");
  await dialog.getByRole("button", { name: "Create" }).click();

  expect(post).toHaveBeenCalledTimes(1);
  expect(post.mock.calls[0][0]).toBe("/admin/media/folders");
  expect(post.mock.calls[0][1]).toEqual({ name: "Lookbook", parent_id: 7 });

  callbacks().onStart?.();
  await expect
    .element(dialog.getByRole("button", { name: "Create" }))
    .toHaveAttribute("aria-busy", "true");

  await callbacks().onSuccess?.();
  callbacks().onFinish?.();
  await expect.element(page.getByRole("dialog")).not.toBeInTheDocument();

  // The next folder starts with an empty name.
  await expect
    .element((await openNewFolderDialog()).getByRole("textbox", { name: "Name" }))
    .toHaveValue("");
});

it("keeps the dialog open with the server's error for the name", async () => {
  const { callbacks } = stubPost();
  await renderMedia(1);

  const dialog = await openNewFolderDialog();
  await dialog.getByRole("button", { name: "Create" }).click();

  callbacks().onStart?.();
  callbacks().onError?.({ name: "The name field is required." });
  callbacks().onFinish?.();

  await expect.element(dialog.getByText("The name field is required.")).toBeVisible();
  await expect.element(dialog).toBeVisible();
});

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

it("shows when each item was added, in place of its type", async () => {
  await page.viewport(1280, 800);
  await renderMedia(2);

  const table = page.getByRole("table");
  await expect.element(table.getByRole("columnheader", { name: "Added at" })).toBeVisible();
  expect(table.getByRole("columnheader", { name: "Type" }).elements()).toHaveLength(0);
  // In the browser's own locale and timezone; the test browser runs in en-US.
  await expect.element(table.getByText(/Oct 2[01], 2026/)).toBeVisible();
});

describe.each([
  { layout: "data list", width: 400 },
  { layout: "table", width: 1280 },
])("in the $layout", ({ width }) => {
  it("shows a folder icon for folders and the picture for product images", async () => {
    await page.viewport(width, 800);
    const screen = await renderMedia(2);

    const visible = (selector: string) =>
      [...screen.container.querySelectorAll<HTMLElement>(selector)].filter(
        (element) => element.offsetParent !== null,
      );

    // The picture only decorates the name next to it, so it has no alt text and the link keeps its name.
    expect(visible("img").map((img) => img.getAttribute("src"))).toEqual([
      "https://cdn.example.com/products/apple.jpg",
    ]);
    await expect.element(page.getByRole("link", { name: "apple.jpg", exact: true })).toBeVisible();
    // Both rows have the same actions trigger; only the folder's adds an icon before its name.
    const rowOf = (name: string) =>
      visible("a")
        .find((a) => a.textContent === name)!
        .closest(width < 1024 ? "li" : "tr")!;
    const icons = (name: string) => rowOf(name).querySelectorAll("svg").length;
    expect(icons("Campaigns")).toBe(icons("apple.jpg") + 1);
    expect(rowOf("Campaigns").querySelector("img")).toBeNull();
  });
});
