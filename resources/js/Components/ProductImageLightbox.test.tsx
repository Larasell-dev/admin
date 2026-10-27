import "../../css/app.css";
import { type ComponentProps, useState } from "react";
import { expect, it, vi } from "vitest";
import { page, userEvent } from "vitest/browser";
import { render } from "vitest-browser-react";
import ProductImageLightbox from "./ProductImageLightbox";

type Image = NonNullable<ComponentProps<typeof ProductImageLightbox>["image"]>;

/** A solid square, so the image renders without a server. */
const swatch = `data:image/svg+xml,${encodeURIComponent(`<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"><rect width="8" height="8" fill="red"/></svg>`)}`;

const front: Image = { id: 7, url: swatch, filename: "front.png", alt: "Boots from the front" };

/** Opens the lightbox on the image, keeping the alt text it reports like the product form does. */
function Opened({ image, onAltChange }: { image: Image; onAltChange?: (alt: string) => void }) {
  const [current, setCurrent] = useState<Image | null>(image);

  return (
    <ProductImageLightbox
      image={current}
      onClose={() => setCurrent(null)}
      onAltChange={(alt) => {
        onAltChange?.(alt);
        setCurrent((image) => image && { ...image, alt });
      }}
    />
  );
}

const lightbox = () => page.getByRole("dialog", { name: "front.png" });
const altText = () => lightbox().getByRole("textbox", { name: "Alt text" });
/** The area the image is shown in, behind it. */
const imageArea = () => lightbox().getByTestId("image-area");

it("shows the image over the whole viewport", async () => {
  await render(<Opened image={front} />);

  await expect.element(lightbox()).toBeVisible();
  await expect.element(lightbox().getByRole("img", { name: "Boots from the front" })).toBeVisible();
  const { width, height } = lightbox().element().getBoundingClientRect();
  expect([width, height]).toEqual([window.innerWidth, window.innerHeight]);
});

it("opens the file in a new tab from its name in the header", async () => {
  await render(<Opened image={front} />);

  const file = lightbox().getByRole("link", { name: "front.png" });
  await expect.element(file).toHaveAttribute("href", swatch);
  await expect.element(file).toHaveAttribute("target", "_blank");
  await expect.element(page.getByText("Open in a new tab")).not.toBeInTheDocument();

  await file.hover();

  await expect.element(page.getByText("Open in a new tab")).toBeVisible();
});

it.each([
  ["its close button", () => lightbox().getByRole("button", { name: "Close" }).click()],
  ["Escape", () => userEvent.keyboard("{Escape}")],
])("closes with %s", async (_, close) => {
  await render(<Opened image={front} />);
  await expect.element(lightbox()).toBeVisible();

  await close();

  await expect.element(lightbox()).not.toBeInTheDocument();
});

it("reports each change to the alt text", async () => {
  const onAltChange = vi.fn<(alt: string) => void>();
  await render(<Opened image={front} onAltChange={onAltChange} />);
  await expect.element(altText()).toHaveValue("Boots from the front");

  await userEvent.clear(altText());
  await userEvent.type(altText(), "Red");

  await expect.element(altText()).toHaveValue("Red");
  expect(onAltChange).toHaveBeenLastCalledWith("Red");
  // The image is described by what was typed.
  await expect.element(lightbox().getByRole("img", { name: "Red" })).toBeVisible();
});

it("switches the image's background between solid and a checkerboard", async () => {
  await render(<Opened image={front} />);
  const toggle = lightbox().getByRole("button", { name: "Checkerboard background" });
  // The area behind the image, which shows through a transparent one.
  const background = () => getComputedStyle(imageArea().element()).backgroundImage;
  await expect.element(toggle).toHaveAttribute("aria-pressed", "false");
  expect(background()).toBe("none");

  await toggle.click();

  await expect.element(toggle).toHaveAttribute("aria-pressed", "true");
  expect(background()).toContain("gradient");

  await toggle.click();

  await expect.element(toggle).toHaveAttribute("aria-pressed", "false");
  expect(background()).toBe("none");
});

it("starts with an empty alt text for an image without one", async () => {
  await render(<Opened image={{ ...front, alt: null }} />);

  await expect.element(altText()).toHaveValue("");
});
