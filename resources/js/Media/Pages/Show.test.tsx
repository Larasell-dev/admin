import "../../../css/app.css";
import { App, http, usePage } from "@inertiajs/react";
import { ToastProvider } from "@larasell-dev/ui";
import { afterEach, expect, it, vi } from "vitest";
import { page } from "vitest/browser";
import { render } from "vitest-browser-react";
import Show from "./Show";

type Props = Parameters<typeof Show>[0];
type Media = Props["media"];

/** A solid square, so the image renders without a server. */
const swatch = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"><rect width="8" height="8" fill="red"/></svg>`)}`;

const boots: Media = {
  id: 7,
  type: "image",
  fileName: "boots.png",
  url: swatch,
  title: "Front",
  description: null,
  alt: "Boots from the front",
  visibility: "private",
  assets: [
    {
      role: "original",
      visibility: "private",
      fileName: "boots.png",
      mimeType: "image/png",
      size: 245_760,
      width: 1600,
      height: 1200,
      url: swatch,
    },
  ],
};

function props(media: Media = boots): Props {
  return {
    media,
    updateUrl: "/admin/media/7",
    destroyUrl: "/admin/media/7",
    indexUrl: "/admin/media",
    contentLocale: null,
  };
}

// Without the sidebar layout, which needs the whole panel's props.
function ShowPage() {
  return (
    <ToastProvider>
      <Show {...usePage<Props>().props} />
    </ToastProvider>
  );
}

function renderShow(initial: Props) {
  return render(
    <App
      initialPage={
        {
          component: "Media/Pages/Show",
          url: "/admin/media/7",
          version: null,
          props: { errors: {}, ...initial },
        } as never
      }
      initialComponent={ShowPage}
      resolveComponent={() => ShowPage}
    />,
  );
}

/** Answers every request with this page again, and records each request. */
function fakeServer(next: Props) {
  const requests: { method: string; url: string; data: unknown }[] = [];
  vi.spyOn(http, "getClient").mockReturnValue({
    async request({ method, url, data }) {
      requests.push({ method, url, data });
      const page = {
        component: "Media/Pages/Show",
        url: "/admin/media/7",
        version: null,
        props: { errors: {}, ...next },
      };
      return { status: 200, data: JSON.stringify(page), headers: { "x-inertia": "true" } };
    },
  });
  return requests;
}

afterEach(() => {
  vi.restoreAllMocks();
});

const dialog = () => page.getByRole("dialog", { name: "boots.png" });

it("shows the image over the whole viewport, named after its file", async () => {
  // Act
  await renderShow(props());

  // Assert
  await expect.element(dialog()).toBeVisible();
  await expect.element(dialog().getByRole("img", { name: "Boots from the front" })).toBeVisible();
  const { width, height } = dialog().element().getBoundingClientRect();
  expect([width, height]).toEqual([window.innerWidth, window.innerHeight]);
  // The file's name sits at the top left, the close button at the top right.
  const title = dialog()
    .getByRole("heading", { name: "boots.png" })
    .element()
    .getBoundingClientRect();
  const close = dialog().getByRole("button", { name: "Close" }).element().getBoundingClientRect();
  expect(title.left).toBeLessThan(window.innerWidth / 4);
  expect(close.right).toBeGreaterThan(window.innerWidth * 0.9);
  expect(Math.abs(title.top - close.top)).toBeLessThan(24);
});

it("switches the background between solid and a checkerboard", async () => {
  // Arrange
  await renderShow(props());
  const toggle = dialog().getByRole("button", { name: "Checkerboard background" });
  const stage = dialog().getByTestId("media-stage");
  await expect.element(toggle).toHaveAttribute("aria-pressed", "false");
  const solid = getComputedStyle(stage.element()).backgroundImage;

  // Act
  await toggle.click();

  // Assert
  await expect.element(toggle).toHaveAttribute("aria-pressed", "true");
  expect(solid).toBe("none");
  expect(getComputedStyle(stage.element()).backgroundImage).toContain("conic-gradient");
});

const zoom = () => dialog().getByRole("group", { name: "Zoom" });
const image = () => dialog().getByRole("img", { name: "Boots from the front" });

it("zooms the image with the buttons left of the background toggle", async () => {
  // Arrange
  await renderShow(props());
  await expect.element(image()).toBeVisible();
  const fitted = image().element().getBoundingClientRect().width;

  // Act
  await zoom().getByRole("button", { name: "Zoom in" }).click();

  // Assert
  const toggle = dialog().getByRole("button", { name: "Checkerboard background" });
  expect(zoom().element().getBoundingClientRect().right).toBeLessThanOrEqual(
    toggle.element().getBoundingClientRect().left,
  );
  await vi.waitFor(() =>
    expect(image().element().getBoundingClientRect().width).toBeGreaterThan(fitted * 1.2),
  );
});

it("fits the zoomed image to the screen again", async () => {
  // Arrange
  await renderShow(props());
  await expect.element(image()).toBeVisible();
  const fitted = image().element().getBoundingClientRect().width;
  await zoom().getByRole("button", { name: "Zoom in" }).click();
  await vi.waitFor(() =>
    expect(image().element().getBoundingClientRect().width).toBeGreaterThan(fitted * 1.2),
  );

  // Act
  await zoom().getByRole("button", { name: "Fit to screen" }).click();

  // Assert
  await vi.waitFor(() =>
    expect(Math.round(image().element().getBoundingClientRect().width)).toBe(Math.round(fitted)),
  );
});

it("offers no zoom for a video, whose controls need the pointer", async () => {
  // Act
  await renderShow(props(unboxing));

  // Assert
  const video = page.getByRole("dialog", { name: "unboxing.mp4" });
  await expect
    .element(video.getByRole("button", { name: "Checkerboard background" }))
    .toBeVisible();
  await expect.element(video.getByRole("group", { name: "Zoom" })).not.toBeInTheDocument();
});

const poster = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="4" height="3"/>`)}`;
const unboxing: Media = {
  ...boots,
  type: "video",
  fileName: "unboxing.mp4",
  alt: null,
  assets: [
    {
      ...boots.assets[0]!,
      fileName: "unboxing.mp4",
      mimeType: "video/mp4",
      width: null,
      height: null,
    },
    {
      ...boots.assets[0]!,
      role: "thumbnail",
      fileName: "unboxing.webp",
      mimeType: "image/webp",
      width: 400,
      height: 300,
      url: poster,
    },
  ],
};
const videoDialog = () => page.getByRole("dialog", { name: "unboxing.mp4" });

it("plays a video with its own controls instead of the browser's", async () => {
  // Act
  await renderShow(props(unboxing));

  // Assert
  const stage = videoDialog().getByTestId("media-stage");
  const player = stage.element().querySelector("video");
  expect(player?.getAttribute("src")).toBe(swatch);
  expect(player?.controls).toBe(false);
  await expect.element(videoDialog().getByRole("button", { name: "Play" })).toBeVisible();
  await expect.element(videoDialog().getByRole("button", { name: "Mute" })).toBeVisible();
  await expect
    .element(videoDialog().getByRole("button", { name: "Enter fullscreen" }))
    .toBeVisible();
  await expect.element(videoDialog().getByRole("slider", { name: "Seek" })).toBeVisible();
});

it("keeps the video's shape from the start, showing its own first frame", async () => {
  // Act
  await renderShow(props(unboxing));

  // Assert
  const frame = videoDialog().getByTestId("video-frame").element() as HTMLElement;
  // From the poster, before the video has loaded anything: the frame doesn't jump.
  const { width, height } = frame.getBoundingClientRect();
  expect(width / height).toBeCloseTo(4 / 3, 1);
  expect(width).toBeGreaterThan(200);
  // The video's own first frame, not the smaller poster image.
  expect(frame.querySelector("img")).toBeNull();
  expect(frame.querySelector("video")?.preload).toBe("auto");
});

it("falls back to a widescreen frame for a video without a poster yet", async () => {
  // Act
  await renderShow(props({ ...unboxing, assets: [unboxing.assets[0]!] }));

  // Assert
  const { width, height } = videoDialog()
    .getByTestId("video-frame")
    .element()
    .getBoundingClientRect();
  expect(width / height).toBeCloseTo(16 / 9, 1);
});

it("previews every asset with the thumbnail, never downloading the original", async () => {
  // Arrange
  const small = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="2" height="2"/>`)}`;

  // Act
  await renderShow(
    props({
      ...boots,
      assets: [
        { ...boots.assets[0]!, url: "/admin/media/7/file" },
        { ...boots.assets[0]!, role: "thumbnail", mimeType: "image/webp", url: small },
      ],
    }),
  );

  // Assert
  const assets = dialog().getByRole("list", { name: "Assets" });
  await expect.element(assets).toBeVisible();
  const sources = [...assets.element().querySelectorAll("img")].map((img) =>
    img.getAttribute("src"),
  );
  expect(sources).toEqual([small, small]);
  const links = [...assets.element().querySelectorAll("a")].map((link) =>
    link.getAttribute("href"),
  );
  expect(links).toEqual(["/admin/media/7/file", small]);
});

it("shows a placeholder for the assets until the thumbnail is made", async () => {
  // Act
  await renderShow(props());

  // Assert
  const assets = dialog().getByRole("list", { name: "Assets" });
  await expect.element(assets).toBeVisible();
  expect(assets.element().querySelectorAll("img")).toHaveLength(0);
});

it("names the thumbnail among the assets", async () => {
  // Act
  await renderShow(
    props({
      ...boots,
      assets: [
        ...boots.assets,
        {
          ...boots.assets[0]!,
          role: "thumbnail",
          fileName: "boots.webp",
          mimeType: "image/webp",
          width: 400,
          height: 300,
        },
      ],
    }),
  );

  // Assert
  const assets = dialog().getByRole("list", { name: "Assets" });
  await expect.element(assets).toBeVisible();
  expect(assets.element().textContent).toContain("Thumbnail");
  expect(assets.element().textContent).toContain("image/webp");
});

it("lists the media's assets in the sidebar", async () => {
  // Act
  await renderShow(props());

  // Assert
  const assets = dialog().getByRole("list", { name: "Assets" });
  await expect.element(assets).toBeVisible();
  const text = assets.element().textContent;
  expect(text).toContain("Original");
  expect(text).toContain("Private");
  expect(text).toContain("image/png");
  expect(text).toContain("1600 × 1200");
  expect(text).toContain("240 KB");
});

it("saves the title and description from the unsaved changes toast", async () => {
  // Arrange
  const requests = fakeServer(
    props({ ...boots, title: "Front view", description: "Shot by Studio X." }),
  );
  await renderShow(props());
  await expect.element(dialog().getByRole("textbox", { name: "Title" })).toHaveValue("Front");

  // Act
  await dialog().getByRole("textbox", { name: "Title" }).fill("Front view");
  await dialog().getByRole("textbox", { name: "Description" }).fill("Shot by Studio X.");
  await page
    .getByRole("region", { name: "Unsaved changes" })
    .getByRole("button", { name: "Save" })
    .click();

  // Assert
  await vi.waitFor(() => expect(requests.map((request) => request.method)).toContain("put"));
  const save = requests.find((request) => request.method === "put")!;
  const data = JSON.stringify(
    save.data instanceof FormData ? Object.fromEntries(save.data) : save.data,
  );
  expect(data).toContain('"title":"Front view"');
  expect(data).toContain('"description":"Shot by Studio X."');
});

it("takes a description over several lines", async () => {
  // Act
  await renderShow(props());

  // Assert
  const description = dialog().getByRole("textbox", { name: "Description" });
  await expect.element(description).toBeVisible();
  expect(description.element().tagName).toBe("TEXTAREA");
  // Four lines of 20px plus the padding, whatever the content.
  expect(description.element().getBoundingClientRect().height).toBeGreaterThanOrEqual(100);
});

it("saves the alt text and visibility from the unsaved changes toast", async () => {
  // Arrange
  const requests = fakeServer(props({ ...boots, alt: "Brown boots", visibility: "public" }));
  await renderShow(props());
  await expect.element(dialog().getByRole("button", { name: "Save" })).not.toBeInTheDocument();

  // Act
  await dialog().getByRole("textbox", { name: "Alt text" }).fill("Brown boots");
  await dialog().getByRole("combobox", { name: "Visibility" }).click();
  await page.getByRole("option", { name: "Public" }).click();
  await page
    .getByRole("region", { name: "Unsaved changes" })
    .getByRole("button", { name: "Save" })
    .click();

  // Assert
  await vi.waitFor(() => expect(requests.map((request) => request.method)).toContain("put"));
  const save = requests.find((request) => request.method === "put")!;
  expect(save.url).toContain("/admin/media/7");
  const data = JSON.stringify(
    save.data instanceof FormData ? Object.fromEntries(save.data) : save.data,
  );
  expect(data).toContain('"alt":"Brown boots"');
  expect(data).toContain('"visibility":"public"');
});

it("goes back to the library when closed", async () => {
  // Arrange
  const requests = fakeServer(props());
  await renderShow(props());

  // Act
  await dialog().getByRole("button", { name: "Close" }).click();

  // Assert
  await vi.waitFor(() =>
    expect(requests).toContainEqual(
      expect.objectContaining({ method: "get", url: expect.stringMatching(/\/admin\/media$/) }),
    ),
  );
});

it('deletes the media only once "Delete media" is typed', async () => {
  // Arrange
  const requests = fakeServer(props());
  await renderShow(props());
  await dialog().getByRole("button", { name: "Delete" }).click();
  const field = page
    .getByRole("alertdialog")
    .getByRole("textbox", { name: 'Type "Delete media" to confirm' });

  // Act
  await field.fill("Delete media");
  await page.getByRole("alertdialog").getByRole("button", { name: "Delete" }).click();

  // Assert
  await vi.waitFor(() => expect(requests.map((request) => request.method)).toContain("delete"));
  const request = requests.find((request) => request.method === "delete")!;
  expect(request.url).toContain("/admin/media/7");
  expect(JSON.stringify(request.data)).toContain('"confirmation":"Delete media"');
});

it("keeps Delete enabled and shows the server's answer when the text doesn't match", async () => {
  // Arrange
  vi.spyOn(http, "getClient").mockReturnValue({
    async request() {
      const next = {
        component: "Media/Pages/Show",
        url: "/admin/media/7",
        version: null,
        props: { ...props(), errors: { confirmation: 'Type "Delete media" to confirm.' } },
      };
      return { status: 200, data: JSON.stringify(next), headers: { "x-inertia": "true" } };
    },
  });
  await renderShow(props());
  await dialog().getByRole("button", { name: "Delete" }).click();
  const confirm = page.getByRole("alertdialog").getByRole("button", { name: "Delete" });
  await expect.element(confirm).toBeEnabled();

  // Act
  await confirm.click();

  // Assert
  await vi.waitFor(() =>
    expect(page.getByRole("alertdialog").element().textContent).toContain("to confirm."),
  );
});

it("dims the media behind the delete dialog", async () => {
  // Arrange
  await renderShow(props());

  // Act
  await dialog().getByRole("button", { name: "Delete" }).click();

  // Assert
  await expect.element(page.getByRole("alertdialog")).toBeVisible();
  await vi.waitFor(() => expect(document.querySelectorAll("[data-overlay]").length).toBe(1));
});
