<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMessageUsage extends Model
{
    use HasFactory;

    protected $table = 'tenant_message_usage';

    protected $fillable = [
        'tenant_id',
        'billing_period_start',
        'billing_period_end',
        'messages_sent_count',
        'quota_limit',
        'quota_exceeded_action',
        'overage_count',
    ];

    protected $casts = [
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'messages_sent_count' => 'integer',
        'quota_limit' => 'integer',
        'overage_count' => 'integer',
    ];

    /**
     * Get the tenant that owns this message usage record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
