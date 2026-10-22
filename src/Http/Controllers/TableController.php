<?php

namespace Larasell\Admin\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Tables\Table;
use Laravel\Head\Facades\Head;

class TableController
{
    /**
     * @param  class-string<Table>  $table
     */
    public function __invoke(string $table): Response
    {
        $resource = $table::make();

        Head::title($resource->title());

        return Inertia::render('TablePage', [
            'table' => $resource,
        ]);
    }
}
