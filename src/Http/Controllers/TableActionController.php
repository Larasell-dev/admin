<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Tables\Table;

class TableActionController
{
    public function __invoke(Request $request, string $table, string $record, string $action): RedirectResponse
    {
        $class = Table::fromSlug($table);
        abort_if($class === null, 404);

        $table = $class::make();
        $action = $table->action($action);
        abort_if($action === null || ! $action->runs(), 404);

        $model = $table->findRecord($record);
        abort_if($model === null, 404);

        $expected = $action->confirmationTextFor($model);

        if ($expected !== null && $request->input('confirmation') !== $expected) {
            throw ValidationException::withMessages([
                'confirmation' => "Type \"{$expected}\" to confirm.",
            ]);
        }

        $action->run($model);

        return back();
    }
}
