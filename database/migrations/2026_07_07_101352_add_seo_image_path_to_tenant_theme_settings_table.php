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
        Schema::table('tenant_theme_settings', function (Blueprint $table) {
            $table->string('seo_image_path')->nullable()->after('banner_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_theme_settings', function (Blueprint $table) {
            $table->dropColumn('seo_image_path');
        });
    }
};
