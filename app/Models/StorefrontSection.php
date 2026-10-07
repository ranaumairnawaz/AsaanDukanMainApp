<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StorefrontSection extends Model
{
    use HasFactory;

    public const TYPES = [
        'featured_products',
        'new_arrivals',
        'promo_banner',
        'testimonials',
        'category_showcase',
        'whatsapp_cta',
        'faq',
    ];

    protected $fillable = [
        'tenant_id',
        'type',
        'title',
        'subtitle',
        'config',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Manually curated products — only meaningful for the
     * `featured_products` section type. `new_arrivals` resolves its
     * products at render time instead (see StoreController).
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'storefront_section_products', 'section_id', 'product_id')
            ->withPivot('sort_order')
            ->orderBy('storefront_section_products.sort_order');
    }
}
