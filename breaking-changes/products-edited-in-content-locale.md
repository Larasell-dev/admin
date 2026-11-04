# Products are edited in a content locale, not in the interface locale

The product pages used to read and save a product's name and slug in the app's current locale
(`App::currentLocale()`), the language of the panel's interface. They now use a separate content
locale: admins pick it with a select in the top right of the product pages, and it starts at
`larasell.locale.default`. This lets admins translate products without changing the panel's
language.

## Who is affected

- Apps whose `app.locale` differs from `larasell.locale.default`. Their admins used to see and
  edit products in `app.locale` and now see the default locale first.
- Apps that edit translations by switching the app locale for admin requests, e.g. with a
  middleware that calls `App::setLocale()`. That no longer changes which translation the product
  pages edit.

Apps where `app.locale` and `larasell.locale.default` are the same, and that don't switch the locale
for admin requests, are not affected.

## How to upgrade

Usually nothing is needed: admins pick the language with the select, which only shows while
`larasell.locale.supported` has more than one locale. To start in another locale, set it as the
default:

```php
// config/larasell/locale.php
'default' => Locale::GERMAN,
```

To keep editing in the app's current locale, bind your own `ContentLocaleResolver`:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Larasell\Admin\ContentLocale\ContentLocaleResolver;
use Larasell\Larasell\Enums\Locale;

class CurrentLocaleResolver implements ContentLocaleResolver
{
    public function resolve(Request $request): Locale
    {
        return Locale::from(Locale::canonicalize(App::currentLocale()));
    }

    // The app's own locale switching decides, so the select has nothing to remember.
    public function remember(Request $request, Locale $locale): void {}
}

// AppServiceProvider::register()
$this->app->bind(ContentLocaleResolver::class, CurrentLocaleResolver::class);
```
