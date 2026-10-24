<?php

namespace Larasell\Admin\Tables\Panel;

use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

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
}
