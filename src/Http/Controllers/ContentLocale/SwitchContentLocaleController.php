<?php

namespace Larasell\Admin\Http\Controllers\ContentLocale;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Larasell\Admin\ContentLocale\AvailableLocales;
use Larasell\Admin\ContentLocale\ContentLocaleResolver;
use Larasell\Larasell\Enums\Locale;

class SwitchContentLocaleController
{
    /**
     * Edits content in the picked locale from now on, and reloads the page it was picked on.
     */
    public function __invoke(Request $request, AvailableLocales $locales, ContentLocaleResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_map(fn (Locale $locale): string => $locale->value, $locales->all()))],
        ]);

        $resolver->remember($request, Locale::from($validated['locale']));

        return back();
    }
}
