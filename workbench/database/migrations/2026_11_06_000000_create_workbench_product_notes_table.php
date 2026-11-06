<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workbench_product_notes', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained('larasell_products')->cascadeOnDelete();
            $table->string('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workbench_product_notes');
    }
};
