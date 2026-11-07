<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\UnknownActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\AuditRecords\AdminCreated;
use Larasell\Admin\Team\Rules\AdminPassword;

class CreateAdmin
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Adds an admin, audited as created by the actor: the signed-in admin, or a SystemActor in
     * jobs and commands. No admin is added if that can't be audited.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(array $input, Actor|UnknownActor $actor): Admin
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Admin::class)],
            'password' => ['required', 'string', new AdminPassword],
        ], [
            'email.unique' => __('An admin with the email [:input] already exists.'),
        ])->validate();

        return DB::transaction(function () use ($validated, $actor): Admin {
            $admin = Admin::query()->create($validated);

            $this->auditor->record(new AdminCreated($admin), $actor);

            return $admin;
        });
    }
}
