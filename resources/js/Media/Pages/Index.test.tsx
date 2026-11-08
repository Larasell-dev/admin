import { thumbHashToDataURL } from "thumbhash";
import "../../../css/app.css";
import { App, router, usePage } from "@inertiajs/react";
import { ToastProvider } from "@larasell-dev/ui";
import { afterEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import Index from "./Index";
import UploadToast from "../Components/UploadToast";
import { uploadQueue } from "../Components/uploadQueue";

type Props = Parameters<typeof Index>[0];
type Media = Props["media"][number];

const swatch = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"><rect width="8" height="8" fill="red"/></svg>`)}`;

/** A different picture than the original, so the test can tell which one the grid loads. */
const thumbnail = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"/>`)}`;

const boots: Media = {
  id: 1,
  type: "image",
  fileName: "boots.png",
  url: swatch,
  title: null,
  alt: "Brown boots",
  visibility: "public",
  thumbnailUrl: thumbnail,
  thumbHash: null,
  showUrl: "/admin/media/1",
  moveUrl: "/admin/media/1/move",
  moveToNewFolderUrl: "/admin/media/1/move-to-new-folder",
};
const unboxing: Media = {
  id: 2,
  type: "video",
  fileName: "unboxing.mp4",
  url: swatch,
  title: null,
  alt: null,
  visibility: "private",
  thumbnailUrl: null,
  thumbHash: null,
  showUrl: "/admin/media/2",
  moveUrl: "/admin/media/2/move",
  moveToNewFolderUrl: "/admin/media/2/move-to-new-folder",
};

function props(overrides: Partial<Props> = {}): Props {
  return {
    folderId: null,
    folders: [],
    tree: [],
    media: [boots, unboxing],
    pagination: { page: 1, pageSize: 24, total: 2 },
    storeUrl: "/admin/media",
    rootUrl: "/admin/media",
    createFolderUrl: "/admin/media/folders",
    ...overrides,
  };
}

function IndexPage() {
  return (
    <ToastProvider>
      <Index {...usePage<Props>().props} />
    </ToastProvider>
  );
}

function renderIndex(initial: Props) {
  return render(
    <App
      initialPage={
        {
          component: "Media/Pages/Index",
          url: "/admin/media",
          version: null,
          props: { errors: {}, ...initial },
        } as never
      }
      initialComponent={IndexPage}
      resolveComponent={() => IndexPage}
    />,
  );
}

afterEach(() => {
  vi.restoreAllMocks();
});

it("shows each media item as a link to it, named by its alt text or file name", async () => {
  // Act
  await renderIndex(props());

  // Assert
  await expect
    .element(page.getByRole("link", { name: "Brown boots" }))
    .toHaveAttribute("href", "/admin/media/1");
  await expect
    .element(page.getByRole("link", { name: "unboxing.mp4" }))
    .toHaveAttribute("href", "/admin/media/2");
  await expect.element(page.getByRole("img", { name: "Brown boots" })).toBeVisible();
});

it("shows videos by their poster, and never loads the video itself", async () => {
  // Act
  await renderIndex(
    props({
      media: [
        { ...unboxing, thumbnailUrl: thumbnail },
        { ...unboxing, id: 3, fileName: "pending.mp4", showUrl: "/admin/media/3" },
      ],
    }),
  );

  // Assert
  const poster = page.getByRole("link", { name: "unboxing.mp4" }).element();
  expect(poster.querySelector("img")?.getAttribute("src")).toBe(thumbnail);
  const pending = page.getByRole("link", { name: "pending.mp4" });
  await expect.element(pending).toBeVisible();
  expect(document.querySelector("video")).toBeNull();
  expect(pending.element().querySelector("img")).toBeNull();
});

it("pages with the tables' pagination, below the grid, even on one page", async () => {
  // Act
  await renderIndex(props());

  // Assert
  const grid = page.getByRole("list").first().element();
  const bar = page.getByTestId("media-pagination").element();
  await expect.element(page.getByTestId("media-pagination")).toBeVisible();
  expect(bar.contains(grid)).toBe(false);
  expect(bar.getBoundingClientRect().top).toBeGreaterThanOrEqual(
    grid.getBoundingClientRect().bottom,
  );
});

it("visits the page and page size the admin picks", async () => {
  // Arrange
  const visit = vi.spyOn(router, "get").mockImplementation(() => {});
  await renderIndex(props({ pagination: { page: 1, pageSize: 24, total: 60 } }));

  // Act
  await page.getByRole("button", { name: /next/i }).click();

  // Assert
  expect(visit).toHaveBeenCalledWith(
    window.location.pathname,
    expect.objectContaining({ page: 2, per_page: 24 }),
    expect.anything(),
  );
});

it("invites to upload the first media when there is none", async () => {
  // Act
  await renderIndex(props({ media: [] }));

  // Assert
  await expect.element(page.getByText("No media yet")).toBeVisible();
});

it("shows images by their thumbnail, and a placeholder until it is made", async () => {
  // Act
  await renderIndex(
    props({
      media: [
        boots,
        { ...boots, id: 3, alt: "Socks", thumbnailUrl: null, showUrl: "/admin/media/3" },
      ],
    }),
  );

  // Assert
  await expect
    .element(page.getByRole("img", { name: "Brown boots" }))
    .toHaveAttribute("src", thumbnail);
  const pending = page.getByRole("link", { name: "Socks" });
  await expect.element(pending).toBeVisible();
  expect(pending.element().querySelector("img")?.getAttribute("src")).not.toBe(swatch);
});

it("shows an image's ThumbHash behind it while it loads", async () => {
  // Arrange
  const hash = "1QcSHQRnh493V4dIh4eXh1h4kJUI";

  // Act
  await renderIndex(props({ media: [{ ...boots, thumbHash: hash }, unboxing] }));

  // Assert
  const tile = page.getByRole("link", { name: "Brown boots" }).element().querySelector("span")!;
  const expected = thumbHashToDataURL(Uint8Array.from(atob(hash), (c) => c.charCodeAt(0)));
  expect(tile.style.backgroundImage).toBe(`url("${expected}")`);
  const video = page.getByRole("link", { name: "unboxing.mp4" }).element() as HTMLElement;
  expect(video.style.backgroundImage).toBe("");
});

it("fades the thumbnail in over its ThumbHash once it has loaded", async () => {
  // Act
  await renderIndex(props({ media: [{ ...boots, thumbHash: "1QcSHQRnh493V4dIh4eXh1h4kJUI" }] }));

  // Assert
  const image = page.getByRole("img", { name: "Brown boots" });
  await vi.waitFor(() => expect(image.element().getAttribute("data-loaded")).toBe("true"));
  expect(getComputedStyle(image.element()).transitionProperty).toContain("opacity");
  await vi.waitFor(() => expect(getComputedStyle(image.element()).opacity).toBe("1"));
  await vi.waitFor(() =>
    expect((image.element().parentElement as HTMLElement).style.backgroundImage).toBe(""),
  );
});

it("names a media item by its title before its alt text", async () => {
  // Act
  await renderIndex(props({ media: [{ ...boots, title: "Summer campaign" }] }));

  // Assert
  await expect
    .element(page.getByRole("link", { name: "Summer campaign" }))
    .toHaveAttribute("href", "/admin/media/1");
});

it("sends each chosen file on its own and lists it in the upload toast", async () => {
  // Arrange
  const sent: string[] = [];
  uploadQueue.send = (_url, upload) => {
    sent.push(upload.file.name);
    return upload.file.name === "huge.png"
      ? Promise.resolve({ ok: false, error: "The file is too large to upload.", retryable: false })
      : Promise.resolve({ ok: true, mediaId: 9 });
  };
  uploadQueue.onSucceeded = () => {};
  // The grid reloads after each upload; a real reload would fail here and leave Inertia's error dialog open.
  vi.spyOn(router, "reload").mockImplementation(() => {});
  await renderIndex(props());
  await render(<UploadToast />);

  // Act
  await page
    .getByLabelText("Upload")
    .first()
    .upload([
      new File(["png"], "socks.png", { type: "image/png" }),
      new File(["png"], "huge.png", { type: "image/png" }),
    ]);

  // Assert
  await expect.element(page.getByText("1 uploaded, 1 failed")).toBeVisible();
  expect(sent).toEqual(["socks.png", "huge.png"]);
  const toggle = page.getByRole("button", { name: "Show files" });
  expect(toggle.elements()).toHaveLength(1);
  (toggle.element() as HTMLButtonElement).click();
  await expect.element(page.getByText("The file is too large to upload.")).toBeVisible();
  uploadQueue.dispatch({ type: "DISMISSED" });
});

type Folder = Props["folders"][number];

const summerFolder: Folder = {
  id: 11,
  name: "Summer",
  url: "/admin/media?folder=11",
  mediaCount: 0,
  folderCount: 0,
  renameUrl: "/admin/media/folders/11",
  destroyUrl: "/admin/media/folders/11",
};

/** Inside Campaigns, which holds Summer; boots is in Campaigns. */
function inCampaigns(): Props {
  return props({
    folderId: 10,
    folders: [summerFolder],
    tree: [
      { id: 10, name: "Campaigns", parentId: null, url: "/admin/media?folder=10" },
      { id: 11, name: "Summer", parentId: 10, url: "/admin/media?folder=11" },
    ],
  });
}

/** Fires a drag event the way the browser does, and says whether a target accepted it. */
function fire(type: string, target: Element, data: DataTransfer): boolean {
  return target.dispatchEvent(
    new DragEvent(type, { dataTransfer: data, bubbles: true, cancelable: true }),
  );
}

/** Starts dragging a media tile, so the data carries what the grid puts in it. */
function dragTile(name: string): DataTransfer {
  const data = new DataTransfer();
  fire("dragstart", page.getByRole("link", { name }).element(), data);
  return data;
}

function dropOn(target: Element, data: DataTransfer) {
  fire("dragenter", target, data);
  fire("dragover", target, data);
  return fire("drop", target, data);
}

it("moves a media item dropped onto a folder tile into that folder", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const data = dragTile("Brown boots");

  // Act
  const tile = page.getByRole("link", { name: /Summer/ }).element();
  const notCanceled = dropOn(tile, data);

  // Assert
  expect(notCanceled).toBe(false);
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: 11 }, expect.anything());
});

it("keeps a folder tile highlighted while the item crosses its children, and says it lands there", async () => {
  // Arrange
  await renderIndex(inCampaigns());
  const data = dragTile("Brown boots");
  const tile = page.getByRole("link", { name: /Summer/ }).element();
  const child = tile.querySelector("span")!;

  // Act
  fire("dragenter", tile, data);
  fire("dragenter", child, data);
  fire("dragleave", tile, data);
  fire("dragover", child, data);

  // Assert
  await expect.element(page.getByText("Move into Summer")).toBeVisible();
  expect(tile.getAttribute("data-drop-target")).toBe("over");
  fire("dragleave", child, data);
  await expect.element(page.getByText("Move into Summer")).not.toBeInTheDocument();
  expect(tile.getAttribute("data-drop-target")).toBeNull();
});

it("moves a media item dropped onto a breadcrumb there, and not onto the current one", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const data = dragTile("Brown boots");

  // Act
  const current = page.getByText("Campaigns", { exact: true }).element();
  fire("dragenter", current, data);
  fire("dragover", current, data);
  const onCurrent = data.dropEffect;
  dropOn(page.getByRole("link", { name: "All media" }).element(), data);

  // Assert
  expect(onCurrent).toBe("none");
  expect(post).toHaveBeenCalledOnce();
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: null }, expect.anything());
});

it("ignores files from the computer and text or links dragged from the page", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const tile = page.getByRole("link", { name: /Summer/ }).element();
  const file = new DataTransfer();
  file.items.add(new File(["png"], "socks.png", { type: "image/png" }));
  const link = new DataTransfer();
  link.setData("text/uri-list", "/admin/media?folder=10");
  link.setData("text/plain", "Campaigns");

  // Act
  const accepted = [dropOn(tile, file), dropOn(tile, link)];

  // Assert
  expect(accepted).toEqual([true, true]);
  expect(tile.getAttribute("data-drop-target")).toBeNull();
  expect(post).not.toHaveBeenCalled();
});

it("ignores drops while a folder dialog is open", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const data = dragTile("Brown boots");
  const allMedia = page.getByRole("link", { name: "All media" }).element();
  await page.getByRole("button", { name: "Actions for Summer" }).click();
  await page.getByRole("menuitem", { name: "Rename" }).click();
  await expect.element(page.getByRole("dialog")).toBeVisible();

  // Act
  const accepted = dropOn(allMedia, data);

  // Assert
  expect(accepted).toBe(false);
  expect(data.dropEffect).toBe("none");
  expect(allMedia.getAttribute("data-drop-target")).toBeNull();
  expect(post).not.toHaveBeenCalled();
});

it("asks for a name when an item is dropped onto New folder, and moves it in once created", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const data = dragTile("Brown boots");

  // Act
  dropOn(page.getByRole("button", { name: "New folder" }).element(), data);
  await page.getByLabelText("Name").fill("Beach");
  await page.getByRole("button", { name: "Create and move" }).click();

  // Assert
  await expect.element(page.getByText("Brown boots is moved into it.")).toBeVisible();
  expect(post).toHaveBeenCalledWith(
    "/admin/media/1/move-to-new-folder",
    { name: "Beach", parent_id: 10 },
    expect.anything(),
  );
});

it("creates no folder and moves nothing when the new folder dialog is canceled", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  dropOn(page.getByRole("button", { name: "New folder" }).element(), dragTile("Brown boots"));

  // Act
  await page.getByRole("button", { name: "Cancel" }).click();

  // Assert
  await expect.element(page.getByRole("dialog")).not.toBeInTheDocument();
  expect(post).not.toHaveBeenCalled();
});

it("says the folder no longer exists when it was deleted before the drop", async () => {
  // Arrange
  vi.spyOn(router, "post").mockImplementation((_url, _data, options) => {
    options?.onError?.({ folder: "The folder no longer exists." });
  });
  await renderIndex(inCampaigns());

  // Act
  dropOn(page.getByRole("link", { name: /Summer/ }).element(), dragTile("Brown boots"));

  // Assert
  await expect.element(page.getByText("The folder no longer exists.")).toBeVisible();
});

it("moves an item with Move to…, without dragging", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());

  // Act
  await page.getByRole("button", { name: "Actions for Brown boots" }).click();
  await page.getByRole("menuitem", { name: "Move to…" }).click();
  await page.getByRole("combobox", { name: "Folder" }).click();
  await page.getByRole("option", { name: "Campaigns / Summer" }).click();
  await page.getByRole("button", { name: "Move", exact: true }).click();

  // Assert
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: 11 }, expect.anything());
});

it("offers every folder but the current one in Move to…", async () => {
  // Arrange
  await renderIndex(inCampaigns());

  // Act
  await page.getByRole("button", { name: "Actions for Brown boots" }).click();
  await page.getByRole("menuitem", { name: "Move to…" }).click();
  await page.getByRole("combobox", { name: "Folder" }).click();

  // Assert
  await expect.element(page.getByRole("option", { name: "All media" })).toBeVisible();
  await expect.element(page.getByRole("option", { name: "Campaigns / Summer" })).toBeVisible();
  expect(page.getByRole("option", { name: "Campaigns", exact: true }).elements()).toHaveLength(0);
});
