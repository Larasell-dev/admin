<?php

namespace Larasell\Admin\Platform\Tables\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Admin\Platform\Tables\Table;

class TableActionController
{
    public function __invoke(Request $request, string $table, string $record, string $action): RedirectResponse
    {
        $class = Table::fromSlug($table);
        abort_if($class === null, 404);

        $state = $request->query('state');
        $table = $class::fromState(is_string($state) ? $state : null);
        abort_if($table === null, 404);

        $action = $table->action($action);
        abort_if($action === null || ! $action->runs(), 404);

        $model = $table->findRecord($record);
        abort_if($model === null, 404);
        // A hidden action answers 404 like an unknown one, so the response doesn't reveal it exists
        // for this record. Checked before disabled, whose 403 would.
        abort_if($action->isHiddenFor($model), 404);
        abort_if($action->isDisabledFor($model), 403);

        $expected = $action->confirmationTextFor($model);

        if ($expected !== null) {
            $request->validate(['confirmation' => [new Confirmation($expected)]]);
        }

        $action->run($model);

        return back();
    }
}
