<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Translation\Translator;
use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;
use Larasell\Admin\Platform\InterfaceLocale\Locales\PseudoLocalization;
use Larasell\Admin\Platform\InterfaceLocale\Resolvers\InterfaceLocaleResolver;
use Laravel\Head\Facades\Head;
use Laravel\Head\Routing\RouteAttributeParser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the request in the admin's interface language: __() and Laravel's own messages, such as
 * validation errors, the page's title, and <html lang>, from which the frontend loads its
 * translations.
 */
class SetInterfaceLocale
{
    public function __construct(
        private readonly InterfaceLocaleResolver $resolver,
        private readonly Translator $translator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolver->resolve($request);

        App::setLocale($locale->code);

        $this->translator->determineLocalesUsing(function (array $locales): array {
            // Pseudo-languages have no files, so every line is missing and made up below.
            if ((new InterfaceLocale($locales[0]))->isPseudo()) {
                return [$locales[0]];
            }

            // An app's lang files are usually per language, e.g. lang/de/validation.php, so
            // "de-DE" reads them too before the fallback locale.
            return array_merge(...array_map(
                fn (string $code): array => [$code, (new InterfaceLocale($code))->language()],
                $locales,
            ));
        });

        if ($locale->isPseudo()) {
            $this->translator->handleMissingKeysUsing(function (string $key) use ($locale): string {
                $line = $this->translator->get($key, [], $this->translator->getFallback());

                return is_string($line) ? PseudoLocalization::apply($locale, $line) : $key;
            });
        }

        $this->translateRouteTitle($request);

        return $next($request);
    }

    /**
     * Routes get their title, e.g. `->withHead(title: Translate::later('Dashboard'))`, while the app
     * boots, before a request picked its language. Sets it again, translated, so a title a
     * controller sets with Head::title() still comes after it.
     */
    private function translateRouteTitle(Request $request): void
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return;
        }

        // The layers from the outermost group to the route itself, each overriding what it sets.
        $title = [];
        foreach (RouteAttributeParser::routeMetadata($route) as $layer) {
            $value = $layer['title'] ?? null;
            $layerTitle = is_string($value) ? ['value' => $value, 'exact' => false] : (is_array($value) ? $value : []);
            $title = [...$title, ...array_filter($layerTitle, fn (mixed $part): bool => $part !== null)];
        }

        if (! is_string($title['value'] ?? null)) {
            return;
        }

        $translate = fn (mixed $part): ?string => is_string($part) ? $this->translate($part) : null;

        Head::title(
            $this->translate($title['value']),
            prefix: $translate($title['prefix'] ?? null),
            suffix: $translate($title['suffix'] ?? null),
            exact: is_bool($title['exact'] ?? null) ? $title['exact'] : null,
        );
    }

    /** A line of a lang file's group is an array; a title is text, so it stays as it is then. */
    private function translate(string $text): string
    {
        $line = __($text);

        return is_string($line) ? $line : $text;
    }
}
