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
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->after('bill_image');
            $table->unsignedBigInteger('payment_confirmed_by')->nullable()->after('payment_confirmed_at');
            $table->string('payment_note', 255)->nullable()->after('payment_confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_confirmed_at', 'payment_confirmed_by', 'payment_note']);
        });
    }
};
