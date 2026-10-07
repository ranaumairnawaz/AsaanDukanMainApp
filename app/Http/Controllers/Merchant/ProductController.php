<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PlanFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(private PlanFeatureService $planFeature) {}

    public function index(Request $request)
    {
        $tenant = app('tenant');
        $products = Product::with('variants')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $limit = (int) $this->planFeature->getValue($tenant, 'product_limit', 30);
        $currentCount = Product::count();

        return Inertia::render('Merchant/Products/Index', [
            'products'      => $products,
            'filters'       => $request->only(['search', 'status']),
            'product_limit' => $limit,
            'product_count' => $currentCount,
            'can_add'       => $limit === 0 || $currentCount < $limit, // 0 = unlimited
        ]);
    }

    public function create()
    {
        $tenant = app('tenant');
        $limit = (int) $this->planFeature->getValue($tenant, 'product_limit', 30);
        $currentCount = Product::count();

        if ($limit > 0 && $currentCount >= $limit) {
            return redirect()->route('merchant.products.index')
                ->with('error', "You have reached your plan limit of {$limit} products. Upgrade to add more.");
        }

        return Inertia::render('Merchant/Products/Form', ['product' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock_quantity'   => 'required|integer|min:0',
            'sku'              => 'nullable|string|max:100',
            'category'         => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'new_images'           => 'nullable|array|max:5',
            'new_images.*'         => 'image|max:3072', // 3MB each
            'variants'         => 'nullable|array',
            'variants.*.option_name'  => 'required_with:variants|string',
            'variants.*.option_value' => 'required_with:variants|string',
            'variants.*.price_override' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
        ]);

        $imagePaths = [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $imagePaths[] = $image->store('products/'. app('tenant')->id, 'public');
            }
        }

        $product = Product::create([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'price'            => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'stock_quantity'   => $validated['stock_quantity'],
            'sku'              => $validated['sku'] ?? null,
            'category'         => $validated['category'] ?? null,
            'is_active'        => $validated['is_active'] ?? true,
            'images'           => $imagePaths,
        ]);

        foreach ($validated['variants'] ?? [] as $variant) {
            $product->variants()->create($variant);
        }

        return redirect()->route('merchant.products.index')
            ->with('success', "Product \"{$product->name}\" created!");
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        return Inertia::render('Merchant/Products/Form', ['product' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock_quantity'   => 'required|integer|min:0',
            'sku'              => 'nullable|string|max:100',
            'category'         => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'new_images'       => 'nullable|array|max:5',
            'new_images.*'     => 'image|max:3072',
            'existing_images'  => 'nullable|array',
            'variants'         => 'nullable|array',
            'variants.*.id'                 => 'nullable|integer',
            'variants.*.option_name'        => 'required_with:variants|string',
            'variants.*.option_value'       => 'required_with:variants|string',
            'variants.*.price_override'     => 'nullable|numeric|min:0',
            'variants.*.stock_quantity'     => 'required_with:variants|integer|min:0',
        ]);

        // Merge existing + new images
        $existingImages = $validated['existing_images'] ?? [];
        $newImages = [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $newImages[] = $image->store('products/'. app('tenant')->id, 'public');
            }
        }

        $product->update([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'price'            => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'stock_quantity'   => $validated['stock_quantity'],
            'sku'              => $validated['sku'] ?? null,
            'category'         => $validated['category'] ?? null,
            'is_active'        => $validated['is_active'] ?? true,
            'images'           => array_merge($existingImages, $newImages),
        ]);

        // Sync variants
        $incomingVariantIds = collect($validated['variants'] ?? [])->pluck('id')->filter()->toArray();
        $product->variants()->whereNotIn('id', $incomingVariantIds)->delete();

        foreach ($validated['variants'] ?? [] as $variantData) {
            if (!empty($variantData['id'])) {
                ProductVariant::where('id', $variantData['id'])->update($variantData);
            } else {
                $product->variants()->create($variantData);
            }
        }

        return redirect()->route('merchant.products.index')
            ->with('success', "Product \"{$product->name}\" updated!");
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        // Remove images from storage
        foreach ($product->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }
        $product->delete();

        return redirect()->route('merchant.products.index')
            ->with('success', "\"{$name}\" deleted.");
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        return back()->with('success', $product->name . ' ' . ($product->is_active ? 'published' : 'hidden') . '.');
    }
}
