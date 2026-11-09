<?php

namespace Larasell\Admin\Media\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Media\Models\MediaFolder;

/**
 * One entry of a library folder: a subfolder or a media item, so the library loads both as one
 * list, a page at a time. Read only, from a union of both tables made by inFolder(); the panel's
 * own, the core knows folders and media apart.
 *
 * @property int|null $folder_id set for a subfolder
 * @property int|null $media_id set for a media item
 * @property-read MediaFolder|null $folder
 * @property-read Media|null $media
 */
class MediaLibraryEntry extends Model
{
    protected $table = 'larasell_admin_media_library_entries';

    public $timestamps = false;

    /**
     * The entries of a folder, or of the top level: its subfolders first, by name, then its media,
     * newest first.
     *
     * @return Builder<self>
     */
    public static function inFolder(?MediaFolder $folder): Builder
    {
        $folders = MediaFolder::query()->toBase()->where('parent_id', $folder?->id)
            ->selectRaw('0 as sort_group, id as folder_id, null as media_id, name_key as sort_name, id as sort_id');
        $media = Media::query()->toBase()->where('folder_id', $folder?->id)
            ->selectRaw("1 as sort_group, null as folder_id, id as media_id, '' as sort_name, -id as sort_id");

        return self::query()
            ->fromSub($folders->unionAll($media), (new self)->getTable())
            ->orderBy('sort_group')->orderBy('sort_name')->orderBy('sort_id');
    }

    /** @return BelongsTo<MediaFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /** @return BelongsTo<Media, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
