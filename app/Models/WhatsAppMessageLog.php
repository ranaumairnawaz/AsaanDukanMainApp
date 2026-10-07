<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_message_log';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'direction',
        'recipient_phone',
        'template_used',
        'status',
        'error_message',
    ];

    /**
     * Get the tenant this message log belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the order associated with this message (if any).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
