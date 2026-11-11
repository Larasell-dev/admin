<?php

namespace Larasell\Admin\Platform\Tables\Actions;

use JsonSerializable;
use Larasell\Admin\Platform\Tables\Exceptions\InvalidTableAction;

/**
 * Table actions shown as one button group: the first visible action is its button, and
 * the others are in a menu next to it, e.g. "Add product" with "Add digital product".
 * With one visible action it shows as a plain button, and without any it is left out.
 * Tables take it wherever they take a TableAction: in the header and the empty state.
 */
final class TableActionGroup implements JsonSerializable
{
    /**
     * @param  array<int, TableAction>  $actions
     */
    private function __construct(private array $actions) {}

    /**
     * @param  array<array-key, mixed>  $actions  At least one TableAction, the first shown as the button.
     */
    public static function make(array $actions): self
    {
        if (! array_is_list($actions) || $actions === []) {
            throw InvalidTableAction::invalidGroup();
        }

        foreach ($actions as $action) {
            if (! $action instanceof TableAction) {
                throw InvalidTableAction::invalidGroup();
            }
        }

        return new self($actions);
    }

    /**
     * @return array<int, TableAction>
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * The visible actions as they show: none, one plain action, or a group of them.
     *
     * @return array<int, TableAction|self>
     */
    public function visible(): array
    {
        $visible = array_values(array_filter($this->actions, fn (TableAction $action): bool => ! $action->isHidden()));

        return count($visible) > 1 ? [new self($visible)] : $visible;
    }

    /**
     * @return array{actions: array<int, TableAction>}
     */
    public function jsonSerialize(): array
    {
        return ['actions' => $this->actions];
    }
}
