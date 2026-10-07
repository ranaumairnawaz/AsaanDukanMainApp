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
        // 1. Subscription Plans
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('store_slug')->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->string('subdomain_status')->default('none'); // none, pending, active
            $table->string('custom_domain')->nullable()->unique();
            $table->string('category')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status')->default('trial'); // active, suspended, trial
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamps();
        });

        // 3. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('sku')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Product Variants
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('option_name'); // e.g. Size, Color
            $table->string('option_value'); // e.g. Large, Red
            $table->decimal('price_override', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();
        });

        // 5. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 6. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('status')->default('pending'); // pending, confirmed, processing, shipped, delivered, cancelled
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('payment_method')->default('cod'); // cod, jazzcash, easypaisa, bank_transfer
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->text('delivery_address');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        // 8. WhatsApp Message Log
        Schema::create('whatsapp_message_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('direction'); // inbound, outbound
            $table->string('recipient_phone');
            $table->string('template_used')->nullable();
            $table->string('status'); // queued, sent, delivered, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 9. Subscription Payments
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('gateway_reference')->nullable();
            $table->timestamp('billing_period_start')->nullable();
            $table->timestamp('billing_period_end')->nullable();
            $table->timestamps();
        });

        // 10. Plan Features
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('feature_key');
            $table->text('feature_value'); // can store bools, ints, or JSON lists
            $table->timestamps();
        });

        // 11. Tenant Theme Settings
        Schema::create('tenant_theme_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('theme_slug');
            $table->string('accent_color')->nullable();
            $table->string('logo_override_path')->nullable();
            $table->string('banner_image_path')->nullable();
            $table->json('custom_settings')->nullable();
            $table->timestamp('theme_last_changed_at')->nullable();
            $table->timestamps();
        });

        // 12. Tenant Message Usage
        Schema::create('tenant_message_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamp('billing_period_start')->nullable();
            $table->timestamp('billing_period_end')->nullable();
            $table->integer('messages_sent_count')->default(0);
            $table->integer('quota_limit')->default(0);
            $table->string('quota_exceeded_action')->default('charge_overage'); // charge_overage, block
            $table->integer('overage_count')->default(0);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_message_usage');
        Schema::dropIfExists('tenant_theme_settings');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('whatsapp_message_log');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('subscription_plans');
    }
};
