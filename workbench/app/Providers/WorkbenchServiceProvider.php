<?php

namespace Workbench\App\Providers;

use DateTimeInterface;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Writers\TypeDefinitionWriter;
use Workbench\App\TypeScript\ConcreteDataCollector;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // `composer types` runs typescript:transform against the package source;
        // classes marked #[TypeScript] land in resources/js/types/generated.d.ts.
        config(['typescript-transformer' => [
            'auto_discover_types' => [dirname(__DIR__, 3).'/src'],
            'collectors' => [
                ConcreteDataCollector::class,
            ],
            'transformers' => [
                DataTypeScriptTransformer::class,
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

        // Product images default to the private local disk, which the browser can't load.
        config(['larasell.images.disk' => 'public']);

        // Portals open at the site root; send it to the panel.
        Route::redirect('/', '/'.config('larasell.admin.panel.path'));
    }
}
