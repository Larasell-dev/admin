<?php

namespace Larasell\Admin\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Tables\Table;

class TableController
{
    /**
     * @param  class-string<Table>  $table
     */
    public function __invoke(string $table): Response
    {
        return Inertia::render('TablePage', [
            'table' => $table::make(),
        ]);
    }
}
