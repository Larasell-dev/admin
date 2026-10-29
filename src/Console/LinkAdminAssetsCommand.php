<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Larasell\Admin\AdminPanel;

class LinkAdminAssetsCommand extends Command
{
    protected $signature = 'larasell:link';

    protected $description = 'Link the admin panel JavaScript and CSS, and those of packages that extend it, into the public directory';

    public function handle(AdminPanel $panel): int
    {
        if (! $this->link('larasell-admin', dirname(__DIR__, 2).'/public', 'Admin assets are not built. Run npm run build in the admin package.')) {
            return self::FAILURE;
        }

        $this->components->info('Admin assets linked.');

        foreach ($panel->packageScripts() as $name => $script) {
            if (! $this->link($name, $script['publicPath'], "Assets of [{$name}] are not built. Run npm run build in its package.")) {
                return self::FAILURE;
            }

            $this->components->info("Assets of [{$name}] linked.");
        }

        return self::SUCCESS;
    }

    /**
     * Links $publicPath/vendor/$name, a package's build, to the app's public/vendor/$name.
     */
    private function link(string $name, string $publicPath, string $notBuilt): bool
    {
        $source = "{$publicPath}/vendor/{$name}";
        $target = public_path("vendor/{$name}");

        if (! is_dir($source)) {
            $this->components->error($notBuilt);

            return false;
        }

        if (is_link($target)) {
            File::delete($target);
        } elseif (is_dir($target)) {
            File::deleteDirectory($target);
        }

        File::ensureDirectoryExists(dirname($target));

        if (! symlink($source, $target)) {
            $this->components->error("Unable to link the assets of [{$name}].");

            return false;
        }

        return true;
    }
}
