<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('larasell_audit_log_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('larasell_admin_users')->cascadeOnDelete();
            $table->string('format');
            $table->string('status');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('records')->nullable();
            $table->dateTime('created_at', 6);
            // Set by the job that writes it, so a second run of the job leaves it alone.
            $table->dateTime('started_at', 6)->nullable();
            $table->dateTime('expires_at', 6)->index();
        });
    }
};
