<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Larasell\Admin\Tables\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:larasell-table')]
class MakeTableCommand extends GeneratorCommand
{
    protected $name = 'make:larasell-table';

    protected $description = 'Create a new admin panel table class.';

    protected $type = 'Table';

    /** Whether the table names its model, because it differs from the one the table would infer. */
    private bool $namesModel = false;

    protected function getStub(): string
    {
        return __DIR__.($this->namesModel ? '/stubs/table.model.stub' : '/stubs/table.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Tables';
    }

    protected function buildClass($name): string
    {
        $model = $this->option('model') ?: Str::singular(Str::replaceEnd('Table', '', class_basename($name)));
        $model = class_exists($model) ? ltrim($model, '\\') : $this->qualifyModel($model);
        $this->namesModel = $model !== Table::guessModelName($name);

        return str_replace(
            ['{{ namespacedModel }}', '{{ model }}'],
            [$model, class_basename($model)],
            parent::buildClass($name),
        );
    }

    protected function getOptions(): array
    {
        return [
            ['model', 'm', InputOption::VALUE_REQUIRED, 'The model the table lists (defaults to the singular table name)'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the table already exists'],
        ];
    }
}
