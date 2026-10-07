<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StorefrontSection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SectionController extends Controller
{
    /**
     * List every section this tenant has added, in display order.
     */
    public function index()
    {
        $tenant = app('tenant');

        $sections = StorefrontSection::where('tenant_id', $tenant->id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Merchant/Sections/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Blank form for a new section. `?type=` preselects the block the
     * merchant clicked from the "+ Add Section" menu.
     */
    public function create(Request $request)
    {
        $tenant = app('tenant');

        return Inertia::render('Merchant/Sections/Form', [
            'section'  => null,
            'type'     => $request->query('type', 'featured_products'),
            'products' => $this->productOptions($tenant),
        ]);
    }

    public function edit(StorefrontSection $section)
    {
        $this->authorizeSection($section);
        $tenant = app('tenant');

        return Inertia::render('Merchant/Sections/Form', [
            'section'  => $section->load(['products:id,name,price,images']),
            'type'     => $section->type,
            'products' => $this->productOptions($tenant),
        ]);
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'type'      => ['required', Rule::in(StorefrontSection::TYPES)],
            'title'     => 'nullable|string|max:255',
            'subtitle'  => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $section = StorefrontSection::create([
            'tenant_id'  => $tenant->id,
            'type'       => $validated['type'],
            'title'      => $validated['title'] ?? null,
            'subtitle'   => $validated['subtitle'] ?? null,
            'config'     => $this->buildConfig($request, $validated['type']),
            'is_active'  => $request->boolean('is_active', true),
            'sort_order' => (StorefrontSection::where('tenant_id', $tenant->id)->max('sort_order') ?? -1) + 1,
        ]);

        if ($validated['type'] === 'featured_products') {
            $this->syncProducts($section, $request->input('product_ids', []));
        }

        return redirect()->route('merchant.sections.index')
            ->with('success', 'Section added to your storefront.');
    }

    public function update(Request $request, StorefrontSection $section)
    {
        $this->authorizeSection($section);

        $validated = $request->validate([
            'title'     => 'nullable|string|max:255',
            'subtitle'  => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $section->update([
            'title'     => $validated['title'] ?? null,
            'subtitle'  => $validated['subtitle'] ?? null,
            'config'    => $this->buildConfig($request, $section->type, $section),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($section->type === 'featured_products') {
            $this->syncProducts($section, $request->input('product_ids', []));
        }

        return redirect()->route('merchant.sections.index')->with('success', 'Section updated.');
    }

    public function destroy(StorefrontSection $section)
    {
        $this->authorizeSection($section);
        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    /**
     * Quick on/off switch from the list page — doesn't touch config.
     */
    public function toggleActive(StorefrontSection $section)
    {
        $this->authorizeSection($section);
        $section->update(['is_active' => ! $section->is_active]);

        return back();
    }

    /**
     * Persist new drag-and-drop order from the list page.
     * Body: { ids: [3, 1, 2, ...] } — already in the desired order.
     */
    public function reorder(Request $request)
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|distinct|exists:storefront_sections,id',
        ]);

        DB::transaction(function () use ($validated, $tenant) {
            foreach ($validated['ids'] as $index => $id) {
                StorefrontSection::where('id', $id)
                    ->where('tenant_id', $tenant->id)
                    ->update(['sort_order' => $index]);
            }
        });

        return back();
    }

    /**
     * Build the `config` JSON blob for the given type. Handles file
     * uploads (promo_banner image, category_showcase per-item image)
     * and preserves previously-stored paths on update when no new file
     * is provided.
     */
    private function buildConfig(Request $request, string $type, ?StorefrontSection $existing = null): array
    {
        $tenant = app('tenant');
        $existingConfig = $existing?->config ?? [];

        return match ($type) {
            'promo_banner' => [
                'cta_text' => $request->input('cta_text'),
                'cta_link' => $request->input('cta_link') ?: '#products',
                'layout'   => $request->input('layout', 'image_right'),
                'image_path' => $request->hasFile('image')
                    ? $this->storeImage($request->file('image'), $tenant, 'banners')
                    : ($request->input('existing_image') ?: ($existingConfig['image_path'] ?? null)),
            ],

            'whatsapp_cta' => [
                'message' => $request->input('message'),
            ],

            'new_arrivals' => [
                'limit' => max(1, (int) $request->input('limit', 8)),
            ],

            'featured_products' => [
                'layout'  => $request->input('layout', 'grid'),
                'columns' => max(2, min(6, (int) $request->input('columns', 4))),
            ],

            'testimonials' => [
                'items' => collect($request->input('items', []))
                    ->filter(fn ($item) => filled($item['text'] ?? null))
                    ->map(fn ($item) => [
                        'name'     => $item['name'] ?? 'Happy Customer',
                        'text'     => $item['text'],
                        'rating'   => isset($item['rating']) && $item['rating'] !== ''
                            ? max(1, min(5, (int) $item['rating'])) : null,
                        'location' => $item['location'] ?: null,
                    ])->values()->all(),
            ],

            'faq' => [
                'items' => collect($request->input('items', []))
                    ->filter(fn ($item) => filled($item['question'] ?? null))
                    ->map(fn ($item) => [
                        'question' => $item['question'],
                        'answer'   => $item['answer'] ?? '',
                    ])->values()->all(),
            ],

            'category_showcase' => [
                'items' => collect($request->input('items', []))
                    ->filter(fn ($item) => filled($item['label'] ?? null))
                    ->values()
                    ->map(function ($item, $i) use ($request, $tenant, $existingConfig) {
                        /** @var UploadedFile|null $file */
                        $file = $request->file("items.$i.image");

                        return [
                            'label'      => $item['label'],
                            'category'   => $item['category'] ?? '',
                            'image_path' => $file
                                ? $this->storeImage($file, $tenant, 'categories')
                                : ($item['existing_image'] ?: ($existingConfig['items'][$i]['image_path'] ?? null)),
                        ];
                    })->all(),
            ],

            default => [],
        };
    }

    private function storeImage(UploadedFile $file, $tenant, string $folder): string
    {
        return $file->store("{$folder}/{$tenant->id}", 'public');
    }

    /**
     * Attach the merchant's chosen products to a `featured_products`
     * section, preserving the order they were picked in.
     */
    private function syncProducts(StorefrontSection $section, array $productIds): void
    {
        $tenant = app('tenant');

        // Guard against a merchant (or tampered request) attaching a
        // product that doesn't belong to their store.
        $ownedIds = Product::where('tenant_id', $tenant->id)
            ->whereIn('id', $productIds)
            ->pluck('id');

        $pivotData = collect($productIds)
            ->intersect($ownedIds)
            ->values()
            ->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])
            ->all();

        $section->products()->sync($pivotData);
    }

    private function productOptions($tenant)
    {
        return Product::where('tenant_id', $tenant->id)
            ->select('id', 'name', 'price', 'images')
            ->orderBy('name')
            ->get();
    }

    private function authorizeSection(StorefrontSection $section): void
    {
        if ($section->tenant_id !== app('tenant')->id) {
            abort(404);
        }
    }
}
