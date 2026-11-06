<?php

namespace Larasell\Admin\Platform\Sections\Exceptions;

use BackedEnum;
use InvalidArgumentException;
use Larasell\Admin\Platform\Sections\Props;
use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;

/**
 * A section was added or asked for in a way its form can't use.
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

    public static function duplicateKey(string $form, string $key): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.duplicate-key',
            message: "The [{$form}] form already has a section [{$key}].",
            why: 'Two sections with the same key would post their fields under the same name, so each would overwrite the other.',
            fix: 'Give each section its own key, prefixed with your plugin\'s name.',
        );
    }

    /**
     * @param  class-string<BackedEnum>|null  $anchors
     */
    public static function unknownAnchor(string $form, string $key, BackedEnum $anchor, ?string $anchors): self
    {
        $given = $anchor::class.'::'.$anchor->name;

        return self::structured(
            errorCode: 'larasell-admin.section.unknown-anchor',
            message: "The [{$key}] section is placed next to [{$given}], which isn't one of the [{$form}] form's own sections.",
            why: $anchors === null
                ? "The [{$form}] form has no own sections registered to place sections next to."
                : "The [{$form}] form's own sections are the cases of [{$anchors}], so it has no place for the section.",
            fix: $anchors === null
                ? 'Leave out before() and after(), so the section goes after the form\'s own sections.'
                : "Pass a case of {$anchors} to before() or after().",
        );
    }

    public static function changedOtherInput(string $key, string $other): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.changed-other-input',
            message: "Preparing the [{$key}] section changed the input of the [{$other}] section.",
            why: "A section request only sees and changes its own input under sections[{$key}], so sections can't interfere with each other.",
            fix: 'Change only the section\'s own input, with merge() in prepareForValidation().',
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
            message: "No section of this form is validated by [{$request}].",
            why: 'The saved event only has the requests of the sections added to its form with Section::add().',
            fix: "Add the section to the form with ->request({$request}::class), or ask for the request class of a section the form has.",
        );
    }
}
