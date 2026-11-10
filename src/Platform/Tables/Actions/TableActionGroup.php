<?php

namespace Larasell\Admin\Platform\Tables\Actions;

use JsonSerializable;
use Larasell\Admin\Platform\Tables\Exceptions\InvalidTableAction;

/**
 * Table actions shown as one button group: the first action is its button, and
 * the others are in a menu next to it, e.g. "Add product" with "Add digital product".
 * Tables take it wherever they take a TableAction: in the header and the empty state.
 */
final class TableActionGroup implements JsonSerializable
{
    /**
     * @param  array<int, TableAction>  $actions
     */
    private function __construct(private array $actions) {}

    /**
     * @param  array<array-key, mixed>  $actions  At least two TableAction, the first shown as the button.
     */
    public static function make(array $actions): self
    {
        if (! array_is_list($actions) || count($actions) < 2) {
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
     * @return array{actions: array<int, TableAction>}
     */
    public function jsonSerialize(): array
    {
        return ['actions' => $this->actions];
    }
}
