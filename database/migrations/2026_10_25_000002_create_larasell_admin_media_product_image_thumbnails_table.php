<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The small preview the media table shows for a product image, on Larasell's images disk.
     * Its own table, so Larasell's product images stay untouched; an image without a row has none yet.
     */
    public function up(): void
    {
        Schema::create('larasell_admin_media_product_image_thumbnails', function (Blueprint $table) {
            $table->foreignId('product_image_id')->primary()->constrained('larasell_product_images')->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larasell_admin_media_product_image_thumbnails');
    }
};
