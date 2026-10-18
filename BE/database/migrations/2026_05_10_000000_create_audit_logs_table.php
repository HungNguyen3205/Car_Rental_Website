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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 30)->default('system'); // admin, user, system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 100)->nullable();
            $table->string('actor_email', 150)->nullable();
            $table->string('action', 80); // e.g. car.created, booking.status_updated, payment.confirmed
            $table->string('target_type', 50)->nullable(); // Car, Booking, Admin, User, etc.
            $table->string('target_id', 50)->nullable();
            $table->string('target_label', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id']);
            $table->index('action');
            $table->index('target_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
