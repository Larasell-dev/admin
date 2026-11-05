<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Data;

use Larasell\Admin\Platform\InterfaceLocale\AvailableInterfaceLocales;
use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The language the panel is shown in, and the others to switch to.
 */
#[TypeScript]
class InterfaceLocaleData extends Data
{
    public function __construct(
        public string $current,
        /** @var array<int, InterfaceLocaleOptionData> */
        public array $options,
        public string $switchUrl,
    ) {}

    public static function of(InterfaceLocale $current, AvailableInterfaceLocales $locales): self
    {
        return new self(
            current: $current->code,
            options: array_map(
                fn (InterfaceLocale $locale): InterfaceLocaleOptionData => new InterfaceLocaleOptionData($locale->code, $locale->name()),
                $locales->all(),
            ),
            switchUrl: route('larasell.admin.interface-locale.update'),
        );
    }
}
