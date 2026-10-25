<?php

namespace Larasell\Admin\Models\Media;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $product_image_id
 * @property string $path On Larasell's images disk.
 */
class ProductImageThumbnail extends Model
{
    protected $table = 'larasell_admin_media_product_image_thumbnails';

    protected $primaryKey = 'product_image_id';

    public $incrementing = false;

    protected $fillable = [
        'product_image_id',
        'path',
    ];
}
