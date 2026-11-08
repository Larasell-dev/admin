<?php

namespace Larasell\Admin\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Media\Data\MediaDetailData;
use Larasell\Admin\Media\Data\MediaIndexPageData;
use Larasell\Admin\Media\Data\MediaItemData;
use Larasell\Admin\Media\Data\MediaShowPageData;
use Larasell\Admin\Media\Data\PaginationData;
use Larasell\Admin\Media\Http\Requests\UpdateMediaRequest;
use Larasell\Admin\Media\Http\Requests\UploadMediaRequest;
use Larasell\Admin\Media\Models\MediaPlaceholder;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Media\Actions\DeleteMedia;
use Larasell\Larasell\Media\Actions\UpdateMedia;
use Larasell\Larasell\Media\Actions\UploadMedia;
use Larasell\Larasell\Media\Contracts\MediaStorage;
use Larasell\Larasell\Media\MediaId;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Media\Models\MediaAsset;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController
{
    /** A grid of four, six or eight columns stays full. */
    /** @var list<int> */
    private const PAGE_SIZES = [24, 48, 96];

    public function index(Request $request, #[CurrentContentLocale] Locale $locale): Response
    {
        // Multiples of the grid's 2, 4 and 6 columns, so full pages fill whole rows.
        $pageSize = in_array($request->integer('per_page'), self::PAGE_SIZES, true) ? $request->integer('per_page') : self::PAGE_SIZES[0];
        $query = Media::query()->with('original', 'thumbnail')->latest('id');
        $page = $query->paginate($pageSize)->withPath(route('larasell.admin.media.index'));

        // Past the last page, e.g. after deleting its only item, show the last page instead.
        if ($page->isEmpty() && $page->currentPage() > $page->lastPage()) {
            $page = $query->paginate($pageSize, page: $page->lastPage())->withPath(route('larasell.admin.media.index'));
        }

        $placeholders = MediaPlaceholder::query()->whereIn('media_id', $page->getCollection()->modelKeys())->pluck('thumbhash', 'media_id');

        return Inertia::render('Media/Pages/Index', new MediaIndexPageData(
            media: $page->getCollection()->map(fn (Media $media): MediaItemData => MediaItemData::fromModel($media, $locale, $placeholders[$media->id] ?? null))->all(),
            pagination: new PaginationData($page->currentPage(), $page->perPage(), $page->total()),
            storeUrl: route('larasell.admin.media.store'),
        ));
    }

    public function store(UploadMediaRequest $request, UploadMedia $upload): RedirectResponse|JsonResponse
    {
        // The library's upload queue sends one file per request, each with its own key, and
        // retries with the same key: a retry after a lost answer gets the media it already made.
        if ($request->expectsJson()) {
            $key = (string) $request->header('Idempotency-Key');
            abort_unless(Str::isUuid($key), 400, 'An upload needs an Idempotency-Key that is a UUID.');
            // Per admin, so one admin's key never reveals another's upload.
            $cacheKey = "larasell-admin:media-upload:{$request->user('larasell-admin')?->getAuthIdentifier()}:{$key}";

            // Locked, so a retry sent while the first request still runs waits for it.
            $id = $this->storeOnce($cacheKey, $request, $upload);

            return response()->json(['id' => $id], 201);
        }

        foreach ($request->payloads() as $payload) {
            $upload->handle($payload);
        }

        return redirect()->route('larasell.admin.media.index');
    }

    private function storeOnce(string $cacheKey, UploadMediaRequest $request, UploadMedia $upload): int
    {
        return Cache::lock("{$cacheKey}:lock", 60)->block(30, function () use ($cacheKey, $request, $upload): int {
            $id = Cache::get($cacheKey);

            if (is_int($id)) {
                return $id;
            }

            $id = $upload->handle($request->payloads()[0])->id;
            Cache::put($cacheKey, $id, now()->addDay());

            return $id;
        });
    }

    public function show(Media $media, #[CurrentContentLocale] Locale $locale): Response
    {
        $media->load('original', 'assets');
        Head::title($media->original->file_name);

        return Inertia::render('Media/Pages/Show', new MediaShowPageData(
            media: MediaDetailData::fromModel($media, $locale),
            updateUrl: route('larasell.admin.media.update', $media),
            destroyUrl: route('larasell.admin.media.destroy', $media),
            indexUrl: route('larasell.admin.media.index'),
        ));
    }

    public function update(UpdateMediaRequest $request, Media $media, #[CurrentContentLocale] Locale $locale, UpdateMedia $update): RedirectResponse
    {
        $update->handle(MediaId::of($media), $request->payload($media, $locale));

        return redirect()->route('larasell.admin.media.show', $media);
    }

    /** What the admin types to confirm a delete. */
    public const DELETE_CONFIRMATION = 'Delete media';

    /** Streams one of the media's files; never cached, as private files must not outlive the session. */
    public function file(Request $request, Media $media, MediaStorage $storage, string $role = MediaAsset::ORIGINAL): StreamedResponse|HttpResponse
    {
        $asset = $media->assets()->where('role', $role)->first() ?? abort(404);
        $headers = [
            'Content-Type' => $asset->mime_type,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];
        $size = $asset->size;
        [$start, $end] = [0, $size - 1];
        $status = 200;

        // Browsers fetch videos in parts: Safari plays nothing without it, and seeking needs it.
        if (preg_match('/^bytes=(\d*)-(\d*)$/', (string) $request->header('Range'), $range) === 1 && $range[1].$range[2] !== '') {
            [$start, $end] = $range[1] === ''
                ? [max(0, $size - (int) $range[2]), $size - 1]
                : [(int) $range[1], $range[2] === '' ? $size - 1 : min((int) $range[2], $size - 1)];

            if ($start > $end || $start >= $size) {
                return response('', 416, ['Content-Range' => "bytes */{$size}"] + $headers);
            }

            $status = 206;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $stream = $storage->readStream($asset->file()) ?? abort(404);
        $headers['Content-Length'] = (string) ($end - $start + 1);

        return response()->stream(function () use ($stream, $start, $end): void {
            // Disks such as S3 hand over streams that can't seek; reading forward skips the same bytes.
            if ($start > 0 && fseek($stream, $start) !== 0) {
                for ($skip = $start; $skip > 0 && ! feof($stream); $skip -= strlen((string) fread($stream, min(8192, $skip))));
            }

            for ($left = $end - $start + 1; $left > 0 && ! feof($stream); $left -= strlen($chunk)) {
                echo $chunk = (string) fread($stream, min(8192, $left));
            }

            fclose($stream);
        }, $status, $headers);
    }

    public function destroy(Request $request, Media $media, DeleteMedia $delete): RedirectResponse
    {
        if ($request->input('confirmation') !== self::DELETE_CONFIRMATION) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type ":text" to confirm.', ['text' => self::DELETE_CONFIRMATION]),
            ]);
        }

        $delete->handle(MediaId::of($media));

        return redirect()->route('larasell.admin.media.index');
    }
}
