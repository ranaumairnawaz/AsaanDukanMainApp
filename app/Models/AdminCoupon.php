<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminCoupon extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'admin_coupon_id');
    }

    public function isValid()
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    public function calculateDiscount($originalAmount)
    {
        if ($this->discount_type === 'percentage') {
            return round($originalAmount * ($this->discount_amount / 100), 2);
        } else {
            return min($originalAmount, $this->discount_amount);
        }
    }
}
