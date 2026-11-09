<?php

namespace Larasell\Admin\Platform\Settings\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;

/**
 * A settings page was added in a way the settings index can't show.
 */
final class InvalidSettingsPage extends InvalidArgumentException implements StructuredException
{
    use HasStructuredException;

    public static function invalidId(string $id): self
    {
        return self::structured(
            errorCode: 'larasell-admin.settings.invalid-id',
            message: "The settings page id [{$id}] may only contain lowercase letters, numbers and dashes.",
            why: 'Other settings pages place themselves before or after it by its id.',
            fix: 'Pass an id such as "acme-wishlists", prefixed with your plugin\'s name.',
        );
    }

    public static function duplicateId(string $id): self
    {
        return self::structured(
            errorCode: 'larasell-admin.settings.duplicate-id',
            message: "The settings page [{$id}] is already registered.",
            why: 'before() and after() could not tell two pages with the same id apart.',
            fix: 'Add each settings page once, with an id prefixed with your plugin\'s name.',
        );
    }

    public static function incomplete(string $id, string $missing): self
    {
        return self::structured(
            errorCode: 'larasell-admin.settings.incomplete',
            message: "The settings page [{$id}] has no {$missing}.",
            why: 'Every card on the settings index shows a label and an icon, and links to a route.',
            fix: "Call {$missing}() on the page, e.g. Settings::add('{$id}')->label(...)->icon(...)->route(...).",
        );
    }
}
