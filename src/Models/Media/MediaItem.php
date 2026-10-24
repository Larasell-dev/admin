<?php

namespace Larasell\Admin\Models\Media;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Enums\Media\MediaType;
use Larasell\Larasell\Models\ProductImage;

/**
 * A read-only row of the media library: a folder or a product image. Queries
 * select from the union of both tables, so one table can list them together.
 *
 * @property int $id
 * @property string $name The folder's name, or the product image's file path.
 * @property MediaType $type
 * @property int $type_order 0 for folders, 1 for product images, to list folders first.
 */
class MediaItem extends Model
{
    protected $table = 'media_items';

    protected $casts = [
        'type' => MediaType::class,
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
                ->select(['id', 'name'])
                ->selectRaw('? as type, 0 as type_order', [MediaType::Folder->value]);

            $productImages = ProductImage::query()->toBase()
                ->select(['id', 'file as name'])
                ->selectRaw('? as type, 1 as type_order', [MediaType::ProductImage->value]);

            $query->fromSub($folders->unionAll($productImages), $query->getModel()->getTable());
        });
    }
}
