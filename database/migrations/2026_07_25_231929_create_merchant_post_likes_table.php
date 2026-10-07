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
        Schema::create('merchant_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_community_post_id')->constrained('merchant_community_posts')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->unique(['merchant_community_post_id', 'tenant_id'], 'unique_tenant_like');
            $table->unique(['merchant_community_post_id', 'ip_address'], 'unique_ip_like');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_post_likes');
    }
};
