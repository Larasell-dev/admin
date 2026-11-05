<?php

namespace Larasell\Admin\Platform\InterfaceLocale;

/**
 * A language the panel's own text, its buttons, menus and messages, can be shown in, as a full
 * BCP 47 code such as "de-DE", the same in the frontend's and the server's translation files.
 * Not a content locale, which products and categories are edited in.
 */
final readonly class InterfaceLocale
{
    /** The language the panel is written in, shown when no other is picked. */
    public const string SOURCE = 'en-US';

    /**
     * Made-up languages for finding untranslated text and layouts that break once translated:
     * accented and longer text, and right-to-left text.
     */
    public const array PSEUDO = ['en-XA', 'ar-XB'];

    private const array RIGHT_TO_LEFT_LANGUAGES = ['ar', 'dv', 'fa', 'he', 'ps', 'ur', 'yi'];

    public function __construct(public string $code) {}

    public function isPseudo(): bool
    {
        return in_array($this->code, self::PSEUDO, true);
    }

    /** The language alone, e.g. "de" for "de-DE". */
    public function language(): string
    {
        return \Locale::getPrimaryLanguage($this->code) ?? $this->code;
    }

    /** @return 'ltr'|'rtl' */
    public function direction(): string
    {
        return in_array($this->language(), self::RIGHT_TO_LEFT_LANGUAGES, true) ? 'rtl' : 'ltr';
    }

    /**
     * Its name in itself, e.g. "Deutsch", so admins find their own language whichever one the
     * panel is in. Pseudo-languages are named in English.
     */
    public function name(): string
    {
        if ($this->isPseudo()) {
            return \Locale::getDisplayName($this->code, 'en') ?: $this->code;
        }

        return mb_ucfirst(\Locale::getDisplayLanguage($this->code, $this->code) ?: $this->code);
    }
}
