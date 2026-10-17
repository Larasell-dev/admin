<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Actions\CreateAdmin;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'larasell:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create an admin.';

    public function handle(CreateAdmin $createAdmin): int
    {
        try {
            $admin = $createAdmin->handle([
                'name' => $this->option('name'),
                'email' => $this->option('email'),
                'password' => $this->option('password'),
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->components->info("Admin user [{$admin->email}] created.");

        return self::SUCCESS;
    }
}
