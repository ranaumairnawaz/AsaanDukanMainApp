<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'amount',
        'payment_method',
        'status',
        'gateway_reference',
        'billing_period_start',
        'billing_period_end',
        'admin_notes',
        'proof_image_path',
        'admin_coupon_id',
        'discount_applied',
        'orders_count_snapshot',
        'order_overage_count',
        'order_limit_snapshot',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'orders_count_snapshot' => 'integer',
        'order_overage_count' => 'integer',
        'order_limit_snapshot' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function adminCoupon(): BelongsTo
    {
        return $this->belongsTo(AdminCoupon::class, 'admin_coupon_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
