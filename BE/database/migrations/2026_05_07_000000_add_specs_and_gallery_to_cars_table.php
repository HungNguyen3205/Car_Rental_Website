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
        Schema::table('cars', function (Blueprint $table) {
            if (!Schema::hasColumn('cars', 'seats')) {
                $table->unsignedTinyInteger('seats')->nullable()->after('type');
            }
            if (!Schema::hasColumn('cars', 'battery_capacity')) {
                $table->string('battery_capacity', 100)->nullable()->after('seats');
            }
            if (!Schema::hasColumn('cars', 'range_per_charge')) {
                $table->string('range_per_charge', 100)->nullable()->after('battery_capacity');
            }
            if (!Schema::hasColumn('cars', 'transmission')) {
                $table->string('transmission', 50)->nullable()->after('range_per_charge');
            }
            if (!Schema::hasColumn('cars', 'year_of_manufacture')) {
                $table->unsignedSmallInteger('year_of_manufacture')->nullable()->after('transmission');
            }
            if (!Schema::hasColumn('cars', 'gallery')) {
                $table->json('gallery')->nullable()->after('image');
            }
            if (!Schema::hasColumn('cars', 'rental_conditions')) {
                $table->json('rental_conditions')->nullable()->after('gallery');
            }
            if (!Schema::hasColumn('cars', 'extra_fees_info')) {
                $table->json('extra_fees_info')->nullable()->after('rental_conditions');
            }
            if (!Schema::hasColumn('cars', 'description')) {
                $table->text('description')->nullable()->after('extra_fees_info');
            }
            if (!Schema::hasColumn('cars', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'seats',
                'battery_capacity',
                'range_per_charge',
                'transmission',
                'year_of_manufacture',
                'gallery',
                'rental_conditions',
                'extra_fees_info',
                'description',
                'is_active',
            ]);
        });
    }
};
