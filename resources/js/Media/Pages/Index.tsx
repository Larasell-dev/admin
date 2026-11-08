import { thumbHashToDataURL } from "thumbhash";
import { Link, router } from "@inertiajs/react";
import { Button, EmptyState, Icon, Pagination, type PaginationChange } from "@larasell-dev/ui";
import { ImageLine, UploadLine, VideoLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import { useEffect, useId, useState } from "react";
import { uploadQueue } from "../Components/uploadQueue";
import { Page } from "../../Platform/Panel/Components/Page";
import SidebarLayout from "../../Platform/Panel/Layouts/SidebarLayout";

type Props = Larasell.Admin.Media.Data.MediaIndexPageData;

/** The media library: a grid of images and videos, a page at a time, with an upload button. */
export default function Index({ media, pagination, storeUrl }: Props) {
  const [paginating, setPaginating] = useState(false);
  return (
    <>
      <Page.Header
        title={fbs("Media", "Media library: page title")}
        actions={<UploadButton storeUrl={storeUrl} />}
      />
      {media.length === 0 ? (
        // Fills everything below the header, like an empty table page, so it sits in the middle.
        <div className="flex flex-1 items-center justify-center px-6 py-12">
          <EmptyState
            icon={ImageLine}
            title={fbs(
              "No media yet",
              "Media library: shown instead of the grid when nothing was uploaded",
            )}
            description={fbs(
              "Images and videos you upload show up here.",
              "Media library: below 'No media yet'",
            )}
          />
        </div>
      ) : (
        <Page.Body width="wide">
          <div className="flex flex-col gap-6">
            <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
              {media.map((item) => (
                <li key={item.id}>
                  <Link
                    href={item.showUrl}
                    aria-label={item.title ?? item.alt ?? item.fileName}
                    className="block aspect-square overflow-hidden bg-base-section bg-cover bg-center focus-visible:focus-ring"
                    style={
                      item.thumbHash && !item.thumbnailUrl
                        ? { backgroundImage: `url("${placeholderUrl(item.thumbHash)}")` }
                        : undefined
                    }
                  >
                    {item.thumbnailUrl ? (
                      // A WebP the queue made, of the image or a frame of the video.
                      <TileImage
                        src={item.thumbnailUrl}
                        alt={item.alt ?? ""}
                        thumbHash={item.thumbHash}
                      />
                    ) : (
                      // Never the original: a library of large photos and videos would load
                      // slowly. The queue makes the thumbnail shortly.
                      <span className="flex size-full items-center justify-center text-base-secondary">
                        <Icon icon={item.type === "video" ? VideoLine : ImageLine} />
                      </span>
                    )}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </Page.Body>
      )}
      {/* Across the whole page below the grid, always there, like a table's. */}
      <div
        data-testid="media-pagination"
        className="shrink-0 border-t border-base-subtle px-6 py-3"
      >
        <Pagination
          {...pagination}
          pageSizeOptions={[24, 48, 96]}
          disabled={paginating}
          onPaginationChange={({ page, pageSize }: PaginationChange) =>
            router.get(
              window.location.pathname,
              { page, per_page: pageSize },
              {
                preserveScroll: true,
                onStart: () => setPaginating(true),
                onFinish: () => setPaginating(false),
              },
            )
          }
        />
      </div>
    </>
  );
}

function UploadButton({ storeUrl }: { storeUrl: string }) {
  const id = useId();

  // Each file goes to the upload queue, which sends them a few at a time and shows the toast;
  // page 1 picks up each one as it lands.
  useEffect(() => {
    uploadQueue.onSucceeded = () => router.reload({ only: ["media"] });
    return () => {
      uploadQueue.onSucceeded = () => {};
    };
  }, []);

  return (
    <>
      <input
        id={id}
        type="file"
        multiple
        accept="image/jpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/webm,video/quicktime"
        aria-label={fbs(
          "Upload",
          "Media library: accessible name of the file picker that uploads media",
        )}
        className="sr-only"
        onChange={(event) => {
          const files = Array.from(event.currentTarget.files ?? []);
          event.currentTarget.value = "";
          if (files.length > 0) uploadQueue.add(storeUrl, files);
        }}
      />
      <Button render={<label htmlFor={id} />}>
        <Icon icon={UploadLine} />
        <fbt desc="Media library: button in the header that picks files to upload">Upload</fbt>
      </Button>
    </>
  );
}

Index.layout = [SidebarLayout];

/** A blurry miniature of the image, decoded from its ThumbHash, shown until the thumbnail loads. */
function placeholderUrl(thumbHash: string): string {
  return thumbHashToDataURL(Uint8Array.from(atob(thumbHash), (char) => char.charCodeAt(0)));
}

/**
 * A tile's thumbnail, hidden until it has loaded and then faded in over the ThumbHash behind it,
 * so the blurry preview turns into the image instead of being swapped for it. The ThumbHash is
 * dropped once the image has faded in, so its blur can't peek out around the image's antialiased edges.
 */
function TileImage({
  src,
  alt,
  thumbHash,
}: {
  src: string;
  alt: string;
  thumbHash?: string | null;
}) {
  const [loaded, setLoaded] = useState(false);
  const [shown, setShown] = useState(false);
  const reveal = (image: HTMLImageElement) => {
    setLoaded(true);
    // Without motion there is no fade, and no transitionend to wait for.
    if (getComputedStyle(image).transitionDuration === "0s") setShown(true);
  };

  return (
    <span
      className="block size-full bg-cover bg-center"
      style={
        thumbHash && !shown ? { backgroundImage: `url("${placeholderUrl(thumbHash)}")` } : undefined
      }
    >
      <img
        // A cached image may have loaded before React listened for it.
        ref={(image) => {
          if (image?.complete && image.naturalWidth > 0) reveal(image);
        }}
        src={src}
        alt={alt}
        loading="lazy"
        decoding="async"
        data-loaded={loaded}
        onLoad={(event) => reveal(event.currentTarget)}
        onTransitionEnd={() => setShown(true)}
        className="size-full object-cover opacity-0 transition-opacity duration-300 ease-out data-[loaded=true]:opacity-100 motion-reduce:transition-none"
      />
    </span>
  );
}
