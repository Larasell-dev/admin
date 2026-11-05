# Admin requests use the interface language, not `app.locale`

The panel's own text is now translated. Each admin picks the language it is shown in, the interface
language, from the command menu or their profile, and it is kept in a cookie in their browser. For
every admin request, `SetInterfaceLocale` sets the app locale to that language, as a full code such
as `en-US` or `de-DE`. Before, admin requests kept the app's `app.locale`. Admins who pick no
language see `en-US`.

The interface language is separate from the content locale that products and categories are edited
in, which doesn't change.

## Who is affected

- Apps whose `app.locale` isn't English. Admin requests now run in `en-US` until an admin picks
  another language, so `App::currentLocale()`, `__()` and Laravel's validation messages in the panel
  follow the admin's pick instead of `app.locale`. Prices, country names and content-locale names
  on admin pages, which were formatted in `app.locale`, are now formatted in the interface language
  too.
- Apps that set the locale of admin requests themselves, e.g. with a middleware that calls
  `App::setLocale()`. Since `SetInterfaceLocale` runs in the admin route group, it decides first.
- Apps that set their own `Translator::determineLocalesUsing()` callback. Admin requests replace it,
  so that `de-DE` also reads the app's `lang/de/` files before the fallback locale.
- Code that read `app()->getLocale()` as `en` in admin requests, e.g. in tests. It is now `en-US`.

Apps with an English `app.locale` that don't switch the locale of admin requests are not affected.

## How to upgrade

Usually nothing is needed: admins pick their language themselves. The panel reads the app's
`lang/<language>/` files for the picked language, so Laravel's own messages, such as validation
errors, appear in German when the app has `lang/de/validation.php`.

To keep showing the panel in the app's locale, bind your own `InterfaceLocaleResolver`:

```php
use Illuminate\Http\Request;
use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;
use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocaleResolver;

class AppLocaleResolver implements InterfaceLocaleResolver
{
    public function resolve(Request $request): InterfaceLocale
    {
        return new InterfaceLocale(config('app.locale'));
    }

    // The app's locale decides, so the command menu and profile have nothing to remember.
    public function remember(Request $request, InterfaceLocale $locale): void {}
}

// AppServiceProvider::register()
$this->app->bind(InterfaceLocaleResolver::class, AppLocaleResolver::class);
```
