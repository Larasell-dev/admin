<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LinkAdminAssetsCommand extends Command
{
    protected $signature = 'larasell:link';

    protected $description = 'Link the admin panel JavaScript and CSS into the public directory';

    public function handle(): int
    {
        $source = dirname(__DIR__, 2).'/public/vendor/larasell-admin';
        $target = public_path('vendor/larasell-admin');

        if (! is_dir($source)) {
            $this->components->error('Admin assets are not built. Run npm run build in the admin package.');

            return self::FAILURE;
        }

        if (is_link($target)) {
            File::delete($target);
        } elseif (is_dir($target)) {
            File::deleteDirectory($target);
        }

        File::ensureDirectoryExists(dirname($target));

        if (! symlink($source, $target)) {
            $this->components->error('Unable to link admin assets.');

            return self::FAILURE;
        }

        $this->components->info('Admin assets linked.');

        return self::SUCCESS;
    }
}
