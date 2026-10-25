<?php

namespace Larasell\Admin\Actions\Media;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Larasell\Models\ProductImage;

class DeleteFolder
{
    public function __construct(
        private DeleteProductImage $deleteProductImage,
    ) {}

    /**
     * Deletes the folder and every folder below it, with the product images in them.
     */
    public function handle(Folder $folder): void
    {
        // Eloquent can't query a tree recursively, so a recursive CTE collects the folder and
        // its descendants in one query. Each is deleted as a model so the Eloquent events fire,
        // deepest first: listeners still see the parent, and no folder is deleted while the
        // parent_id of another still points at it.
        $tree = DB::query()->fromRaw(
            '(with recursive tree (id, depth) as (
                select id, 0 from larasell_admin_media_folders where id = ?
                union all
                select child.id, tree.depth + 1 from larasell_admin_media_folders as child join tree on child.parent_id = tree.id
            ) select id, depth from tree) as tree',
            [$folder->getKey()],
        );

        $folders = Folder::query()
            ->joinSub($tree, 'tree', 'tree.id', '=', 'larasell_admin_media_folders.id')
            ->orderByDesc('tree.depth')
            ->select('larasell_admin_media_folders.*')
            ->get();

        // Images first, each with its file, so none is left behind if deleting the folders fails.
        ProductImage::query()
            ->whereIn('id', DB::table('larasell_admin_media_folder_product_image')->whereIn('folder_id', $folders->modelKeys())->select('product_image_id'))
            ->get()
            ->each(fn (ProductImage $image) => $this->deleteProductImage->handle($image));

        DB::transaction(fn () => $folders->each->delete());
    }
}
