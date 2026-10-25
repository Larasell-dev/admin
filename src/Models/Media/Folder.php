<?php

namespace Larasell\Admin\Models\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Larasell\Larasell\Models\ProductImage;

/**
 * @property int $id
 * @property int|null $parent_id Null for a top-level folder.
 * @property string $name
 */
class Folder extends Model
{
    protected $table = 'larasell_admin_media_folders';

    protected $fillable = [
        'parent_id',
        'name',
    ];

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<ProductImage, $this>
     */
    public function productImages(): BelongsToMany
    {
        return $this->belongsToMany(ProductImage::class, 'larasell_admin_media_folder_product_image', 'folder_id', 'product_image_id')
            ->withTimestamps();
    }
}
