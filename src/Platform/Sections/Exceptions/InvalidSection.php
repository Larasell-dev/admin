<?php

namespace Larasell\Admin\Platform\Sections\Exceptions;

use InvalidArgumentException;
use Larasell\Admin\Platform\Sections\Props;
use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;

/**
 * A section was added or asked for in a way the page can't use.
 */
final class InvalidSection extends InvalidArgumentException implements StructuredException
{
    use HasStructuredException;

    public static function invalidKey(string $key): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.invalid-key',
            message: "The section key [{$key}] may only contain letters, numbers, dashes and underscores.",
            why: "The key becomes part of the section's field names and error keys, where dots and brackets would nest them under another field.",
            fix: 'Pass a key such as "acme-wishlists-notes", prefixed with your plugin\'s name.',
        );
    }

    public static function duplicateKey(string $page, string $key): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.duplicate-key',
            message: "The [{$page}] page already has a section [{$key}].",
            why: 'Two sections with the same key would post their fields under the same name, so each would overwrite the other.',
            fix: 'Give each section its own key, prefixed with your plugin\'s name.',
        );
    }

    public static function invalidProps(string $key, string $props): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.invalid-props',
            message: "The props of the [{$key}] section must implement [".Props::class."], [{$props}] doesn't.",
            why: 'The page asks a props class for its section\'s props by calling Props::resolve().',
            fix: 'Implement '.Props::class.' on the class, or pass a closure to props().',
        );
    }

    public static function invalidRequest(string $key, string $request): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.invalid-request',
            message: "The request of the [{$key}] section must extend [".SectionRequest::class."], [{$request}] doesn't.",
            why: 'The page validates a section with a SectionRequest, scoped to the section\'s own fields.',
            fix: 'Extend '.SectionRequest::class.'. A FormRequest can\'t be used, since it would see the whole page\'s input.',
        );
    }

    public static function unknownRequest(string $request): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.unknown-request',
            message: "No section of this page is validated by [{$request}].",
            why: 'The saved event only has the requests of the sections added to its page with Section::add().',
            fix: "Add the section to this page with ->request({$request}::class), or ask for the request class of a section the page has.",
        );
    }
}
