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
        Schema::create('one_time_unlock_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // type: 'theme_change' | 'store_info_change'
            $table->string('unlock_type');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending | approved | rejected
            // Payment proof (same as subscription payment flow)
            $table->string('payment_proof_path')->nullable();
            $table->text('admin_note')->nullable();
            // Once approved, apply immediately
            $table->boolean('applied')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_time_unlock_payments');
    }
};
