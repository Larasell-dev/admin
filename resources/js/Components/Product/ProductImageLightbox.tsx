import { Dialog } from "@base-ui/react/dialog";
import { ButtonGroup, Field, Icon, IconButton, Input, Tooltip } from "@larasell-dev/ui";
import {
  AspectRatioLine,
  CloseLine,
  FileImageLine,
  PaintFill,
  PaintLine,
  ZoomInLine,
  ZoomOutLine,
} from "@larasell-dev/ui/icons";
import { HotkeysProvider } from "@tanstack/react-hotkeys";
import { cn } from "cn";
import { useRef, useState } from "react";
import { TransformComponent, TransformWrapper } from "react-zoom-pan-pinch";
import { overlayExclusive } from "../../utils/hotkeys";
import { fbs } from "fbtee";

type Image = Larasell.Admin.Product.Data.ProductImageData;

/**
 * A product image over the whole viewport. Its file's name in the header opens the file in a new
 * tab; the sidebar edits its alt text, which it only reports, so the caller decides when it's saved.
 * The image sits on a solid background or, to tell its transparent parts apart, a checkerboard. It
 * zooms with the buttons, the wheel or a pinch, and pans by dragging once it's larger than the
 * screen.
 */
export default function ProductImageLightbox({
  open,
  image,
  onClose,
  onAltChange,
}: {
  open: boolean;
  /** The image to show; keep the last one while closing, so it fades out with the lightbox. */
  image: Image;
  onClose: () => void;
  /** Called with the alt text on every change. */
  onAltChange: (alt: string) => void;
}) {
  // Kept while the lightbox stays mounted, so the next image opens on the same background.
  const [checkerboard, setCheckerboard] = useState(false);
  const altInput = useRef<HTMLInputElement>(null);

  return (
    <Dialog.Root open={open} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Popup
          // The alt text is what's edited here, and focusing the file's name first would show its
          // tooltip on every open.
          initialFocus={altInput}
          className={cn(
            "fixed inset-0 z-50 flex flex-col bg-base-canvas outline-none",
            "transition-opacity duration-200 data-ending-style:opacity-0 data-starting-style:opacity-0",
            "motion-reduce:transition-none",
          )}
        >
          <HotkeysProvider scope="image-lightbox" exclusive={overlayExclusive} enabled={open}>
            <header className="border-base-subtle flex items-center justify-between gap-4 border-b px-4 py-3">
              {/* The title names the dialog after the file. */}
              <Dialog.Title className="type-body-md min-w-0">
                <Tooltip.Root>
                  <Tooltip.Trigger
                    render={
                      <a
                        href={image.url}
                        target="_blank"
                        rel="noreferrer"
                        className="flex items-center gap-2 text-base-primary hover:underline focus-visible:focus-ring"
                      >
                        <Icon icon={FileImageLine} />
                        <span className="truncate">{image.filename}</span>
                      </a>
                    }
                  />
                  <Tooltip.Popup side="bottom" align="start">
                    <fbt desc="Product image lightbox: tooltip of the image's file name in the header, which opens the image file in a new browser tab">
                      Open in a new tab
                    </fbt>
                  </Tooltip.Popup>
                </Tooltip.Root>
              </Dialog.Title>
              <Dialog.Close
                render={
                  <IconButton
                    variant="tertiary"
                    aria-label={fbs(
                      "Close",
                      "Product image lightbox: accessible name of the button that closes the full-screen image view",
                    )}
                  >
                    <Icon icon={CloseLine} />
                  </IconButton>
                }
              />
            </header>
            {/* The sidebar goes under the image while the viewport is too narrow for both. */}
            <div className="flex min-h-0 flex-1 flex-col md:flex-row">
              {/* Keyed by the image, so each one opens fitted to the screen again. */}
              <TransformWrapper key={image.id} maxScale={8} doubleClick={{ mode: "toggle" }}>
                {({ zoomIn, zoomOut, resetTransform }) => (
                  <div className="relative flex min-h-0 flex-1">
                    <div
                      data-checkerboard={checkerboard || undefined}
                      className={cn(
                        "flex min-h-0 flex-1 cursor-grab bg-base-section active:cursor-grabbing",
                        // Squares of two colors, like image editors show behind transparency.
                        "data-checkerboard:bg-base-surface data-checkerboard:bg-[repeating-conic-gradient(var(--border-color-base-medium)_0_25%,transparent_0_50%)] data-checkerboard:bg-size-[16px_16px]",
                      )}
                    >
                      {/* Both fill the area, which the library sizes to their content otherwise,
                              so the image starts centered and fitted, and zooms within it. Inline,
                              since the library's own styles override utility classes. */}
                      <TransformComponent
                        wrapperStyle={{ width: "100%", height: "100%" }}
                        contentStyle={{ width: "100%", height: "100%", padding: 24 }}
                        contentClass="items-center justify-center"
                      >
                        <img
                          src={image.url}
                          alt={image.alt ?? ""}
                          className="max-h-full max-w-full object-contain"
                        />
                      </TransformComponent>
                    </div>
                    <div className="absolute top-3 right-3 flex gap-2">
                      <ButtonGroup
                        label={fbs(
                          "Zoom",
                          "Product image lightbox: accessible name of the group of zoom out, fit to screen and zoom in buttons",
                        )}
                      >
                        <IconButton
                          variant="secondary"
                          aria-label={fbs(
                            "Zoom out",
                            "Product image lightbox: accessible name of the button that makes the image smaller",
                          )}
                          onClick={() => zoomOut()}
                        >
                          <Icon icon={ZoomOutLine} />
                        </IconButton>
                        <IconButton
                          variant="secondary"
                          aria-label={fbs(
                            "Fit to screen",
                            "Product image lightbox: accessible name of the button that resets the zoom so the whole image fits",
                          )}
                          onClick={() => resetTransform()}
                        >
                          <Icon icon={AspectRatioLine} />
                        </IconButton>
                        <IconButton
                          variant="secondary"
                          aria-label={fbs(
                            "Zoom in",
                            "Product image lightbox: accessible name of the button that makes the image larger",
                          )}
                          onClick={() => zoomIn()}
                        >
                          <Icon icon={ZoomInLine} />
                        </IconButton>
                      </ButtonGroup>
                      <IconButton
                        variant="secondary"
                        aria-label={fbs(
                          "Checkerboard background",
                          "Product image lightbox: accessible name of the toggle button that shows a checkerboard behind the image, so its transparent parts stand out",
                        )}
                        aria-pressed={checkerboard}
                        onClick={() => setCheckerboard((on) => !on)}
                      >
                        {/* The background a click switches to: filled for solid, outlined for
                                the checkerboard. */}
                        <Icon icon={checkerboard ? PaintFill : PaintLine} />
                      </IconButton>
                    </div>
                  </div>
                )}
              </TransformWrapper>
              <aside className="border-base-subtle border-t p-4 md:w-80 md:border-t-0 md:border-l">
                <Field
                  label={fbs(
                    "Alt text",
                    "Product image lightbox: label of the field for the image's alternative text, read to people who can't see it",
                  )}
                  name="alt"
                  description={fbs(
                    "Describes the image to those who can't see it.",
                    "Product image lightbox: hint below the alt text field",
                  )}
                >
                  <Input
                    ref={altInput}
                    value={image.alt ?? ""}
                    onChange={(event) => onAltChange(event.currentTarget.value)}
                    // As long as the server keeps it.
                    maxLength={255}
                    autoComplete="off"
                  />
                </Field>
              </aside>
            </div>
          </HotkeysProvider>
        </Dialog.Popup>
      </Dialog.Portal>
    </Dialog.Root>
  );
}
