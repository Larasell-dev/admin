<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\InterfaceLocale\CookieInterfaceLocaleResolver;
use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;
use Larasell\Admin\Platform\InterfaceLocale\PanelInterfaceLocales;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;

function signInForInterfaceLocale(): void
{
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
}

it('remembers the picked language in a cookie and reloads the page it was picked on', function () {
    signInForInterfaceLocale();

    $this->from(route('larasell.admin.settings'))
        ->put(route('larasell.admin.interface-locale.update'), ['locale' => 'de-DE'])
        ->assertRedirect(route('larasell.admin.settings'))
        ->assertCookie(CookieInterfaceLocaleResolver::COOKIE, 'de-DE');

    // Inertia's own requests get a full reload, so the frontend loads its translations too.
    $this->from(route('larasell.admin.settings'))
        ->withHeaders(['X-Inertia' => 'true'])
        ->put(route('larasell.admin.interface-locale.update'), ['locale' => 'de-DE'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('larasell.admin.settings'));
});

it('shows the panel in the picked language, its server text and <html lang> included', function () {
    signInForInterfaceLocale();

    $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'de-DE')
        ->get(route('larasell.admin.home'))
        ->assertSee('<html lang="de-DE" dir="ltr">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('interfaceLocale.current', 'de-DE')
            ->where('interfaceLocale.options', [['value' => 'en-US', 'label' => 'English'], ['value' => 'de-DE', 'label' => 'Deutsch']])
            ->where('navigation.0.label', 'Startseite')
            ->where('commandMenu.searches.0.label', 'Produkte suchen')
            ->where('commandMenu.searches.0.placeholder', 'Nach Name, SKU oder Barcode suchen')
        );
});

it('translates the page title a route set while the app booted', function () {
    signInForInterfaceLocale();
    app('translator')->addLines(['*.Dashboard' => 'Übersicht', '*. - Admin' => ' - Verwaltung'], 'de-DE');

    $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'de-DE')
        ->get(route('larasell.admin.home'))
        ->assertSee('<title data-inertia="title">Übersicht - Verwaltung</title>', false);
});

it('names a table action in English, so a page loaded before switching the language still runs it', function () {
    signInForInterfaceLocale();
    app('translator')->addLines(['*.Delete' => 'Löschen'], 'de-DE');
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $action = $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'de-DE')
        ->get(route('larasell.admin.products.index'))
        ->inertiaProps('table.rows.0._actions.0');

    expect($action['label'])->toBe('Löschen')
        ->and($action['url'])->toEndWith("/{$boots->id}/actions/delete");

    $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'en-US')
        ->post($action['url'])
        ->assertSessionHasNoErrors();

    expect(Product::find($boots->id))->toBeNull();
});

it('shows the panel in English without a pick, or with one it does not offer', function (?string $picked) {
    signInForInterfaceLocale();

    $request = $picked === null ? $this : $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, $picked);

    $request->get(route('larasell.admin.home'))
        ->assertSee('<html lang="en-US" dir="ltr">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('interfaceLocale.current', 'en-US')
            ->where('navigation.0.label', 'Home')
        );
})->with([
    'no pick' => [null],
    'unknown' => ['xx-XX'],
    'pseudo outside the local environment' => ['en-XA'],
]);

it('reads the app\'s lang files of the picked language, e.g. lang/de for de-DE', function () {
    app('translator')->addLines(['auth.failed' => 'Diese Zugangsdaten sind falsch.'], 'de');

    $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'de-DE')
        ->post(route('larasell.admin.login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => 'Diese Zugangsdaten sind falsch.']);
});

it('rejects a language it does not offer', function (string $locale) {
    signInForInterfaceLocale();

    $this->put(route('larasell.admin.interface-locale.update'), ['locale' => $locale])
        ->assertSessionHasErrors('locale')
        ->assertCookieMissing(CookieInterfaceLocaleResolver::COOKIE);
})->with([
    'unknown' => ['xx-XX'],
    'only a language' => ['de'],
    // Tests run in the testing environment, so not locally.
    'pseudo outside the local environment' => ['ar-XB'],
]);

describe('pseudo-languages', function () {
    beforeEach(function () {
        $this->inEnvironment('local');
        signInForInterfaceLocale();
    });

    it('offers them locally, named in English', function () {
        $this->get(route('larasell.admin.home'))->assertInertia(fn (Assert $page) => $page
            ->where('interfaceLocale.options.2', ['value' => 'en-XA', 'label' => 'English (Pseudo-Accents)'])
            ->where('interfaceLocale.options.3', ['value' => 'ar-XB', 'label' => 'Arabic (Pseudo-Bidi)'])
        );
    });

    it('accents and lengthens the server text, placeholders and Laravel\'s own lines included', function () {
        app('translator')->addLines(['auth.throttle' => 'Try again in :seconds seconds.'], 'en');

        $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'en-XA')
            ->get(route('larasell.admin.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('navigation.0.label', '[Ĥöɱé ~~]')
                ->where('commandMenu.searches.0.label', '[Šéåŕçĥ þŕöðûçţš ~~~~~~]')
            );

        // "Try again in  seconds." without the placeholder has 22 characters, so 9 tildes.
        expect(__('auth.throttle', ['seconds' => 30]))->toBe('[Ţŕý åĝåîñ îñ 30 šéçöñðš. ~~~~~~~~~]');
    });

    it('runs the text right to left in ar-XB', function () {
        $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'ar-XB')
            ->get(route('larasell.admin.home'))
            ->assertSee('<html lang="ar-XB" dir="rtl">', false)
            ->assertInertia(fn (Assert $page) => $page->where('navigation.0.label', "\u{202E}Home\u{202C}"));
    });
});

it('has translation files for every language it ships, for the frontend and the server', function () {
    $translated = array_diff(PanelInterfaceLocales::TRANSLATED, [InterfaceLocale::SOURCE]);

    foreach ($translated as $code) {
        expect(__DIR__."/../../../../resources/translations/{$code}.json")->toBeFile()
            ->and(__DIR__."/../../../../lang/{$code}.json")->toBeFile();
    }
});
