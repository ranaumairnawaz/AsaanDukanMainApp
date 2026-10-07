<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StorefrontSection;
use App\Services\ThemeRegistry;
use Inertia\Inertia;

class StoreController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $theme = ThemeRegistry::forTenant($tenant);

        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->latest()
            ->get();

        // Get unique categories
        $categories = $products->pluck('category')->filter()->unique()->values();

        $sections = $this->resolveSections($tenant, $products);

        return Inertia::render("Storefront/{$theme['slug']}/Home", [
            'tenant' => $tenant,
            'products' => $products,
            'categories' => $categories,
            'sections' => $sections,
            'theme' => [
                'slug' => $theme['slug'],
                'name' => $theme['name'],
                'accent' => $theme['accent'] ?? $theme['preview_css_vars']['--color-accent'],
                'background' => $theme['background'] ?? $theme['preview_css_vars']['--color-bg'],
                'css_vars' => $theme['preview_css_vars'],
                'settings' => $theme['settings'],
                'banner_text' => $theme['banner_text'] ?? "Welcome to {$tenant->name}",
                'text_color' => $theme['text_color'] ?? $theme['preview_css_vars']['--color-text'],
                'store_description' => $theme['store_description'] ?? 'Browse our catalog',
                'seo_title' => $theme['seo_title'] ?? null,
                'seo_description' => $theme['seo_description'] ?? null,
                'seo_image_url' => $theme['seo_image_url'] ?? null,
                'typography' => $theme['typography'] ?? null,
                'heading' => $theme['heading_font'] ?? null,
                'hero_image' => $theme['settings']?->banner_image_path,
                'logo_path' => $theme['logo_path'] ?? null,
            ],
        ]);
    }

    public function product(Product $product)
    {
        $tenant = app('tenant');
        $theme = ThemeRegistry::forTenant($tenant);

        // Security: ensure the product belongs to this tenant
        if ($product->tenant_id !== $tenant->id) {
            abort(404);
        }


        $product->load('variants');

        $relatedProducts = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category, fn($q) => $q->where('category', $product->category))
            ->take(4)
            ->get();

        return Inertia::render("Storefront/{$theme['slug']}/Product", [
            'tenant' => $tenant,
            'product' => $product,
            'related_products' => $relatedProducts,
            'theme' => [
                'slug' => $theme['slug'],
                'name' => $theme['name'],
                'accent' => $theme['accent'] ?? $theme['preview_css_vars']['--color-accent'],
                'background' => $theme['background'] ?? $theme['preview_css_vars']['--color-bg'],
                'css_vars' => $theme['preview_css_vars'],
                'settings' => $theme['settings'],
                'banner_text' => $theme['banner_text'] ?? "Welcome to {$tenant->name}",
                'text_color' => $theme['text_color'] ?? $theme['preview_css_vars']['--color-text'],
                'store_description' => $theme['store_description'] ?? 'Browse our catalog',
                'seo_title' => $theme['seo_title'] ?? null,
                'seo_description' => $theme['seo_description'] ?? null,
                'seo_image_url' => $theme['seo_image_url'] ?? null,
                'typography' => $theme['typography'] ?? null,
                'heading' => $theme['heading_font'] ?? null,
                'hero_image' => $theme['settings']?->banner_image_path,
                'logo_path' => $theme['logo_path'] ?? null,
            ],
        ]);
    }

    public function checkout()
    {
        $tenant = app('tenant');
        $theme = ThemeRegistry::forTenant($tenant);

        $cart = session("cart.{$tenant->id}", []);
        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $item) {
            $product = \App\Models\Product::with('variants')->find($item['product_id']);
            if (!$product)
                continue;

            $variant = null;
            $price = (float) $product->price;
            if (!empty($item['variant_id'])) {
                $variant = $product->variants->firstWhere('id', $item['variant_id']);
                if ($variant?->price_override)
                    $price = (float) $variant->price_override;
            }

            $qty = (int) $item['quantity'];
            $sub = $price * $qty;
            $subtotal += $sub;

            $cartItems[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->images[0] ?? null,
                'variant_id' => $variant?->id,
                'variant_name' => $variant?->option_value,
                'price' => $price,
                'quantity' => $qty,
                'subtotal' => $sub,
            ];
        }

        $coupon = session("coupon.{$tenant->id}");
        $discount = 0;
        if ($coupon) {
            $discount = $coupon['type'] === 'percentage'
                ? round($subtotal * ($coupon['value'] / 100), 2)
                : min((float) $coupon['value'], $subtotal);
        }

        $delivery = (float) ($tenant->delivery_charge ?? 0);
        $total = max(0, $subtotal - $discount) + $delivery;

        $themeData = [
            'slug' => $theme['slug'],
            'accent' => $theme['accent'] ?? $theme['preview_css_vars']['--color-accent'],
            'css_vars' => $theme['preview_css_vars'],
            'settings' => $theme['settings'],
            'logo_path' => $theme['logo_path'] ?? null,
        ];

        $checkoutPage = $theme['slug'] === 'basic'
            ? 'Storefront/basic/Checkout'
            : 'Storefront/Shared/Checkout';

        return Inertia::render($checkoutPage, [
            'tenant' => $tenant,
            'theme' => $themeData,
            'cart' => $cartItems,
            'coupon' => $coupon,
            'totals' => compact('subtotal', 'discount', 'delivery', 'total'),
        ]);
    }

    /**
     * Load every active section for this tenant and fill in `products`
     * for the two catalog-driven types:
     *
     *  - featured_products: whatever the merchant manually attached
     *    (storefront_section_products pivot, in the order they picked).
     *  - new_arrivals: the N most recent active products, N coming from
     *    section.config.limit. Computed here instead of a relation so it
     *    always reflects the live catalog with zero merchant upkeep.
     *
     * Every other type (promo_banner, testimonials, category_showcase,
     * whatsapp_cta, faq) is fully described by its own `config` JSON, so
     * no extra querying is needed for them.
     */
    private function resolveSections($tenant, $products)
    {
        return StorefrontSection::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->map(function (StorefrontSection $section) use ($products) {
                if ($section->type === 'new_arrivals') {
                    $limit = $section->config['limit'] ?? 8;
                    $section->setRelation('products', $products->take($limit)->values());
                }

                return $section;
            });
    }
}
