<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('larasell_audit_log', function (Blueprint $table) {
            $table->unsignedBigInteger('seq')->nullable();
            $table->char('previous_hash', 64)->nullable();
            $table->char('hash', 64)->nullable();
        });

        Schema::create('larasell_audit_log_head', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('seq');
            $table->char('hash', 64)->nullable();
        });

        // Chains the records stored so far in the order they were recorded.
        $chain = app(AuditChain::class);
        $seq = 0;
        $previousHash = null;

        foreach (DB::table('larasell_audit_log')->orderBy('created_at')->orderBy('id')->lazy() as $record) {
            $record = [...(array) $record, 'seq' => ++$seq, 'previous_hash' => $previousHash];
            $previousHash = $chain->hash($record);

            DB::table('larasell_audit_log')->where('id', $record['id'])->update(['seq' => $seq, 'previous_hash' => $record['previous_hash'], 'hash' => $previousHash]);
        }

        DB::table('larasell_audit_log_head')->insert(['id' => 1, 'seq' => $seq, 'hash' => $previousHash]);

        Schema::table('larasell_audit_log', function (Blueprint $table) {
            $table->unsignedBigInteger('seq')->nullable(false)->change();
            $table->char('hash', 64)->nullable(false)->change();
            $table->unique('seq');
        });
    }
};
