<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantPostLike extends Model
{
    protected $fillable = [
        'merchant_community_post_id',
        'tenant_id',
        'ip_address',
    ];

    public function post()
    {
        return $this->belongsTo(MerchantCommunityPost::class, 'merchant_community_post_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
