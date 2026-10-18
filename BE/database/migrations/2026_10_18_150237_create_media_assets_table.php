<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_name');
            $table->string('disk', 50)->default('public');
            $table->string('path', 500);
            $table->string('url', 500)->nullable();
            $table->string('mime_type', 100);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('visibility', 20)->default('public'); // public | private
            $table->string('collection', 50)->default('cars');   // cars | marketing | receipts | handovers | general
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->string('uploaded_by_type', 50)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'collection']);
            $table->index('mime_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
