<?php

namespace Larasell\Admin\Media\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What the media library shows while an image loads: a ThumbHash, a blurry miniature of it in a
 * few bytes. The panel's own, made by GeneratePlaceholder.
 *
 * @property int $id
 * @property int $media_id
 * @property string $thumbhash base64
 */
class MediaPlaceholder extends Model
{
    protected $table = 'larasell_admin_media_placeholders';

    protected $guarded = [];
}
