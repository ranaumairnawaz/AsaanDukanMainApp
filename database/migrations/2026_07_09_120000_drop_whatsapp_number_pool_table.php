<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The whatsapp_number_pool table backed the abandoned internal Baileys
 * session-pool gateway (multi-number warmup/QR pairing). The project now
 * only uses the external API-based WhatsApp gateway via
 * App\Services\WhatsAppMessageService::send(), so this table is no longer
 * needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('whatsapp_number_pool');
    }

    public function down(): void
    {
        Schema::create('whatsapp_number_pool', function ($table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->string('status')->default('standby');
            $table->longText('session_data')->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('warmup_started_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
