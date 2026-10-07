<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('type', ['subdomain', 'custom_domain']);
            $table->string('requested_value'); // e.g. "mystore" or "mystore.com"
            $table->enum('status', [
                'pending',           // just submitted
                'quote_sent',        // admin sent price (custom domain only)
                'payment_submitted', // merchant uploaded payment proof
                'active',            // approved and linked
                'rejected',          // rejected by admin
            ])->default('pending');
            $table->decimal('quoted_price', 10, 2)->nullable();  // admin sets for custom domain
            $table->string('payment_proof_path')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_requests');
    }
};
