<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_per_day', 12, 2);
            $table->string('type')->nullable();
            $table->string('status')->default('available'); // available, rented, maintenance
            $table->string('image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('car_categories')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
