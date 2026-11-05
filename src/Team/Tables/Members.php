<?php

namespace Larasell\Admin\Team\Tables;

use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Attributes\UseModel;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Admin\Team\Actions\DeleteAdmin;
use LogicException;

#[UseModel(Admin::class)]
class Members extends Table
{
    public function title(): string
    {
        return 'Team';
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('email'),
        ];
    }

    public function rowLink(Admin $admin): string
    {
        return route('larasell.admin.settings.team.edit', $admin);
    }

    public function actions(): array
    {
        return [
            Action::make('Delete', handle: fn (Admin $admin) => app(DeleteAdmin::class)->handle($admin, $this->signedInAdmin()))
                ->confirm(
                    title: 'Delete member',
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
            throw new LogicException('The team table runs actions only for a signed-in admin.');
        }

        return $admin;
    }
}
