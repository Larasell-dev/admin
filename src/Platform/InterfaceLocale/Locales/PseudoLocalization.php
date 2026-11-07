<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Locales;

use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;

/**
 * Turns English text into a pseudo-language, like scripts/translations.mjs does for the frontend.
 *
 * @internal Used by SetInterfaceLocale.
 */
final class PseudoLocalization
{
    /** Placeholders, such as :name, stay as they are: the translator replaces them. */
    private const string PLACEHOLDER = '/(:[A-Za-z_]+)/';

    private const string LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const string ACCENTED_LETTERS = 'åƀçðéƒĝĥîĵķļɱñöþǫŕšţûṽŵẋýžÅƁÇÐÉƑĜĤÎĴĶĻṀÑÖÞǪŔŠŢÛṼŴẊÝŽ';

    public static function apply(InterfaceLocale $locale, string $text): string
    {
        return match ($locale->code) {
            'en-XA' => self::accented($text),
            'ar-XB' => self::rightToLeft($text),
            default => $text,
        };
    }

    /**
     * "Products" becomes "[Þŕöðûçţš ~~~]": accented, so text that skipped translation stands out,
     * about 40% longer, like many languages, and in brackets, so cut-off text shows.
     */
    private static function accented(string $text): string
    {
        $accents = array_combine(str_split(self::LETTERS), mb_str_split(self::ACCENTED_LETTERS));
        $words = self::mapText($text, fn (string $part): string => strtr($part, $accents));
        $length = mb_strlen((string) preg_replace(self::PLACEHOLDER, '', $text));

        return '['.$words.' '.str_repeat('~', max(1, (int) round($length * 0.4))).']';
    }

    /** Runs each piece of text right to left, with Unicode's override marks, around the placeholders. */
    private static function rightToLeft(string $text): string
    {
        return self::mapText($text, fn (string $part): string => $part === '' ? '' : "\u{202E}{$part}\u{202C}");
    }

    /**
     * @param  callable(string): string  $map
     */
    private static function mapText(string $text, callable $map): string
    {
        $parts = preg_split(self::PLACEHOLDER, $text, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [$text];

        return implode('', array_map(
            fn (string $part, int $index): string => $index % 2 === 1 ? $part : $map($part),
            $parts,
            array_keys($parts),
        ));
    }
}
