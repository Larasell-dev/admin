<?php

namespace Larasell\Admin\Tables\Panel;

use Larasell\Admin\Actions\DeleteAdmin;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use LogicException;

#[UseModel(Admin::class)]
class Admins extends Table
{
    public function columns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('email'),
        ];
    }

    public function rowLink(Admin $admin): string
    {
        return route('larasell.admin.settings.admins.edit', $admin);
    }

    public function actions(): array
    {
        return [
            Action::make('Delete', handle: fn (Admin $admin) => app(DeleteAdmin::class)->handle($admin, $this->signedInAdmin()))
                ->confirm(
                    message: 'They can no longer sign in to the admin panel. This can\'t be undone.',
                    confirmationText: fn (Admin $admin): string => $admin->email,
                )
                ->icon(Icon::DeleteBin)
                ->destructive()
                ->disabled(fn (Admin $admin): bool => $admin->is($this->signedInAdmin())),
        ];
    }

    /**
     * The table only shows to signed-in admins.
     */
    private function signedInAdmin(): Admin
    {
        $admin = auth('larasell-admin')->user();

        if (! $admin instanceof Admin) {
            throw new LogicException('The admins table runs actions only for a signed-in admin.');
        }

        return $admin;
    }
}
