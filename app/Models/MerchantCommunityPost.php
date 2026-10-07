<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantCommunityPost extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'body',
        'image_path',
        'video_path',
        'likes_count',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(MerchantPostLike::class);
    }
}
