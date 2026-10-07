<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'merchant_owns_domain',
        'requested_value',
        'status',
        'quoted_price',
        'payment_proof_path',
        'admin_note',
        'linked_at',
    ];

    protected $casts = [
        'merchant_owns_domain' => 'boolean',
        'quoted_price' => 'decimal:2',
        'linked_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function awaitingPayment(): bool
    {
        return $this->status === 'quote_sent';
    }
}
