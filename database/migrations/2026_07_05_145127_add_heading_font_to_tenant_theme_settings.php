<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_theme_settings', function (Blueprint $table) {
            $table->string('heading_font')->nullable()->after('typography');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_theme_settings', function (Blueprint $table) {
            $table->dropColumn('heading_font');
        });
    }
};
