<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puts product images into media folders. Its own table, so Larasell's product images stay
     * untouched; an image without a row is at the top level, and it is in at most one folder.
     */
    public function up(): void
    {
        Schema::create('larasell_admin_media_folder_product_image', function (Blueprint $table) {
            $table->foreignId('folder_id')->constrained('larasell_admin_media_folders')->cascadeOnDelete();
            $table->foreignId('product_image_id')->unique()->constrained('larasell_product_images')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larasell_admin_media_folder_product_image');
    }
};
