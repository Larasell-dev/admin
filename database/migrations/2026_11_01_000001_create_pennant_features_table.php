<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Pennant\Migrations\PennantMigration;

/**
 * The table Pennant's database store keeps feature values in, such as audit-log-filters. Apps that
 * already use Pennant have it, from Pennant's own migration, so it is only created when missing,
 * on the connection and with the name the app configured for Pennant.
 */
return new class extends PennantMigration
{
    public function up(): void
    {
        $table = config('pennant.stores.database.table') ?? 'features';

        if (Schema::connection($this->getConnection())->hasTable($table)) {
            return;
        }

        Schema::connection($this->getConnection())->create($table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope');
            $table->text('value');
            $table->timestamps();

            $table->unique(['name', 'scope']);
        });
    }

    public function down(): void
    {
        // The table may be the app's own, with features of its own, so it stays.
    }
};
