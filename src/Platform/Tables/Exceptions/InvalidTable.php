<?php

namespace Larasell\Admin\Platform\Tables\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use LogicException;

final class InvalidTable extends LogicException implements StructuredException
{
    use HasStructuredException;

    public static function outsideNamespace(string $table): static
    {
        $namespace = (string) config('larasell.admin.panel.tables.namespace');

        return self::structured(
            errorCode: 'larasell-admin.table.outside-namespace',
            message: "{$table} must live in the {$namespace} namespace to run actions.",
            why: "An action's URL names its table by its place in the tables namespace, so the panel can find the table again when the action runs.",
            fix: "Move the table into the {$namespace} namespace, or change larasell.admin.panel.tables.namespace.",
        );
    }

    public static function unrememberedArgument(string $table, string $parameter): static
    {
        return self::structured(
            errorCode: 'larasell-admin.table.unremembered-argument',
            message: "{$table} must mark its constructor argument [\${$parameter}] as a promoted property with #[Remember] to run actions.",
            why: 'An action runs in a request of its own, so the table is built again from the arguments its URL remembers.',
            fix: "Make [\${$parameter}] a promoted constructor property and mark it with #[Remember].",
        );
    }

    public static function missingModel(string $table, ?string $guess): static
    {
        return self::structured(
            errorCode: 'larasell-admin.table.missing-model',
            message: "{$table} must define a model with the UseModel attribute or a \$model property".($guess !== null ? ", or there must be a [{$guess}] model." : '.'),
            why: 'The table queries its rows from a model, and none was given or found by its name.',
            fix: 'Add #[UseModel(YourModel::class)] to the table, or set its $model property.',
        );
    }
}
