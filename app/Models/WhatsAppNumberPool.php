<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppNumberPool extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_number_pool';

    protected $fillable = [
        'phone_number',
        'status',
        'session_data',
        'paired_at',
        'last_active_at',
        'warmup_started_at',
        'notes',
    ];

    protected $casts = [
        'paired_at' => 'datetime',
        'last_active_at' => 'datetime',
        'warmup_started_at' => 'datetime',
    ];

    /**
     * Check if this number is the active gateway number.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
