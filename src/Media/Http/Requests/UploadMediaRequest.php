<?php

namespace Larasell\Admin\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Larasell\Larasell\Media\Actions\UploadMediaPayload;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\MediaType;

/** Uploads images and videos into the media library, several at once. */
class UploadMediaRequest extends FormRequest
{
    /** How many files one upload takes, so a dropped folder can't hold the request open for long. */
    public const MAX_FILES = 20;

    /** In kilobytes: 100 MB, room for a short product video. */
    public const MAX_SIZE = 102_400;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.($this->expectsJson() ? 1 : self::MAX_FILES)],
            // Checked against the file's content, as the core's UploadMedia does.
            // In the URL, so each queued file keeps the folder it was picked in.
            'folder' => ['nullable', 'integer', 'min:1'],
            'files.*' => ['required', 'file', 'mimetypes:'.implode(',', MediaType::mimeTypes()), 'max:'.self::MAX_SIZE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // PHP drops files above its upload_max_filesize before Laravel sees them.
            'files.*.uploaded' => __('The file is too large to upload.'),
            'files.*.max' => __('The file is too large to upload.'),
            'files.*.mimetypes' => __('Upload images (JPEG, PNG, GIF, WebP or AVIF) or videos (MP4, WebM or MOV).'),
        ];
    }

    /** @return list<UploadMediaPayload> */
    public function payloads(): array
    {
        /** @var list<UploadedFile> $files */
        $files = $this->validated('files');

        $folderId = $this->filled('folder') ? new MediaFolderId($this->integer('folder')) : null;

        return array_map(fn (UploadedFile $file): UploadMediaPayload => new UploadMediaPayload($file, folderId: $folderId), $files);
    }
}
