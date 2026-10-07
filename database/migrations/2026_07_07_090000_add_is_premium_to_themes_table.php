<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // Free themes (e.g. "basic") are available on every plan at no extra cost.
            // Premium themes (fashion, electronics, decor, cosmetics, ...) require the
            // tenant's plan to allow free theme switching, or a paid one-time unlock.
            $table->boolean('is_premium')->default(true)->after('is_active');
        });

        // Make sure every existing catalog theme is explicitly marked premium.
        DB::table('themes')->whereIn('slug', ['fashion', 'decor', 'cosmetics'])->update(['is_premium' => true]);

        // "electronics" already has Vue pages built (Storefront/electronics/*) but was
        // never inserted into the themes table, so it has been unselectable. Add it.
        if (!DB::table('themes')->where('slug', 'electronics')->exists()) {
            DB::table('themes')->insert([
                'name'         => 'Electronics & Gadgets',
                'slug'         => 'electronics',
                'is_active'    => true,
                'is_premium'   => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Seed the new free "Basic" theme (simple logo/name/category storefront).
        if (!DB::table('themes')->where('slug', 'basic')->exists()) {
            DB::table('themes')->insert([
                'name'         => 'Basic',
                'slug'         => 'basic',
                'is_active'    => true,
                'is_premium'   => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('is_premium');
        });

        DB::table('themes')->where('slug', 'basic')->delete();
    }
};
