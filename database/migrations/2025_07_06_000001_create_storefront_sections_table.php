<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // featured_products | new_arrivals | promo_banner | testimonials |
            // category_showcase | whatsapp_cta | faq
            $table->string('type');

            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            // Everything type-specific lives here as JSON — see each Vue
            // component's top-of-file comment for the exact shape expected.
            $table->json('config')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_sections');
    }
};
