<?php

namespace Larasell\Admin\Http\Controllers\Media;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Tables\Panel\Media\Media;
use Laravel\Head\Facades\Head;

class FolderController
{
    public function show(Folder $folder): Response
    {
        $table = Media::make($folder);

        Head::title($table->title());

        return Inertia::render('TablePage', [
            'table' => $table,
        ]);
    }
}
