<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix order overage tracking so counts are based on the tenant's actual
 * billing cycle (billing_cycle_start) instead of the current calendar month.
 *
 * Changes:
 * 1. tenants — add billing_cycle_start (set when payment is approved)
 * 2. subscription_payments — add orders_count_snapshot & order_overage_count
 *    (locked-in at the time the merchant submits their payment proof)
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Add billing cycle start date to tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('billing_cycle_start')->nullable()->after('subscription_expires_at');
        });

        // 2. Snapshot order count and overage in subscription payments
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->integer('orders_count_snapshot')->nullable()->after('amount')
                ->comment('Total orders placed since billing_cycle_start at time of payment submission');
            $table->integer('order_overage_count')->nullable()->after('orders_count_snapshot')
                ->comment('Number of orders that exceeded the monthly limit at submission time');
            $table->integer('order_limit_snapshot')->nullable()->after('order_overage_count')
                ->comment('The plan order limit that was active at the time of submission');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn(['orders_count_snapshot', 'order_overage_count', 'order_limit_snapshot']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('billing_cycle_start');
        });
    }
};
