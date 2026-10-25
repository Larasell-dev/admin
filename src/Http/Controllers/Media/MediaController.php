<?php

namespace Larasell\Admin\Http\Controllers\Media;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Tables\Panel\Media\Media;
use Laravel\Head\Facades\Head;

class MediaController
{
    public function index(): Response
    {
        $table = Media::make();

        Head::title($table->title());

        return Inertia::render('Media/Index', [
            'table' => $table,
        ]);
    }
}
