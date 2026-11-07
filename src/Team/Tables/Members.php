<?php

namespace Larasell\Admin\Team\Tables;

use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Attributes\UseModel;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Admin\Team\Actions\DeleteAdmin;
use Larasell\Chronicle\Exceptions\StructuredLogicException;

#[UseModel(Admin::class)]
class Members extends Table
{
    public function title(): string
    {
        return __('Team');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name')),
            TextColumn::make('email', __('Email')),
        ];
    }

    public function rowLink(Admin $admin): string
    {
        return route('larasell.admin.settings.team.edit', $admin);
    }

    public function actions(): array
    {
        return [
            Action::make(Translate::later('Delete'), handle: fn (Admin $admin) => app(DeleteAdmin::class)->handle($admin, $this->signedInAdmin()))
                ->confirm(
                    title: __('Delete member'),
                    message: __('They can no longer sign in to the admin panel. This can\'t be undone.'),
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
            throw new StructuredLogicException(
                errorCode: 'larasell-admin.team.table-without-admin',
                message: 'The team table runs actions only for a signed-in admin.',
                why: 'The table is behind the panel\'s authentication, so an action without a signed-in admin is a bug.',
            );
        }

        return $admin;
    }
}
