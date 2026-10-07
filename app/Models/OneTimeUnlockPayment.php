<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneTimeUnlockPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'unlock_type',
        'amount',
        'status',
        'payment_proof_path',
        'admin_note',
        'applied',
    ];

    protected $casts = [
        'applied' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
