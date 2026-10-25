<?php

namespace Larasell\Admin\Models\Media;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Larasell\Admin\Enums\Media\MediaType;
use Larasell\Larasell\Casts\PlaceholderCast;
use Larasell\Larasell\Images\Placeholder;
use Larasell\Larasell\Models\ProductImage;

/**
 * A read-only row of the media library: a folder or a product image. Queries
 * select from the union of both tables, so one table can list them together.
 *
 * @property int $id
 * @property string $name The folder's name, or the product image's file path.
 * @property MediaType $type
 * @property int $type_order 0 for folders, 1 for product images, to list folders first.
 * @property int|null $folder_id The folder it is in, or null at the top level.
 * @property Carbon|null $added_at When the folder was created or the product image uploaded.
 * @property string|null $thumbnail The product image's thumbnail on Larasell's images disk, once its job ran.
 * @property Placeholder|null $placeholder Larasell's low-quality placeholder of the product image.
 */
class MediaItem extends Model
{
    protected $table = 'media_items';

    protected $casts = [
        'type' => MediaType::class,
        'added_at' => 'datetime',
        'placeholder' => PlaceholderCast::class,
    ];

    /**
     * Folders and product images share ids, so the key names the type too, e.g. "folder-1".
     */
    public function getKey(): string
    {
        return "{$this->type->value}-{$this->getAttribute('id')}";
    }

    protected static function booted(): void
    {
        // A scope, so it applies after the builder sets the table as the query's source.
        static::addGlobalScope('folders and product images', function (Builder $query): void {
            $folders = Folder::query()->toBase()
                ->select(['id', 'name', 'parent_id as folder_id', 'created_at as added_at'])
                ->selectRaw('? as type, 0 as type_order, null as thumbnail, null as placeholder', [MediaType::Folder->value]);

            // An image without a row in the link table is at the top level.
            $productImages = ProductImage::query()->toBase()
                ->leftJoin('larasell_admin_media_folder_product_image as link', 'link.product_image_id', '=', 'larasell_product_images.id')
                ->leftJoin('larasell_admin_media_product_image_thumbnails as thumbnail', 'thumbnail.product_image_id', '=', 'larasell_product_images.id')
                ->select(['larasell_product_images.id', 'larasell_product_images.file as name', 'link.folder_id', 'larasell_product_images.created_at as added_at'])
                ->selectRaw('? as type, 1 as type_order', [MediaType::ProductImage->value])
                ->addSelect(['thumbnail.path as thumbnail', 'larasell_product_images.placeholder']);

            $query->fromSub($folders->unionAll($productImages), $query->getModel()->getTable());
        });
    }
}
