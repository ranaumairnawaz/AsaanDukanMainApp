<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_section_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('storefront_sections')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_section_products');
    }
};
