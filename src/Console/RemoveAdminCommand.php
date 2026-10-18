<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Exceptions\AdminRemovalFailed;
use Larasell\Admin\Models\Admin;

class RemoveAdminCommand extends Command
{
    protected $signature = 'larasell:remove-admin {email} {--force : Remove the admin without asking for confirmation}';

    protected $description = 'Remove an admin.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $admin = Admin::query()->where('email', $email)->first();

        if ($admin === null) {
            $this->components->error("No admin with the email [{$email}] exists.");

            return self::FAILURE;
        }

        if ($admin->email !== $email) {
            throw AdminRemovalFailed::emailMismatch($email, $admin->email);
        }

        if (Admin::query()->count() === 1) {
            $this->components->error("Admin user [{$admin->email}] is the last admin and cannot be removed.");

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                $this->components->error('Use --force to remove an admin in a non-interactive session.');

                return self::FAILURE;
            }

            if (! $this->confirm("Remove the admin [{$admin->email}]?")) {
                $this->components->warn("Admin user [{$admin->email}] was not removed.");

                return self::FAILURE;
            }
        }

        if ($admin->delete() !== true) {
            throw AdminRemovalFailed::notDeleted($admin->email);
        }

        if (Admin::query()->whereKey($admin->getKey())->exists()) {
            throw AdminRemovalFailed::stillExists($admin->email);
        }

        if ($admin->avatar_path !== null && ! Storage::disk(config('larasell-admin.disk'))->delete($admin->avatar_path)) {
            throw AdminRemovalFailed::avatarNotDeleted($admin->email, $admin->avatar_path);
        }

        $this->components->info("Admin user [{$admin->email}] removed.");

        return self::SUCCESS;
    }
}
