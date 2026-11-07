<?php

namespace Larasell\Admin\Platform\Tables\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\Tables\Table;
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

        return Inertia::render('Platform/Tables/Pages/TablePage', [
            'table' => $resource,
        ]);
    }
}
