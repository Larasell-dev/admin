import { Accordion } from "@base-ui/react/accordion";
import { Dialog } from "@base-ui/react/dialog";
import { Form, router } from "@inertiajs/react";
import {
  Button,
  ButtonGroup,
  EmptyState,
  Field,
  Icon,
  IconButton,
  Input,
  Select,
  Spinner,
} from "@larasell-dev/ui";
import {
  ArrowDownSLine,
  AspectRatioLine,
  CloseLine,
  DeleteBinLine,
  ErrorWarningLine,
  PaintFill,
  PaintLine,
  TimeLine,
  ZoomInLine,
  ZoomOutLine,
} from "@larasell-dev/ui/icons";
import { TransformComponent, TransformWrapper } from "react-zoom-pan-pinch";
import { cn } from "cn";
import { fbs } from "fbtee";
import { useId, useState, type ReactNode } from "react";
import { useStorage } from "../../Platform/Support/useStorage";
import type { SharedProps } from "../../types";
import VideoPlayer from "../Components/VideoPlayer";
import DelayedFallback from "../../Platform/Panel/Components/DelayedFallback";
import MediaAssets, { visibilityLabel } from "../Components/MediaAssets";
import { DeleteMediaDialog } from "../Components/DeleteMediaDialog";
import ContentLocaleSelect from "../../Platform/ContentLocale/Components/ContentLocaleSelect";
import UnsavedChangesToast, {
  UnsavedChangesToastHost,
} from "../../Platform/Panel/Components/UnsavedChangesToast";

type Props = Larasell.Admin.Media.Data.MediaShowPageData & Pick<SharedProps, "contentLocale">;
type Visibility = Larasell.Admin.Media.Data.MediaAssetData["visibility"];

/**
 * A media item over the whole viewport: its file's name at the top left, a close button at the top
 * right, the image or video in the middle on a solid background or a checkerboard, and a sidebar
 * to edit its alt text and see its assets. Closing goes back to the library.
 */
export default function Show({ media, updateUrl, destroyUrl, indexUrl, contentLocale }: Props) {
  const [checkerboard, setCheckerboard] = useState(false);
  // The toast draws inside the dialog, which sits above the page and makes everything else inert.
  const [popup, setPopup] = useState<HTMLDivElement | null>(null);
  const [deleting, setDeleting] = useState(false);
  const close = () => router.visit(indexUrl);

  return (
    <>
      <Dialog.Root open onOpenChange={(open) => !open && close()}>
        <Dialog.Portal>
          <Dialog.Popup
            ref={setPopup}
            className="fixed inset-0 z-50 flex flex-col bg-base-canvas outline-none"
          >
            <header className="flex items-center justify-between gap-4 border-b border-base-subtle px-4 py-3">
              <Dialog.Title className="min-w-0 truncate type-body-md text-base-primary">
                {media.fileName}
              </Dialog.Title>
              <div className="flex shrink-0 items-center gap-2">
                {/* The language the alt text is edited in. */}
                <ContentLocaleSelect contentLocale={contentLocale} />
                <Dialog.Close
                  render={
                    <IconButton
                      variant="tertiary"
                      aria-label={fbs(
                        "Close",
                        "Media page: accessible name of the button that closes the full-screen media view and returns to the library",
                      )}
                    >
                      <Icon icon={CloseLine} />
                    </IconButton>
                  }
                />
              </div>
            </header>
            <div className="flex min-h-0 flex-1 flex-col md:flex-row">
              {/* Keyed by the item, so each one opens fitted to the screen. Videos don't zoom: dragging
                  would fight their controls. */}
              <TransformWrapper
                key={media.id}
                maxScale={8}
                doubleClick={{ mode: "toggle" }}
                disabled={media.type === "video"}
              >
                {({ zoomIn, zoomOut, resetTransform }) => (
                  <div
                    // Stacked above the sidebar on narrow screens, it keeps half of the screen, whatever the
                    // sidebar holds; side by side, it takes all the height.
                    className="relative flex min-h-[50dvh] flex-1 md:min-h-0"
                  >
                    <div
                      data-testid="media-stage"
                      data-checkerboard={checkerboard || undefined}
                      className={cn(
                        "flex min-h-0 flex-1 bg-base-section",
                        media.type === "image" && "cursor-grab active:cursor-grabbing",
                        "data-checkerboard:bg-base-surface data-checkerboard:bg-[repeating-conic-gradient(var(--border-color-base-medium)_0_25%,transparent_0_50%)] data-checkerboard:bg-size-[16px_16px]",
                      )}
                    >
                      {media.url === null ? (
                        <NotReady status={media.status} failure={media.failure} />
                      ) : media.type === "video" ? (
                        <VideoPlayer
                          src={media.url}
                          width={posterOf(media)?.width ?? null}
                          height={posterOf(media)?.height ?? null}
                        />
                      ) : (
                        // Both fill the stage, which the library sizes to their content otherwise, so the
                        // image starts centered and fitted. Inline, since the library's styles win over classes.
                        <TransformComponent
                          wrapperStyle={{ width: "100%", height: "100%" }}
                          contentStyle={{ width: "100%", height: "100%", padding: 24 }}
                          contentClass="items-center justify-center"
                        >
                          <StageImage src={media.url} alt={media.alt ?? ""} />
                        </TransformComponent>
                      )}
                    </div>
                    <div className="absolute top-3 right-3 flex gap-2">
                      {media.type === "image" && (
                        <ButtonGroup
                          label={fbs(
                            "Zoom",
                            "Media page: accessible name of the group of zoom out, fit to screen and zoom in buttons",
                          )}
                        >
                          <IconButton
                            variant="secondary"
                            aria-label={fbs(
                              "Zoom out",
                              "Media page: accessible name of the button that makes the image smaller",
                            )}
                            onClick={() => zoomOut()}
                          >
                            <Icon icon={ZoomOutLine} />
                          </IconButton>
                          <IconButton
                            variant="secondary"
                            aria-label={fbs(
                              "Fit to screen",
                              "Media page: accessible name of the button that resets the zoom so the whole image fits",
                            )}
                            onClick={() => resetTransform()}
                          >
                            <Icon icon={AspectRatioLine} />
                          </IconButton>
                          <IconButton
                            variant="secondary"
                            aria-label={fbs(
                              "Zoom in",
                              "Media page: accessible name of the button that makes the image larger",
                            )}
                            onClick={() => zoomIn()}
                          >
                            <Icon icon={ZoomInLine} />
                          </IconButton>
                        </ButtonGroup>
                      )}
                      <IconButton
                        variant="secondary"
                        aria-label={fbs(
                          "Checkerboard background",
                          "Media page: accessible name of the toggle button that shows a checkerboard behind the media, so its transparent parts stand out",
                        )}
                        aria-pressed={checkerboard}
                        onClick={() => setCheckerboard((on) => !on)}
                      >
                        <Icon icon={checkerboard ? PaintFill : PaintLine} />
                      </IconButton>
                    </div>
                  </div>
                )}
              </TransformWrapper>
              <aside className="flex min-h-0 flex-col overflow-y-auto border-t border-base-subtle md:w-80 md:border-t-0 md:border-l">
                <SidebarSections
                  general={
                    <Form
                      // Inertia keeps this page mounted when moving between items; remounting resets the fields.
                      key={`${updateUrl}-${contentLocale?.current}`}
                      action={updateUrl}
                      method="put"
                      options={{ preserveScroll: true }}
                      // The saved values become the new baseline, so the form is no longer dirty.
                      setDefaultsOnSuccess
                      className="flex flex-col gap-4"
                    >
                      <Field
                        label={fbs(
                          "Title",
                          "Media page: label of the field for the media's short name, shown in the library and as a caption",
                        )}
                        name="title"
                      >
                        <Input
                          defaultValue={media.title ?? ""}
                          maxLength={255}
                          autoComplete="off"
                        />
                      </Field>
                      <Field
                        label={fbs(
                          "Description",
                          "Media page: label of the field for longer text about the media, e.g. a caption or notes on its use",
                        )}
                        name="description"
                      >
                        <Input
                          render={
                            <textarea
                              rows={4}
                              className="scroll-mask-y"
                              // Four to twelve lines, growing with the text, then scrolling with a
                              // fade. Inline, since the input's own classes would win over utilities.
                              style={{
                                minHeight: "calc(4lh + 1.25rem)",
                                maxHeight: "calc(12lh + 1.25rem)",
                                fieldSizing: "content",
                                resize: "vertical",
                              }}
                            />
                          }
                          defaultValue={media.description ?? ""}
                          maxLength={2000}
                        />
                      </Field>
                      <Field
                        label={fbs(
                          "Alt text",
                          "Media page: label of the field for the media's alternative text, read to people who can't see it",
                        )}
                        name="alt"
                        description={fbs(
                          "Describes the media to those who can't see it.",
                          "Media page: hint below the alt text field",
                        )}
                      >
                        <Input defaultValue={media.alt ?? ""} maxLength={255} autoComplete="off" />
                      </Field>
                      <Field
                        label={fbs(
                          "Visibility",
                          "Media page: label of the select that makes the media's files public or private",
                        )}
                        name="visibility"
                        description={fbs(
                          "Private files only open for signed-in admins.",
                          "Media page: hint below the visibility select",
                        )}
                      >
                        <Select<Visibility>
                          items={["private", "public"]}
                          itemToKey={(visibility) => visibility}
                          itemToLabel={visibilityLabel}
                          defaultValue={media.visibility}
                        />
                      </Field>
                      <UnsavedChangesToast />
                    </Form>
                  }
                  assets={(labelledBy) => (
                    <MediaAssets assets={media.assets} labelledBy={labelledBy} />
                  )}
                />
                <div className="p-4">
                  <Button variant="destructive" onClick={() => setDeleting(true)}>
                    <Icon icon={DeleteBinLine} />
                    <fbt desc="Media page: button in the sidebar that asks to delete the media">
                      Delete
                    </fbt>
                  </Button>
                </div>
              </aside>
            </div>
            <UnsavedChangesToastHost container={popup} />
          </Dialog.Popup>
        </Dialog.Portal>
      </Dialog.Root>
      {/* Beside the media's dialog, not inside it: Base UI leaves out a nested dialog's backdrop. */}
      <DeleteMediaDialog destroyUrl={destroyUrl} open={deleting} onOpenChange={setDeleting} />
    </>
  );
}

/** A video's poster, the frame the queue took; its size gives the player its shape. */
function posterOf(media: Props["media"]) {
  return media.assets.find((asset) => asset.role === "thumbnail");
}

/**
 * The image in the middle. A spinner shows only when it takes a while to load, and then long
 * enough not to blink; the image appears once it's whole, never line by line.
 */
function StageImage({ src, alt }: { src: string; alt: string }) {
  const [loaded, setLoaded] = useState(false);

  return (
    <DelayedFallback
      pending={!loaded}
      fallback={
        <span className="absolute inset-0 flex items-center justify-center text-base-secondary">
          <Spinner
            size={6}
            aria-label={fbs("Loading image", "Media page: spinner shown while the image loads")}
          />
        </span>
      }
    >
      <img
        // A cached image may have loaded before React listened for it.
        ref={(image) => {
          if (image?.complete && image.naturalWidth > 0) setLoaded(true);
        }}
        src={src}
        alt={alt}
        onLoad={() => setLoaded(true)}
        // A broken image shows as one, instead of a spinner forever.
        onError={() => setLoaded(true)}
        className="max-h-full max-w-full object-contain"
      />
    </DelayedFallback>
  );
}

/** In the middle instead of the media, until its provider is done with it, or when it failed. */
function NotReady({
  status,
  failure,
}: {
  status: Props["media"]["status"];
  failure: string | null;
}) {
  return (
    <div className="flex flex-1 items-center justify-center p-6">
      <EmptyState
        icon={status === "failed" ? ErrorWarningLine : TimeLine}
        title={
          status === "failed"
            ? fbs(
                "This file could not be processed",
                "Media page: shown instead of the media when its provider, e.g. Mux, failed to process it",
              )
            : fbs(
                "Still processing",
                "Media page: shown instead of the media while its provider, e.g. Mux, still works on it",
              )
        }
        description={
          status === "failed"
            ? (failure ?? undefined)
            : fbs("It shows up here once it's ready.", "Media page: below 'Still processing'")
        }
      />
    </div>
  );
}

type SidebarSection = "general" | "assets";

const SECTIONS_STORAGE_KEY = "larasell-admin.media.sidebar-sections";

/**
 * The sidebar's sections as an accordion, all open at first. Which ones are open is kept, so it
 * carries over to the next media. Panels stay mounted, so closing General keeps unsaved edits.
 */
function SidebarSections({
  general,
  assets,
}: {
  general: ReactNode;
  assets: (labelledBy: string) => ReactNode;
}) {
  const [open, setOpen] = useStorage<SidebarSection[]>(SECTIONS_STORAGE_KEY, ["general", "assets"]);
  const assetsHeading = useId();

  return (
    <Accordion.Root<SidebarSection>
      multiple
      keepMounted
      value={open}
      onValueChange={setOpen}
      className="flex flex-col"
    >
      <Section
        value="general"
        title={fbs(
          "General",
          "Media page: title of the sidebar section with the media's title, description, alt text and visibility",
        )}
      >
        {general}
      </Section>
      <Section
        value="assets"
        title={fbs("Assets", "Media page: title of the sidebar section listing the media's files")}
        triggerId={assetsHeading}
      >
        {assets(assetsHeading)}
      </Section>
    </Accordion.Root>
  );
}

function Section({
  value,
  title,
  triggerId,
  children,
}: {
  value: SidebarSection;
  title: string;
  triggerId?: string;
  children: ReactNode;
}) {
  return (
    <Accordion.Item value={value} className="border-b border-base-subtle">
      <Accordion.Header>
        <Accordion.Trigger
          id={triggerId}
          className="group flex w-full items-center justify-between gap-2 border-base-subtle px-4 data-panel-open:border-b py-3 type-body-md text-base-primary focus-visible:focus-ring focus-visible:-outline-offset-2"
        >
          {title}
          <span className="flex transition-transform group-data-panel-open:rotate-180">
            <Icon icon={ArrowDownSLine} />
          </span>
        </Accordion.Trigger>
      </Accordion.Header>
      <Accordion.Panel className="h-(--accordion-panel-height) overflow-hidden transition-[height] duration-200 ease-out data-ending-style:h-0 data-starting-style:h-0">
        <div className="p-4">{children}</div>
      </Accordion.Panel>
    </Accordion.Item>
  );
}
