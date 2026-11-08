import { Dialog } from "@base-ui/react/dialog";
import { Form, router } from "@inertiajs/react";
import {
  AlertDialog,
  Button,
  ButtonGroup,
  Field,
  Icon,
  IconButton,
  Input,
  Select,
} from "@larasell-dev/ui";
import {
  AspectRatioLine,
  CloseLine,
  DeleteBinLine,
  PaintFill,
  PaintLine,
  ZoomInLine,
  ZoomOutLine,
} from "@larasell-dev/ui/icons";
import { TransformComponent, TransformWrapper } from "react-zoom-pan-pinch";
import { cn } from "cn";
import { fbs } from "fbtee";
import { useState } from "react";
import type { SharedProps } from "../../types";
import VideoPlayer from "../Components/VideoPlayer";
import MediaAssets, { visibilityLabel } from "../Components/MediaAssets";
import ContentLocaleSelect from "../../Platform/ContentLocale/Components/ContentLocaleSelect";
import UnsavedChangesToast, {
  UnsavedChangesToastHost,
} from "../../Platform/Panel/Components/UnsavedChangesToast";

type Props = Larasell.Admin.Media.Data.MediaShowPageData & Pick<SharedProps, "contentLocale">;
type Visibility = Larasell.Admin.Media.Data.MediaAssetData["visibility"];

/** What the admin types to confirm a delete; the server checks the same text. */
const DELETE_CONFIRMATION = "Delete media";

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
                      {media.type === "video" ? (
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
                          <img
                            src={media.url}
                            alt={media.alt ?? ""}
                            className="max-h-full max-w-full object-contain"
                          />
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
              <aside className="flex min-h-0 flex-col gap-6 overflow-y-auto border-t border-base-subtle p-4 md:w-80 md:border-t-0 md:border-l">
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
                    <Input defaultValue={media.title ?? ""} maxLength={255} autoComplete="off" />
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
                <MediaAssets assets={media.assets} />
                <div>
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
      <DeleteMedia destroyUrl={destroyUrl} open={deleting} onOpenChange={setDeleting} />
    </>
  );
}

function DeleteMedia({
  destroyUrl,
  open,
  onOpenChange,
}: {
  destroyUrl: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const [typed, setTyped] = useState("");
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string>();

  return (
    <AlertDialog.Root
      open={open}
      onOpenChange={(next) => {
        onOpenChange(next);
        setTyped("");
        setError(undefined);
      }}
    >
      <AlertDialog.Popup
        title={fbs(
          "Delete media",
          "Media page: title of the dialog that asks to confirm deleting the media",
        )}
        description={fbs(
          "Its files are removed from the store. This can't be undone.",
          "Media page: explains what deleting the media does",
        )}
      >
        <Field
          label={fbs(
            ['Type "', fbs.param("confirmationText", DELETE_CONFIRMATION), '" to confirm'],
            "Media page: label of the field in the delete dialog that the admin types the given text into to confirm, e.g. 'Type \"delete media\" to confirm'",
          )}
          name="confirmation"
          error={error}
        >
          <Input
            autoComplete="off"
            value={typed}
            onChange={(event) => setTyped(event.currentTarget.value)}
          />
        </Field>
        <AlertDialog.Actions>
          <AlertDialog.Cancel>
            <fbt desc="Media page: button that closes the delete dialog without deleting">
              Cancel
            </fbt>
          </AlertDialog.Cancel>
          <AlertDialog.Confirm
            variant="destructive"
            loading={processing}
            onClick={() =>
              router.delete(destroyUrl, {
                data: { confirmation: typed },
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(errors.confirmation),
              })
            }
          >
            <fbt desc="Media page: button in the dialog that deletes the media">Delete</fbt>
          </AlertDialog.Confirm>
        </AlertDialog.Actions>
      </AlertDialog.Popup>
    </AlertDialog.Root>
  );
}

/** A video's poster, the frame the queue took; its size gives the player its shape. */
function posterOf(media: Props["media"]) {
  return media.assets.find((asset) => asset.role === "thumbnail");
}
