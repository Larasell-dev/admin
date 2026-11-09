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
type Media = Larasell.Admin.Media.Data.MediaItemData;
type Folder = Larasell.Admin.Media.Data.MediaFolderData;
/** The page's props, with its folders and media given apart and sent as its entries. */
type Setup = Omit<Props, "entries"> & { folders: Folder[]; media: Media[] };

const swatch = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"><rect width="8" height="8" fill="red"/></svg>`)}`;

/** A different picture than the original, so the test can tell which one the grid loads. */
const thumbnail = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"/>`)}`;

const boots: Media = {
  id: 1,
  type: "image",
  fileName: "boots.png",
  url: swatch,
  status: "ready",
  title: null,
  alt: "Brown boots",
  visibility: "public",
  thumbnailUrl: thumbnail,
  thumbHash: null,
  showUrl: "/admin/media/1",
  moveUrl: "/admin/media/1/move",
  moveToNewFolderUrl: "/admin/media/1/move-to-new-folder",
  destroyUrl: "/admin/media/1",
};
const unboxing: Media = {
  id: 2,
  type: "video",
  fileName: "unboxing.mp4",
  url: swatch,
  status: "ready",
  title: null,
  alt: null,
  visibility: "private",
  thumbnailUrl: null,
  thumbHash: null,
  showUrl: "/admin/media/2",
  moveUrl: "/admin/media/2/move",
  moveToNewFolderUrl: "/admin/media/2/move-to-new-folder",
  destroyUrl: "/admin/media/2",
};

function props(overrides: Partial<Setup> = {}): Setup {
  return {
    folderId: null,
    folders: [],
    tree: [],
    media: [boots, unboxing],
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

function renderIndex({ folders, media, ...initial }: Setup) {
  const entries = [
    ...folders.map((folder) => ({ key: `folder-${folder.id}`, folder, media: null })),
    ...media.map((item) => ({ key: `media-${item.id}`, folder: null, media: item })),
  ];
  return render(
    <App
      initialPage={
        {
          component: "Media/Pages/Index",
          url: "/admin/media",
          version: null,
          props: { errors: {}, ...initial, entries: { data: entries } },
          scrollProps: {
            entries: {
              pageName: "page",
              previousPage: null,
              nextPage: null,
              currentPage: 1,
              reset: false,
            },
          },
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

it("lists the folders before the media, in one grid", async () => {
  // Act
  await renderIndex(inCampaigns());

  // Assert
  const items = page.getByRole("listitem").elements();
  expect(items[0].textContent).toContain("Summer");
  expect(items[1].querySelector('[aria-label="Brown boots"]')).not.toBeNull();
});

it("switches between the grid and a list with names, and remembers the choice", async () => {
  // Arrange
  localStorage.removeItem("larasell-admin.media.view");
  const screen = await renderIndex(inCampaigns());
  await expect.element(page.getByText("boots.png")).not.toBeInTheDocument();

  // Act
  await page.getByRole("button", { name: "List view" }).click();

  // Assert
  await expect.element(page.getByText("boots.png")).toBeVisible();
  await expect
    .element(page.getByRole("button", { name: "List view" }))
    .toHaveAttribute("aria-pressed", "true");
  await screen.unmount();
  await renderIndex(inCampaigns());
  await expect.element(page.getByText("boots.png")).toBeVisible();
  localStorage.removeItem("larasell-admin.media.view");
});

it("says which items are still processing or failed, in the grid and the list", async () => {
  // Arrange
  localStorage.removeItem("larasell-admin.media.view");
  const media = [
    { ...unboxing, url: null, status: "processing" as const },
    { ...boots, url: null, thumbnailUrl: null, status: "failed" as const },
  ];

  // Act
  await renderIndex(props({ media }));

  // Assert
  await expect.element(page.getByText("Processing")).toBeVisible();
  await expect.element(page.getByText("Failed")).toBeVisible();
  await page.getByRole("button", { name: "List view" }).click();
  await expect.element(page.getByText("Processing")).toBeVisible();
  await expect.element(page.getByText("Failed")).toBeVisible();
  localStorage.removeItem("larasell-admin.media.view");
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
  const behind = (name: string) =>
    page.getByRole("link", { name }).element().querySelector<HTMLElement>("[style*=background]");
  const expected = thumbHashToDataURL(Uint8Array.from(atob(hash), (c) => c.charCodeAt(0)));
  expect(behind("Brown boots")?.style.backgroundImage).toBe(`url("${expected}")`);
  expect(behind("unboxing.mp4")).toBeNull();
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
function inCampaigns(): Setup {
  return props({
    folderId: 10,
    folders: [summerFolder],
    tree: [
      { id: 10, name: "Campaigns", parentId: null, url: "/admin/media?folder=10" },
      { id: 11, name: "Summer", parentId: 10, url: "/admin/media?folder=11" },
    ],
  });
}

/** Presses, moves and releases the pointer the way a browser does, one frame per move. */
async function pointerDrag(
  from: Element,
  onto: Element,
  { pointerType = "mouse", hold = 0, release = true } = {},
) {
  const center = (element: Element) => {
    const box = element.getBoundingClientRect();
    return { x: box.left + box.width / 2, y: box.top + box.height / 2 };
  };
  const pointer = (type: string, at: { x: number; y: number }, element: Element = document.body) =>
    element.dispatchEvent(
      new PointerEvent(type, {
        bubbles: true,
        cancelable: true,
        pointerId: 1,
        pointerType,
        isPrimary: true,
        buttons: type === "pointerup" ? 0 : 1,
        clientX: at.x,
        clientY: at.y,
      }),
    );
  const start = center(from);
  const end = center(onto);
  pointer("pointerdown", start, from);
  if (hold > 0) await new Promise((resolve) => setTimeout(resolve, hold));
  for (let step = 1; step <= 10; step++) {
    pointer("pointermove", {
      x: start.x + ((end.x - start.x) * step) / 10,
      y: start.y + ((end.y - start.y) * step) / 10,
    });
    await new Promise(requestAnimationFrame);
  }
  if (!release) return;
  pointer("pointerup", end);
  // dnd-kit ends a drag a frame after the pointer is released.
  await new Promise(requestAnimationFrame);
  await new Promise(requestAnimationFrame);
}

/** Drags a media tile, by its name, onto an element with the mouse. */
function dragTile(name: string, onto: Element, options?: Parameters<typeof pointerDrag>[2]) {
  return pointerDrag(page.getByRole("link", { name }).element(), onto, options);
}

it("moves a media item dropped onto a folder tile into that folder", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());

  // Act
  await dragTile("Brown boots", page.getByRole("link", { name: /Summer/ }).element());

  // Assert
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: 11 }, expect.anything());
});

it("highlights a folder tile while an item is dragged over it, and says it lands there", async () => {
  // Arrange
  await renderIndex(inCampaigns());
  const tile = page.getByRole("link", { name: /Summer/ }).element();

  // Act
  await dragTile("Brown boots", tile, { release: false });

  // Assert
  await expect.element(page.getByText("Move into Summer")).toBeVisible();
  expect(tile.getAttribute("data-drop-target")).toBe("over");
});

it("moves a media item dropped onto a breadcrumb there, and not onto the current one", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());

  // Act
  await dragTile("Brown boots", page.getByText("Campaigns", { exact: true }).element());
  await dragTile("Brown boots", page.getByRole("link", { name: "All media" }).element());

  // Assert
  expect(post).toHaveBeenCalledOnce();
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: null }, expect.anything());
});

it("drags an item on touch only after a long press, so a swipe still scrolls", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const summer = page.getByRole("link", { name: /Summer/ }).element();

  // Act
  await dragTile("Brown boots", summer, { pointerType: "touch" });
  const afterSwipe = post.mock.calls.length;
  await dragTile("Brown boots", summer, { pointerType: "touch", hold: 400 });

  // Assert
  expect(afterSwipe).toBe(0);
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: 11 }, expect.anything());
});

it("ignores drops while a folder dialog is open", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());
  const allMedia = page.getByRole("link", { name: "All media" }).element();
  await page.getByRole("button", { name: "Actions for Summer" }).click();
  await page.getByRole("menuitem", { name: "Rename" }).click();
  await expect.element(page.getByRole("dialog")).toBeVisible();

  // Act
  // The open dialog hides the page from the accessibility tree, so find the tile by its label.
  await pointerDrag(document.querySelector('[aria-label="Brown boots"]')!, allMedia);

  // Assert
  expect(allMedia.getAttribute("data-drop-target")).toBeNull();
  expect(post).not.toHaveBeenCalled();
});

it("asks for a name when an item is dropped onto New folder, and moves it in once created", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());

  // Act
  await dragTile("Brown boots", page.getByRole("button", { name: "New folder" }).element());
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
  await dragTile("Brown boots", page.getByRole("button", { name: "New folder" }).element());

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
  await dragTile("Brown boots", page.getByRole("link", { name: /Summer/ }).element());

  // Assert
  await expect.element(page.getByText("The folder no longer exists.")).toBeVisible();
});

it("moves an item dropped onto a folder's menu button into that folder", async () => {
  // Arrange
  const post = vi.spyOn(router, "post").mockImplementation(() => {});
  await renderIndex(inCampaigns());

  // Act
  await dragTile("Brown boots", page.getByRole("button", { name: "Actions for Summer" }).element());

  // Assert
  expect(post).toHaveBeenCalledWith("/admin/media/1/move", { folder_id: 11 }, expect.anything());
});

it("deletes a media item from its menu once the admin typed what the server asks for", async () => {
  // Arrange
  const del = vi.spyOn(router, "delete").mockImplementation((_url, options) => {
    if (options?.data && (options.data as { confirmation: string }).confirmation !== "Delete media")
      options.onError?.({ confirmation: 'Type "Delete media" to confirm.' });
  });
  await renderIndex(props());
  await page.getByRole("button", { name: "Actions for Brown boots" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();
  const confirm = page.getByRole("button", { name: "Delete", exact: true });

  // Act
  await confirm.click();
  await expect.element(page.getByText('Type "Delete media" to confirm.')).toBeVisible();
  await page.getByLabelText('Type "Delete media" to confirm').fill("Delete media");
  await confirm.click();

  // Assert
  expect(del).toHaveBeenCalledTimes(2);
  expect(del).toHaveBeenLastCalledWith(
    "/admin/media/1",
    expect.objectContaining({ data: { confirmation: "Delete media" } }),
  );
});

it("lets the admin press Delete on an empty folder before typing its name, and the server says what to type", async () => {
  // Arrange
  const del = vi.spyOn(router, "delete").mockImplementation((_url, options) => {
    options?.onError?.({ confirmation: "Type the folder's name to confirm." });
  });
  await renderIndex(inCampaigns());
  await page.getByRole("button", { name: "Actions for Summer" }).click();
  await page.getByRole("menuitem", { name: "Delete" }).click();

  // Act
  await page.getByRole("button", { name: "Delete", exact: true }).click();

  // Assert
  expect(del).toHaveBeenCalledWith(
    "/admin/media/folders/11",
    expect.objectContaining({ data: { confirmation: "" } }),
  );
  await expect.element(page.getByText("Type the folder's name to confirm.")).toBeVisible();
});
