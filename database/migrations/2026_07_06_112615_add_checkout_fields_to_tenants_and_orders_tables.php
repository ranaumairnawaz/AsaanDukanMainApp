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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('whatsapp_number')->nullable()->after('status');
            $table->decimal('delivery_charge', 10, 2)->default(0)->after('whatsapp_number');
            $table->string('bank_name')->nullable()->after('delivery_charge');
            $table->string('account_title')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_title');
        });

        // The orders table already has delivery_fee and payment_method, we just ensure no missing fields
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'delivery_charge',
                'bank_name',
                'account_title',
                'account_number'
            ]);
        });
    }
};
