<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Larasell\Admin\Actions\DeleteAdmin;
use Larasell\Admin\Audit\SystemActor;
use Larasell\Admin\Exceptions\AdminRemovalFailed;
use Larasell\Admin\Models\Admin;

class RemoveAdminCommand extends Command
{
    protected $signature = 'larasell:remove-admin {email} {--force : Remove the admin without asking for confirmation}';

    protected $description = 'Remove an admin.';

    public function handle(DeleteAdmin $deleteAdmin): int
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

        try {
            $deleteAdmin->handle($admin, new SystemActor('larasell:remove-admin'));
        } catch (AdminRemovalFailed $exception) {
            if ($exception->errorCode() !== 'larasell-admin.admin.last-admin') {
                throw $exception;
            }

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Admin user [{$admin->email}] removed.");

        return self::SUCCESS;
    }
}
