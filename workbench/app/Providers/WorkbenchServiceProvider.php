<?php

namespace Workbench\App\Providers;

use DateTimeInterface;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\Writers\TypeDefinitionWriter;
use Workbench\App\TypeScript\ConcreteDataCollector;
use Workbench\App\TypeScript\TypeScriptEnumCollector;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // `composer types` runs typescript:transform against the package source;
        // Data classes and backed enums marked #[TypeScript] land in resources/js/types/generated.d.ts.
        config(['typescript-transformer' => [
            'auto_discover_types' => [dirname(__DIR__, 3).'/src'],
            'collectors' => [
                ConcreteDataCollector::class,
                TypeScriptEnumCollector::class,
            ],
            'transformers' => [
                DataTypeScriptTransformer::class,
                EnumTransformer::class,
            ],
            'default_type_replacements' => [
                DateTimeInterface::class => 'string',
            ],
            'output_file' => dirname(__DIR__, 3).'/resources/js/types/generated.d.ts',
            'writer' => TypeDefinitionWriter::class,
            'formatter' => null,
            'transform_to_native_enums' => false,
            'transform_null_to_optional' => false,
        ]]);
    }

    public function boot(): void
    {
        // Amp orb portals terminate TLS and forward over HTTP; trusting the proxy
        // makes generated asset URLs use https instead of being blocked as mixed content.
        TrustProxies::at('*');

        // APP_URL is localhost, which a portal visitor's browser can't reach;
        // a relative URL serves public uploads from whatever host the page uses.
        config(['filesystems.disks.public.url' => '/storage']);

        // The playground store serves every locale, so sales channels can pick from all of them.
        config(['larasell.locale.supported' => Locale::cases()]);

        // workbench/live-proxy.mjs serves Reverb on the panel's own origin, so browsers connect to
        // live updates wherever they opened the panel, inside the orb or through its portal.
        if (! $this->app->runningInConsole()) {
            $request = $this->app->make(Request::class);
            $request->setTrustedProxies([(string) $request->server('REMOTE_ADDR')], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
            config(['larasell.admin.panel.live' => ['host' => $request->getHost(), 'port' => $request->getPort(), 'scheme' => $request->getScheme()]]);
        }

        // Portals open at the site root; send it to the panel.
        Route::redirect('/', '/'.config('larasell.admin.panel.path'));
    }
}
