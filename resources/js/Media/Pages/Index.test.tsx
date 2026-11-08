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
};

function props(overrides: Partial<Props> = {}): Props {
  return {
    media: [boots, unboxing],
    pagination: { page: 1, pageSize: 24, total: 2 },
    storeUrl: "/admin/media",
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
