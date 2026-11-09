import { Icon } from "@larasell-dev/ui";
import { FileVideoLine, ImageLine } from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";

type Asset = Larasell.Admin.Media.Data.MediaAssetData;

/** Bytes as KB or MB, 1024 to the next. */
function fileSize(bytes: number): string {
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function roleLabel(asset: Asset): string {
  if (asset.role === "original") {
    return fbs("Original", "Media page: name of the media's asset that is the file as uploaded");
  }
  if (asset.role === "thumbnail") {
    return fbs(
      "Thumbnail",
      "Media page: name of the media's asset that is the small copy shown in the library's grid",
    );
  }
  return asset.role;
}

export function visibilityLabel(visibility: Asset["visibility"]): string {
  return visibility === "public"
    ? fbs(
        "Public",
        "Media page: visibility of a file anyone with its link can open, e.g. product images",
      )
    : fbs(
        "Private",
        "Media page: visibility of a file only signed-in admins and code that checked access can open",
      );
}

function dimensions(asset: Asset): string | null {
  return asset.width !== null && asset.height !== null ? `${asset.width} × ${asset.height}` : null;
}

/**
 * The media's files, e.g. its original and thumbnail, stacked: a small preview on the left, the
 * file's details on the right. Each previews with the thumbnail, which is the same picture, so the
 * sidebar never downloads an original; the link opens the file itself.
 */
export default function MediaAssets({
  assets,
  labelledBy,
}: {
  assets: Asset[];
  labelledBy: string;
}) {
  const preview = assets.find((asset) => asset.role === "thumbnail")?.url ?? null;

  return <Tiles assets={assets} labelledBy={labelledBy} preview={preview} />;
}

function Tiles({
  assets,
  labelledBy,
  preview,
}: {
  assets: Asset[];
  labelledBy: string;
  preview: string | null;
}) {
  return (
    <ul aria-labelledby={labelledBy} className="flex flex-col gap-2">
      {assets.map((asset) => (
        <li key={asset.role}>
          <a
            href={asset.url ?? undefined}
            target="_blank"
            rel="noreferrer"
            className="group flex items-start gap-3 border border-base-subtle type-body-sm focus-visible:focus-ring"
          >
            <span className="flex size-16 shrink-0 items-center justify-center overflow-hidden bg-base-section">
              {asset.mimeType.startsWith("image/") && preview ? (
                <img src={preview} alt="" loading="lazy" className="size-full object-cover" />
              ) : (
                <Icon icon={asset.mimeType.startsWith("image/") ? ImageLine : FileVideoLine} />
              )}
            </span>
            <span className="flex min-w-0 flex-col py-2 pr-2">
              <span className="text-base-primary group-hover:underline">{roleLabel(asset)}</span>
              <span className="text-base-secondary">{asset.mimeType}</span>
              {dimensions(asset) && (
                <span className="text-base-secondary">{dimensions(asset)}</span>
              )}
              <span className="text-base-secondary">
                {fileSize(asset.size)} · {visibilityLabel(asset.visibility)}
              </span>
            </span>
          </a>
        </li>
      ))}
    </ul>
  );
}
