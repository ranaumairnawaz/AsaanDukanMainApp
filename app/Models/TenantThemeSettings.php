<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantThemeSettings extends Model
{
    use HasFactory;

    protected $table = 'tenant_theme_settings';

    protected $fillable = [
        'tenant_id',
        'theme_slug',
        'accent_color',
        'secondary_color',
        'typography',
        'favicon_path',
        'logo_override_path',
        'banner_image_path',
        'custom_settings',
        'heading_font',
        'seo_image_path',
        'theme_last_changed_at',
    ];

    protected $casts = [
        'custom_settings' => 'array',
        'theme_last_changed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns these theme settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
