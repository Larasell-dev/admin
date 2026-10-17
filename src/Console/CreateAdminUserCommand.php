<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Larasell\Admin\Models\Admin;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'larasell:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create an admin.';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        if (! is_string($name) || $name === '' || ! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->components->error('Name, email, and password are required.');

            return self::FAILURE;
        }

        if (Admin::query()->where('email', $email)->exists()) {
            $this->components->error("An admin with the email [{$email}] already exists.");

            return self::FAILURE;
        }

        Admin::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->components->info("Admin user [{$email}] created.");

        return self::SUCCESS;
    }
}
