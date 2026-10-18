<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Larasell\Admin\Models\Admin;

class ListAdminsCommand extends Command
{
    protected $signature = 'larasell:list-admins';

    protected $description = 'List all admins.';

    public function handle(): int
    {
        $admins = Admin::query()->orderBy('id')->get(['id', 'name', 'email']);

        if ($admins->isEmpty()) {
            $this->components->info('No admins found.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Name', 'Email'], $admins->map->only(['id', 'name', 'email']));

        return self::SUCCESS;
    }
}
