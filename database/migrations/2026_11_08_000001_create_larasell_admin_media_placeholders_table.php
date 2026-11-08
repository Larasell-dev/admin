<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The panel's own: what the media library shows while an image loads. The core never reads it.
        Schema::create('larasell_admin_media_placeholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->unique()->constrained('larasell_media')->cascadeOnDelete();
            // Base64 of a ThumbHash, at most 25 bytes.
            $table->string('thumbhash', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larasell_admin_media_placeholders');
    }
};
