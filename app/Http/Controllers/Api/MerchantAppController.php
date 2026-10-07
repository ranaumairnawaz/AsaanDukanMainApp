<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\DomainRequest;
use App\Models\Event;
use App\Models\MerchantCommunityPost;
use App\Models\MerchantPostLike;
use App\Models\NewsItem;
use App\Models\OneTimeUnlockPayment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StorefrontSection;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantMessageUsage;
use App\Models\TenantThemeSettings;
use App\Models\User;
use App\Services\PlanFeatureService;
use App\Services\ThemeRegistry;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MerchantAppController extends Controller
{
    public function __construct(private PlanFeatureService $planFeature)
    {
    }

    // =========================================================================
    //  HELPER
    // =========================================================================

    private function tenant()
    {
        return auth()->user()->tenant;
    }

    private function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    private function err(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function transformOrder(Order $order): array
    {
        $arr = $order->toArray();
        $arr['customer_name'] = $order->customer?->name ?? $arr['customer_name'] ?? null;
        $arr['customer_phone'] = $order->customer?->phone ?? $arr['customer_phone'] ?? null;
        $arr['customer_address'] = $order->customer?->address ?? $arr['customer_address'] ?? null;
        return $arr;
    }

    // =========================================================================
    //  AUTH
    // =========================================================================

    /**
     * POST /api/merchant/login
     * Login with email + password. Returns a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return $this->err('Invalid credentials.', 401);
        }

        $user = Auth::user();

        if ($user->is_admin) {
            Auth::logout();
            return $this->err('Admin accounts cannot use the merchant API.', 403);
        }

        if (!$user->tenant) {
            Auth::logout();
            return $this->err('No merchant store is linked to this account.', 403);
        }

        // Revoke old tokens issued by this device (optional – comment out to allow multi-device)
        $user->tokens()->where('name', 'merchant-app')->delete();

        $token = $user->createToken('merchant-app')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'tenant_role' => $user->tenant_role,
            ],
            'store' => [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
                'store_slug' => $user->tenant->store_slug,
                'status' => $user->tenant->status,
            ],
        ], 'Logged in successfully.');
    }

    /**
     * POST /api/merchant/register
     * Register a new merchant (owner) with a fresh store/tenant.
     * All plans get a 7-day free trial. Premium themes are unlocked during
     * registration when the user selects a paid plan (trial access).
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
            'store_name' => 'required|string|max:120',
            'store_slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('tenants', 'store_slug')],
            'category' => 'nullable|string|max:80',
            'theme_slug' => 'nullable|string|max:60',
            'plan_slug' => 'nullable|string|max:60',
        ]);

        // Resolve chosen plan (default to cheapest active plan)
        $planSlug = $data['plan_slug'] ?? null;
        $selectedPlan = $planSlug
            ? SubscriptionPlan::where('slug', $planSlug)->where('is_active', true)->first()
            : null;
        if (!$selectedPlan) {
            $selectedPlan = SubscriptionPlan::where('is_active', true)
                ->orderBy('price_monthly', 'asc')
                ->first();
        }

        // Determine if selected plan allows premium themes
        $premiumThemesAllowed = false;
        if ($selectedPlan) {
            $feature = \App\Models\PlanFeature::where('plan_id', $selectedPlan->id)->where('feature_key', 'premium_themes_allowed')->first();
            $premiumThemesAllowed = $feature ? filter_var($feature->feature_value, FILTER_VALIDATE_BOOLEAN) : false;
        }

        // Validate theme_slug against known themes (if provided)
        $chosenTheme = $data['theme_slug'] ?? null;
        if ($chosenTheme) {
            $knownThemes = ThemeRegistry::themes();
            if (!isset($knownThemes[$chosenTheme])) {
                return $this->err("Selected theme '{$chosenTheme}' does not exist.", 422);
            }
            // Premium themes allowed on registration only if user selected a plan with premium_themes_allowed
            if (ThemeRegistry::isPremium($chosenTheme) && !$premiumThemesAllowed) {
                return $this->err("Premium themes require a plan upgrade. Please select a free theme or upgrade your plan.", 422);
            }
        }

        return DB::transaction(function () use ($data, $chosenTheme, $selectedPlan) {
            // Create the Tenant (store) — 7-day free trial for ALL plans
            $tenant = Tenant::create([
                'name' => $data['store_name'],
                'store_slug' => Str::slug($data['store_slug']),
                'category' => $data['category'] ?? null,
                'status' => 'active',
                'subscription_plan_id' => $selectedPlan?->id,
                'subscription_expires_at' => now()->addDays(7), // 7-day trial for all plans
            ]);

            // Create the owner User linked to the Tenant
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'tenant_id' => $tenant->id,
                'tenant_role' => 'owner',
                'is_admin' => false,
            ]);

            // Seed initial Theme Settings (chosen or default to 'basic')
            $themeSlug = $chosenTheme ?? 'basic';
            $preset = ThemeRegistry::get($themeSlug);
            TenantThemeSettings::create([
                'tenant_id' => $tenant->id,
                'theme_slug' => $themeSlug,
                'accent_color' => $preset['preview_css_vars']['--color-accent'] ?? '#1F7A4D',
                'secondary_color' => $preset['preview_css_vars']['--color-surface'] ?? '#FFFFFF',
                'typography' => 'Inter',
                'heading_font' => 'Lora',
            ]);

            // Issue Sanctum token for immediate login
            $token = $user->createToken('merchant-app')->plainTextToken;

            // Send Welcome Email and WhatsApp Notification
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\MerchantWelcomeMail($user, $tenant));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send welcome email: " . $e->getMessage());
            }

            try {
                $waService = new \App\Services\WhatsAppMessageService();
                $waService->notifyMerchantWelcome($tenant, $user);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send welcome WA message: " . $e->getMessage());
            }

            return $this->ok([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'tenant_role' => $user->tenant_role,
                ],
                'store' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'store_slug' => $tenant->store_slug,
                    'status' => $tenant->status,
                ],
            ], 'Account created successfully. Welcome to Asaan Dukan!', 201);
        });
    }

    /**
     * GET /api/merchant/public/themes
     * Return available themes for the registration screen — no auth required.
     * Accepts optional ?plan_slug= to indicate what plan user selected.
     * On paid plans, premium themes are returned as unlocked.
     */
    public function publicThemes(Request $request): JsonResponse
    {
        $planSlug = $request->query('plan_slug');
        $premiumThemesAllowed = false;

        if ($planSlug) {
            $plan = SubscriptionPlan::where('slug', $planSlug)->where('is_active', true)->first();
            if ($plan) {
                $feature = \App\Models\PlanFeature::where('plan_id', $plan->id)->where('feature_key', 'premium_themes_allowed')->first();
                $premiumThemesAllowed = $feature ? filter_var($feature->feature_value, FILTER_VALIDATE_BOOLEAN) : false;
            }
        }

        $themes = ThemeRegistry::themes();

        $result = [];
        foreach ($themes as $slug => $meta) {
            $isPremium = $meta['is_premium'];
            $screenshot = $meta['screenshot'] ?? null;
            $demoUrl = $meta['demo_url'] ?? null;
            $result[] = [
                'slug' => $slug,
                'name' => $meta['name'],
                'description' => $meta['description'] ?? '',
                // On plans with premium_themes_allowed, premium themes are accessible
                'is_premium' => $premiumThemesAllowed ? false : $isPremium,
                'is_actually_premium' => $isPremium, // always reflects true premium status
                'accent' => $meta['accent'],
                'screenshot_url' => $screenshot ? asset("storage/{$screenshot}") : null,
                'demo_url' => $demoUrl ? (str_starts_with($demoUrl, 'http') ? $demoUrl : url($demoUrl)) : null,
                'preview_css_vars' => $meta['preview_css_vars'] ?? [],
            ];
        }

        return $this->ok($result);
    }

    /**
     * GET /api/merchant/public/plans
     * Return all active subscription plans for the registration screen.
     */
    public function publicPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::with('features')->where('is_active', true)
            ->orderBy('price_monthly', 'asc')
            ->get();

        $result = $plans->map(fn($plan) => [
            'id' => $plan->id,
            'slug' => $plan->slug,
            'name' => $plan->name,
            'price_monthly' => (float) $plan->price_monthly,
            'price_yearly' => (float) $plan->price_yearly,
            'is_free' => $plan->price_monthly == 0,
            'features' => $plan->features->map(fn($f) => $f->feature_label ?? $f->feature_key)->values(),
        ]);

        return $this->ok($result);
    }

    /**
     * POST /api/merchant/logout
     * Revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->ok(message: 'Logged out successfully.');
    }

    /**
     * GET /api/merchant/me
     * Return the authenticated merchant profile and store info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $this->tenant();
        $tenant->load('subscriptionPlan');

        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'tenant_role' => $user->tenant_role,
            ],
            'store' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'store_slug' => $tenant->store_slug,
                'status' => $tenant->status,
                'subdomain' => $tenant->subdomain,
                'custom_domain' => $tenant->custom_domain,
                'whatsapp_number' => $tenant->whatsapp_number,
                'delivery_charge' => $tenant->delivery_charge,
                'subscription_plan' => $tenant->subscriptionPlan?->name,
                'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
                'payment_accounts' => \App\Models\GlobalSetting::getVal('payment_accounts', []),
            ],
        ]);
    }

    // =========================================================================
    //  DASHBOARD
    // =========================================================================

    /**
     * GET /api/merchant/dashboard
     * Returns stats, recent orders, and top-selling products.
     */
    public function dashboard(): JsonResponse
    {
        $tenant = $this->tenant();
        $analyticsEnabled = $this->planFeature->isEnabled($tenant, 'analytics_enabled');

        $stats = [
            'total_products' => Product::where('tenant_id', $tenant->id)->count(),
            'active_products' => Product::where('tenant_id', $tenant->id)->where('is_active', true)->count(),
            'total_orders' => Order::where('tenant_id', $tenant->id)->count(),
            'pending_orders' => Order::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
            'today_orders' => $analyticsEnabled
                ? Order::where('tenant_id', $tenant->id)->whereDate('created_at', today())->count()
                : null,
            'total_revenue' => $analyticsEnabled
                ? Order::where('tenant_id', $tenant->id)->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total')
                : null,
            'today_revenue' => $analyticsEnabled
                ? Order::where('tenant_id', $tenant->id)->whereDate('created_at', today())->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total')
                : null,
        ];

        $recentOrders = Order::where('tenant_id', $tenant->id)
            ->with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($o) => $this->transformOrder($o));

        $topProducts = [];
        $chartData = [];
        $ordersChart = [];
        $monthlyRevenue = [];
        $ordersByStatus = [];
        $topProductsRevenue = [];

        if ($analyticsEnabled) {
            // --- Top products by units sold ---
            $topProducts = OrderItem::selectRaw('product_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
                ->whereHas('order', fn($q) => $q->where('tenant_id', $tenant->id)->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']))
                ->with('product:id,name,images')
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->take(5)
                ->get()
                ->map(fn($oi) => [
                    'product_id' => $oi->product_id,
                    'product_name' => $oi->product?->name,
                    'image' => $oi->product?->images[0] ?? null,
                    'total_sold' => $oi->total_sold,
                    'total_revenue' => (float) $oi->total_revenue,
                ]);

            // --- Chart Data: Last 30 days daily revenue ---
            $dates30 = collect();
            for ($i = 29; $i >= 0; $i--) {
                $dates30->push(now()->subDays($i)->format('Y-m-d'));
            }
            $sales30 = Order::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', now()->subDays(30)->startOfDay())
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            foreach ($dates30 as $date) {
                $row = $sales30[$date] ?? null;
                $chartData[] = [
                    'date' => \Carbon\Carbon::parse($date)->format('M d'),
                    'revenue' => (float) ($row?->revenue ?? 0),
                    'orders' => (int) ($row?->orders ?? 0),
                ];
            }

            // --- 7-day orders trend ---
            $dates7 = collect();
            for ($i = 6; $i >= 0; $i--) {
                $dates7->push(now()->subDays($i)->format('Y-m-d'));
            }
            $orders7 = Order::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', now()->subDays(7)->startOfDay())
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            foreach ($dates7 as $date) {
                $ordersChart[] = [
                    'date' => \Carbon\Carbon::parse($date)->format('D'),
                    'orders' => (int) ($orders7[$date] ?? 0),
                ];
            }

            // --- Monthly revenue (last 6 months) ---
            for ($m = 5; $m >= 0; $m--) {
                $month = now()->subMonths($m);
                $rev = Order::where('tenant_id', $tenant->id)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->sum('total');
                $monthlyRevenue[] = [
                    'month' => $month->format('M'),
                    'revenue' => (float) $rev,
                ];
            }

            // --- Orders by status ---
            $statusCounts = Order::where('tenant_id', $tenant->id)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $allStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
            foreach ($allStatuses as $status) {
                $count = (int) ($statusCounts[$status] ?? 0);
                if ($count > 0) {
                    $ordersByStatus[] = [
                        'status' => ucfirst($status),
                        'count' => $count,
                    ];
                }
            }

            // --- Top products by revenue (for pie/bar) ---
            $topProductsRevenue = collect($topProducts)->sortByDesc('total_revenue')->take(5)->values()->toArray();
        }

        return $this->ok([
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'chart_data' => $chartData,
            'orders_chart' => $ordersChart,
            'monthly_revenue' => $monthlyRevenue,
            'orders_by_status' => $ordersByStatus,
            'top_products_revenue' => $topProductsRevenue,
            'analytics_enabled' => $analyticsEnabled,
        ]);
    }

    // =========================================================================
    //  PRODUCTS
    // =========================================================================

    /**
     * GET /api/merchant/products
     * List products with optional search and status filters.
     * Query params: search, status (active|inactive), per_page (default 20)
     */
    public function getProducts(Request $request): JsonResponse
    {
        $tenant = $this->tenant();
        $limit = (int) $this->planFeature->getValue($tenant, 'product_limit', 30);

        $products = Product::with('variants')
            ->where('tenant_id', $tenant->id)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('sort_order')
            ->latest()
            ->paginate($request->per_page ?? 20)
            ->withQueryString();

        return $this->ok([
            'products' => $products,
            'product_limit' => $limit,
            'product_count' => Product::where('tenant_id', $tenant->id)->count(),
            'can_add' => $limit === 0 || Product::where('tenant_id', $tenant->id)->count() < $limit,
        ]);
    }

    /**
     * GET /api/merchant/products/{product}
     * Show a single product with its variants.
     */
    public function showProduct(Product $product): JsonResponse
    {
        if ($product->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        return $this->ok($product->load('variants'));
    }

    /**
     * POST /api/merchant/products
     * Create a new product (multipart/form-data for images).
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $tenant = $this->tenant();
        $limit = (int) $this->planFeature->getValue($tenant, 'product_limit', 30);

        if ($limit > 0 && Product::where('tenant_id', $tenant->id)->count() >= $limit) {
            return $this->err("You have reached your plan limit of {$limit} products.", 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:10240',
            'variants' => 'nullable|array',
            'variants.*.option_name' => 'required_with:variants|string',
            'variants.*.option_value' => 'required_with:variants|string',
            'variants.*.price_override' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imagePaths[] = $img->store("products/{$tenant->id}", 'public');
            }
        }

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'stock_quantity' => $validated['stock_quantity'],
            'sku' => $validated['sku'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'images' => $imagePaths,
        ]);

        foreach ($validated['variants'] ?? [] as $variant) {
            $product->variants()->create($variant);
        }

        return $this->ok($product->load('variants'), 'Product created.', 201);
    }

    /**
     * POST /api/merchant/products/{product}  (with _method=PUT)
     * Update an existing product (multipart/form-data for images).
     * Send existing_images[] with the paths to retain; omitted paths are deleted.
     */
    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $tenant = $this->tenant();

        if ($product->tenant_id !== $tenant->id) {
            return $this->err('Not found.', 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'existing_images' => 'nullable|array',
            'new_images' => 'nullable|array|max:5',
            'new_images.*' => 'image|max:10240',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.option_name' => 'required_with:variants|string',
            'variants.*.option_value' => 'required_with:variants|string',
            'variants.*.price_override' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
        ]);

        $existingImages = $validated['existing_images'] ?? [];
        $newImages = [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $img) {
                $newImages[] = $img->store("products/{$tenant->id}", 'public');
            }
        }

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'stock_quantity' => $validated['stock_quantity'],
            'sku' => $validated['sku'] ?? null,
            'category' => $validated['category'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'images' => array_merge($existingImages, $newImages),
        ]);

        // Sync variants
        $incomingVariantIds = collect($validated['variants'] ?? [])->pluck('id')->filter()->toArray();
        $product->variants()->whereNotIn('id', $incomingVariantIds)->delete();

        foreach ($validated['variants'] ?? [] as $variantData) {
            if (!empty($variantData['id'])) {
                ProductVariant::where('id', $variantData['id'])
                    ->where('product_id', $product->id)
                    ->update($variantData);
            } else {
                $product->variants()->create($variantData);
            }
        }

        return $this->ok($product->fresh('variants'), 'Product updated.');
    }

    /**
     * DELETE /api/merchant/products/{product}
     * Delete a product and its images from storage.
     */
    public function deleteProduct(Product $product): JsonResponse
    {
        if ($product->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        foreach ($product->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $product->delete();

        return $this->ok(message: 'Product deleted.');
    }

    /**
     * PATCH /api/merchant/products/{product}/toggle-active
     * Toggle the is_active flag.
     */
    public function toggleProductActive(Product $product): JsonResponse
    {
        if ($product->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        $product->update(['is_active' => !$product->is_active]);

        return $this->ok(['is_active' => $product->is_active], $product->is_active ? 'Product published.' : 'Product hidden.');
    }

    // =========================================================================
    //  ORDERS
    // =========================================================================

    /**
     * GET /api/merchant/orders
     * List orders. Query params: status, per_page.
     */
    public function getOrders(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $orders = Order::where('tenant_id', $tenant->id)
            ->with('customer')
            ->when($request->status && $request->status !== 'all', fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20)
            ->withQueryString();

        $orders->getCollection()->transform(fn($o) => $this->transformOrder($o));

        return $this->ok($orders);
    }

    /**
     * GET /api/merchant/orders/{order}
     * Show order detail with customer, items, product, variant.
     */
    public function showOrder(Order $order): JsonResponse
    {
        if ($order->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        return $this->ok($this->transformOrder($order->load(['customer', 'items.product', 'items.variant'])));
    }

    /**
     * PATCH /api/merchant/orders/{order}
     * Update order status and optional courier / tracking info.
     * Body: { status, courier_name?, tracking_number? }
     */
    public function updateOrder(Request $request, Order $order): JsonResponse
    {
        if ($order->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'courier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $oldStatus = $order->status;
        $order->update($validated);

        // WhatsApp notifications
        $waService = new WhatsAppMessageService();

        if ($validated['status'] === 'confirmed' && $oldStatus !== 'confirmed') {
            try {
                $waService->sendMerchantOrderConfirmedAlert($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("WhatsApp sendMerchantOrderConfirmedAlert failed: " . $e->getMessage());
            }

            if ($this->planFeature->isEnabled($order->tenant, 'customer_whatsapp_notifications')) {
                try {
                    $waService->sendOrderConfirmedNotification($order);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("WhatsApp sendOrderConfirmedNotification failed: " . $e->getMessage());
                }
            }
        }

        if ($validated['status'] === 'shipped' && $oldStatus !== 'shipped') {
            $customer = $order->customer;
            if ($customer?->phone) {
                $trackingUrl = url("/store/{$order->tenant->store_slug}/track");
                $message = "🚚 Your order *{$order->order_number}* has been shipped.\n\n";
                if ($order->courier_name && $order->tracking_number) {
                    $message .= "Courier: {$order->courier_name}\nTracking #: {$order->tracking_number}\n\n";
                }
                $message .= "Track here: {$trackingUrl}";
                try {
                    $waService->send($customer->phone, $message, ['tenant_id' => $order->tenant_id]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("WhatsApp order shipped notification failed: " . $e->getMessage());
                }
            }
        }

        return $this->ok($this->transformOrder($order->fresh(['customer', 'items.product', 'items.variant'])), 'Order updated.');
    }

    // =========================================================================
    //  COUPONS
    // =========================================================================

    /**
     * GET /api/merchant/coupons
     * List all coupons for this tenant.
     */
    public function getCoupons(): JsonResponse
    {
        $coupons = Coupon::where('tenant_id', $this->tenant()->id)->latest()->get();
        return $this->ok($coupons);
    }

    /**
     * POST /api/merchant/coupons
     * Create a coupon.
     * Body: { code, type (percentage|fixed), value, usage_limit?, expires_at?, is_active }
     */
    public function storeCoupon(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,NULL,id,tenant_id,' . $tenant->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['code'] = strtoupper($validated['code']);

        $coupon = Coupon::create($validated);

        return $this->ok($coupon, 'Coupon created.', 201);
    }

    /**
     * PUT /api/merchant/coupons/{coupon}
     * Update a coupon.
     */
    public function updateCoupon(Request $request, Coupon $coupon): JsonResponse
    {
        $tenant = $this->tenant();

        if ($coupon->tenant_id !== $tenant->id) {
            return $this->err('Not found.', 404);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id . ',id,tenant_id,' . $tenant->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $coupon->update($validated);

        return $this->ok($coupon->fresh(), 'Coupon updated.');
    }

    /**
     * DELETE /api/merchant/coupons/{coupon}
     * Delete a coupon.
     */
    public function deleteCoupon(Coupon $coupon): JsonResponse
    {
        if ($coupon->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        $coupon->delete();

        return $this->ok(message: 'Coupon deleted.');
    }

    // =========================================================================
    //  THEME
    // =========================================================================

    /**
     * GET /api/merchant/theme
     * Return available themes, active slug, and current settings.
     */
    public function getTheme(): JsonResponse
    {
        $tenant = $this->tenant();
        $theme = ThemeRegistry::forTenant($tenant);
        $products = Product::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']);

        $pendingThemeUnlock = \App\Models\OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'theme_change')
            ->where('status', 'pending')
            ->exists();

        return $this->ok([
            'themes' => ThemeRegistry::themes(),
            'active_slug' => $theme['slug'],
            'settings' => $theme['settings'],
            'products' => $products,
            'can_change' => ThemeRegistry::canChangeTheme($tenant, $this->planFeature),
            'theme_change_fee' => $this->planFeature->getValue($tenant, 'theme_change_fee', 500),
            'theme_unlock_pending' => $pendingThemeUnlock,
            'payment_accounts' => \App\Models\GlobalSetting::getVal('payment_accounts', []),
        ]);
    }

    /**
     * POST /api/merchant/theme  (use _method=PUT for multipart)
     * Update theme settings (slug, colors, fonts, logo, banner, SEO).
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $request->validate([
            'theme_slug' => 'required|string|in:' . implode(',', array_keys(ThemeRegistry::themes())),
            'accent_color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'typography' => 'nullable|string|max:100',
            'heading_font' => 'nullable|string|max:100',
            'favicon' => 'nullable|image|max:1024',
            'logo' => 'nullable|image|max:2048',
            'banner_image' => 'nullable|image|max:2048',
            'banner_text' => 'nullable|string|max:150',
            'store_description' => 'nullable|string|max:300',
            'bg_color' => 'nullable|string',
            'text_color' => 'nullable|string',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_image' => 'nullable|image|max:2048',
        ]);

        $currentSlug = $tenant->themeSettings?->theme_slug;
        $isChangingTheme = $request->theme_slug !== $currentSlug && $currentSlug !== null;

        $hasApprovedUnlock = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'theme_change')
            ->where('status', 'approved')
            ->where('applied', false)
            ->exists();

        if ($isChangingTheme && !ThemeRegistry::canChangeTheme($tenant, $this->planFeature, $request->theme_slug)) {
            return $this->err('Switching to this premium theme requires an upgrade or a paid one-time unlock.', 403);
        }

        if ($isChangingTheme && ThemeRegistry::isPremium($request->theme_slug) && !$this->planFeature->isEnabled($tenant, 'theme_change_allowed') && $hasApprovedUnlock) {
            OneTimeUnlockPayment::where('tenant_id', $tenant->id)->where('unlock_type', 'theme_change')->where('status', 'approved')->where('applied', false)->update(['applied' => true]);
        }

        $customSettings = $tenant->themeSettings?->custom_settings ?? [];
        foreach (['banner_text', 'store_description', 'bg_color', 'text_color', 'seo_title', 'seo_description'] as $key) {
            if ($request->filled($key)) {
                $customSettings[$key] = $request->input($key);
            }
        }

        $settingsData = [
            'theme_slug' => $request->theme_slug,
            'accent_color' => $request->accent_color,
            'secondary_color' => $request->secondary_color,
            'typography' => $request->typography ?? 'Inter',
            'heading_font' => $request->heading_font,
            'custom_settings' => $customSettings,
            'theme_last_changed_at' => now(),
        ];

        foreach (['favicon' => 'favicons', 'logo' => 'logos', 'banner_image' => 'banners', 'seo_image' => 'seo'] as $field => $folder) {
            if ($request->hasFile($field)) {
                $settingsData["{$field}_path"] = $request->file($field)->store("{$folder}/{$tenant->id}", 'public');
            }
        }

        TenantThemeSettings::updateOrCreate(['tenant_id' => $tenant->id], $settingsData);

        return $this->ok(message: 'Theme settings saved.');
    }

    // =========================================================================
    //  SECTIONS
    // =========================================================================

    /**
     * GET /api/merchant/sections
     * List storefront sections in display order.
     */
    public function getSections(): JsonResponse
    {
        $tenant = $this->tenant();
        $sections = StorefrontSection::where('tenant_id', $tenant->id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        $products = Product::where('tenant_id', $tenant->id)->select('id', 'name', 'price', 'images')->orderBy('name')->get();

        return $this->ok(['sections' => $sections, 'products' => $products]);
    }

    /**
     * POST /api/merchant/sections
     * Create a new storefront section.
     */
    public function storeSection(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'type' => ['required', Rule::in(StorefrontSection::TYPES)],
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'image' => 'nullable|image|max:10240',
        ]);

        $section = StorefrontSection::create([
            'tenant_id' => $tenant->id,
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'config' => $this->buildSectionConfig($request, $validated['type']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (StorefrontSection::where('tenant_id', $tenant->id)->max('sort_order') ?? -1) + 1,
        ]);

        if ($validated['type'] === 'featured_products') {
            $this->syncSectionProducts($section, $request->input('product_ids', []), $tenant);
        }

        return $this->ok($section->load('products'), 'Section created.', 201);
    }

    /**
     * PUT /api/merchant/sections/{section}
     * Update a storefront section.
     */
    public function updateSection(Request $request, StorefrontSection $section): JsonResponse
    {
        $tenant = $this->tenant();
        if ($section->tenant_id !== $tenant->id) {
            return $this->err('Not found.', 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'image' => 'nullable|image|max:10240',
        ]);

        $section->update([
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'config' => $this->buildSectionConfig($request, $section->type, $section),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($section->type === 'featured_products') {
            $this->syncSectionProducts($section, $request->input('product_ids', []), $tenant);
        }

        return $this->ok($section->fresh()->load('products'), 'Section updated.');
    }

    /**
     * DELETE /api/merchant/sections/{section}
     * Remove a section.
     */
    public function deleteSection(StorefrontSection $section): JsonResponse
    {
        if ($section->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        $section->delete();

        return $this->ok(message: 'Section removed.');
    }

    /**
     * PATCH /api/merchant/sections/{section}/toggle-active
     * Toggle section visibility.
     */
    public function toggleSectionActive(StorefrontSection $section): JsonResponse
    {
        if ($section->tenant_id !== $this->tenant()->id) {
            return $this->err('Not found.', 404);
        }

        $section->update(['is_active' => !$section->is_active]);

        return $this->ok(['is_active' => $section->is_active]);
    }

    /**
     * POST /api/merchant/sections/reorder
     * Reorder sections via drag-and-drop.
     * Body: { ids: [3, 1, 2] }
     */
    public function reorderSections(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|distinct|exists:storefront_sections,id',
        ]);

        DB::transaction(function () use ($validated, $tenant) {
            foreach ($validated['ids'] as $index => $id) {
                StorefrontSection::where('id', $id)->where('tenant_id', $tenant->id)->update(['sort_order' => $index]);
            }
        });

        return $this->ok(message: 'Sections reordered.');
    }

    private function buildSectionConfig(Request $request, string $type, ?StorefrontSection $existing = null): array
    {
        $tenant = $this->tenant();
        $existingConfig = $existing?->config ?? [];

        return match ($type) {
            'promo_banner' => [
                'cta_text' => $request->input('cta_text'),
                'cta_link' => $request->input('cta_link') ?: '#products',
                'layout' => $request->input('layout', 'image_right'),
                'image_path' => $request->hasFile('image')
                    ? $request->file('image')->store("banners/{$tenant->id}", 'public')
                    : ($request->input('existing_image') ?: ($existingConfig['image_path'] ?? null)),
            ],
            'whatsapp_cta' => ['message' => $request->input('message')],
            'new_arrivals' => ['limit' => max(1, (int) $request->input('limit', 8))],
            'featured_products' => ['layout' => $request->input('layout', 'grid'), 'columns' => max(2, min(6, (int) $request->input('columns', 4)))],
            'testimonials' => [
                'items' => collect($request->input('items', []))->filter(fn($i) => filled($i['text'] ?? null))->map(fn($i) => [
                    'name' => $i['name'] ?? 'Happy Customer',
                    'text' => $i['text'],
                    'rating' => isset($i['rating']) && $i['rating'] !== '' ? max(1, min(5, (int) $i['rating'])) : null,
                    'location' => $i['location'] ?? null,
                ])->values()->all(),
            ],
            'faq' => [
                'items' => collect($request->input('items', []))->filter(fn($i) => filled($i['question'] ?? null))->map(fn($i) => [
                    'question' => $i['question'],
                    'answer' => $i['answer'] ?? '',
                ])->values()->all(),
            ],
            'category_showcase' => [
                'items' => collect($request->input('items', []))->filter(fn($i) => filled($i['label'] ?? null))->values()->map(function ($item, $i) use ($request, $tenant, $existingConfig) {
                        $file = $request->file("items.{$i}.image");
                        return [
                        'label' => $item['label'],
                        'category' => $item['category'] ?? '',
                        'image_path' => $file ? $file->store("categories/{$tenant->id}", 'public') : ($item['existing_image'] ?? $existingConfig['items'][$i]['image_path'] ?? null),
                        ];
                    })->all(),
            ],
            default => [],
        };
    }

    private function syncSectionProducts(StorefrontSection $section, array $productIds, $tenant): void
    {
        $ownedIds = Product::where('tenant_id', $tenant->id)->whereIn('id', $productIds)->pluck('id');
        $pivotData = collect($productIds)->intersect($ownedIds)->values()->mapWithKeys(fn($id, $idx) => [$id => ['sort_order' => $idx]])->all();
        $section->products()->sync($pivotData);
    }

    // =========================================================================
    //  SETTINGS
    // =========================================================================

    /**
     * GET /api/merchant/settings
     * Return store settings and unlock status.
     */
    public function getSettings(): JsonResponse
    {
        $tenant = $this->tenant();

        $canChangeStoreInfo = $this->planFeature->isEnabled($tenant, 'store_info_change_allowed');
        $storeInfoFee = $this->planFeature->getValue($tenant, 'store_info_change_fee', 1000);
        $themeChangeFee = $this->planFeature->getValue($tenant, 'theme_change_fee', 500);

        $pendingUnlocks = OneTimeUnlockPayment::where('tenant_id', $tenant->id)->where('status', 'pending')->pluck('unlock_type')->toArray();
        $approvedUnlocks = OneTimeUnlockPayment::where('tenant_id', $tenant->id)->where('status', 'approved')->where('applied', false)->pluck('unlock_type')->toArray();

        return $this->ok([
            'tenant' => [
                'name' => $tenant->name,
                'store_slug' => $tenant->store_slug,
                'whatsapp_number' => $tenant->whatsapp_number,
                'delivery_charge' => $tenant->delivery_charge,
                'bank_name' => $tenant->bank_name,
                'account_title' => $tenant->account_title,
                'account_number' => $tenant->account_number,
            ],
            'can_change_store_info' => $canChangeStoreInfo || in_array('store_info_change', $approvedUnlocks),
            'store_info_change_fee' => $storeInfoFee,
            'theme_change_fee' => $themeChangeFee,
            'pending_unlocks' => $pendingUnlocks,
            'approved_unlocks' => $approvedUnlocks,
            'payment_accounts' => \App\Models\GlobalSetting::getVal('payment_accounts', []),
        ]);
    }

    /**
     * PUT /api/merchant/settings
     * Update store settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $tenant = $this->tenant();
        $canChangeStoreInfo = $this->planFeature->isEnabled($tenant, 'store_info_change_allowed');

        $hasApprovedUnlock = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'store_info_change')
            ->where('status', 'approved')
            ->where('applied', false)
            ->exists();

        $allowStoreInfoChange = $canChangeStoreInfo || $hasApprovedUnlock;

        $rules = [
            'whatsapp_number' => 'nullable|string|max:20',
            'delivery_charge' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'account_title' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
        ];

        if ($allowStoreInfoChange) {
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['store_slug'] = 'sometimes|required|string|max:255|unique:tenants,store_slug,' . $tenant->id;
        }

        $validated = $request->validate($rules);

        if (isset($validated['store_slug'])) {
            $validated['store_slug'] = Str::slug($validated['store_slug']);
        }

        $tenant->update($validated);

        if ($hasApprovedUnlock) {
            OneTimeUnlockPayment::where('tenant_id', $tenant->id)->where('unlock_type', 'store_info_change')->where('status', 'approved')->where('applied', false)->update(['applied' => true]);
        }

        return $this->ok(message: 'Settings updated.');
    }

    /**
     * POST /api/merchant/settings/request-unlock
     * Submit payment proof for a one-time unlock (theme_change | store_info_change).
     * Body: { unlock_type, payment_proof (file) }
     */
    public function requestUnlock(Request $request): JsonResponse
    {
        $tenant = $this->tenant();
        $unlockType = $request->input('unlock_type');

        if (!in_array($unlockType, ['theme_change', 'store_info_change'])) {
            return $this->err('Invalid unlock type.', 422);
        }

        $alreadyPending = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', $unlockType)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return $this->err('You already have a pending unlock request for this feature.', 409);
        }

        $fee = $unlockType === 'theme_change'
            ? $this->planFeature->getValue($tenant, 'theme_change_fee', 500)
            : $this->planFeature->getValue($tenant, 'store_info_change_fee', 1000);

        if ($fee == 0) {
            OneTimeUnlockPayment::create([
                'tenant_id' => $tenant->id,
                'unlock_type' => $unlockType,
                'amount' => 0,
                'status' => 'approved',
                'payment_proof_path' => null,
            ]);
            return $this->ok(message: 'Request approved instantly.', status: 201);
        }

        $request->validate(['payment_proof' => 'required|image|max:10240']);

        $proofPath = $request->file('payment_proof')->store("unlock_proofs/{$tenant->id}", 'public');

        OneTimeUnlockPayment::create([
            'tenant_id' => $tenant->id,
            'unlock_type' => $unlockType,
            'amount' => $fee,
            'status' => 'pending',
            'payment_proof_path' => $proofPath,
        ]);

        return $this->ok(message: 'Payment proof submitted. Admin will approve shortly.', status: 201);
    }

    // =========================================================================
    //  DOMAIN
    // =========================================================================

    /**
     * GET /api/merchant/domain
     * Return domain status and active requests.
     */
    public function getDomain(): JsonResponse
    {
        $tenant = $this->tenant();
        $subReq = $tenant->domainRequests()->where('type', 'subdomain')->whereNotIn('status', ['active', 'rejected'])->latest()->first();
        $customReq = $tenant->domainRequests()->where('type', 'custom_domain')->whereNotIn('status', ['active', 'rejected'])->latest()->first();

        return $this->ok([
            'subdomain_allowed' => $this->planFeature->isEnabled($tenant, 'subdomain_allowed'),
            'custom_domain_allowed' => $this->planFeature->isEnabled($tenant, 'custom_domain_allowed'),
            'current_subdomain' => $tenant->subdomain,
            'subdomain_status' => $tenant->subdomain_status,
            'current_custom_domain' => $tenant->custom_domain,
            'subdomain_request' => $subReq?->requested_value,
            'custom_domain_request' => $customReq?->requested_value,
            'custom_domain_request_id' => $customReq?->id,
            'custom_domain_status' => $customReq?->status,
            'custom_domain_quote' => $customReq?->quoted_price,
            'merchant_owns_domain' => $customReq?->merchant_owns_domain,
            'admin_note' => $customReq?->admin_note,
            'connection_fee' => (int) \App\Models\GlobalSetting::getVal('custom_domain_connection_fee', 400),
            'payment_accounts' => \App\Models\GlobalSetting::getVal('payment_accounts', []),
        ]);
    }


    /**
     * POST /api/merchant/domain/subdomain
     * Request a subdomain.
     * Body: { requested_value }
     */
    public function applySubdomain(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        if (!$this->planFeature->isEnabled($tenant, 'subdomain_allowed')) {
            return $this->err('Your current plan does not allow subdomains.', 403);
        }

        $request->validate(['requested_value' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9\-]+$/']);

        $value = strtolower(trim($request->requested_value));

        if (\App\Models\Tenant::where('subdomain', $value)->where('id', '!=', $tenant->id)->exists()) {
            return $this->err('This subdomain is already taken.', 409);
        }

        $tenant->domainRequests()->where('type', 'subdomain')->whereNotIn('status', ['active', 'rejected'])->update(['status' => 'rejected']);

        DomainRequest::create(['tenant_id' => $tenant->id, 'type' => 'subdomain', 'requested_value' => $value, 'status' => 'pending']);
        $tenant->update(['subdomain_status' => 'pending']);

        try {
            (new WhatsAppMessageService())->notifyAdminSubdomainRequest($tenant, $value);
        } catch (\Exception $e) {
        }

        return $this->ok(message: 'Subdomain request submitted.', status: 201);
    }

    /**
     * POST /api/merchant/domain/custom
     * Request a custom domain.
     * Body: { requested_value, merchant_owns_domain (bool) }
     */
    public function applyCustomDomain(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        if (!$this->planFeature->isEnabled($tenant, 'custom_domain_allowed')) {
            return $this->err('Your current plan does not allow custom domains.', 403);
        }

        $request->validate([
            'requested_value' => 'required|string|regex:/^[a-zA-Z0-9\-\.]+$/',
            'merchant_owns_domain' => 'nullable|boolean',
        ]);

        $value = strtolower(trim($request->requested_value));
        $ownsDomain = $request->boolean('merchant_owns_domain');

        $tenant->domainRequests()->where('type', 'custom_domain')->whereNotIn('status', ['active', 'rejected'])->update(['status' => 'rejected']);

        if ($ownsDomain) {
            $fee = \App\Models\GlobalSetting::getVal('custom_domain_connection_fee', 0);
            $dr = DomainRequest::create(['tenant_id' => $tenant->id, 'type' => 'custom_domain', 'merchant_owns_domain' => true, 'requested_value' => $value, 'status' => 'quote_sent', 'quoted_price' => $fee]);
        } else {
            $dr = DomainRequest::create(['tenant_id' => $tenant->id, 'type' => 'custom_domain', 'merchant_owns_domain' => false, 'requested_value' => $value, 'status' => 'pending']);
        }

        try {
            (new WhatsAppMessageService())->notifyAdminCustomDomainRequest($tenant, $value);
        } catch (\Exception $e) {
        }

        return $this->ok($dr, 'Custom domain request submitted.', 201);
    }

    /**
     * POST /api/merchant/domain/payment
     * Submit payment proof for a custom domain request.
     * Body: { domain_request_id, proof_image (file) }
     */
    public function submitDomainPayment(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $request->validate([
            'domain_request_id' => 'required|exists:domain_requests,id',
            'proof_image' => 'required|image|max:5120',
        ]);

        $dr = DomainRequest::where('tenant_id', $tenant->id)->findOrFail($request->domain_request_id);

        if ($dr->status !== 'quote_sent') {
            return $this->err('Invalid request state for payment.', 409);
        }

        $proofPath = $request->file('proof_image')->store("domain_payments/{$tenant->id}", 'public');
        $dr->update(['status' => 'payment_submitted', 'payment_proof_path' => $proofPath]);

        try {
            (new WhatsAppMessageService())->notifyAdminDomainPaymentSubmitted($dr);
        } catch (\Exception $e) {
        }

        return $this->ok(message: 'Payment proof submitted.');
    }

    /**
     * GET /api/merchant/customers
     */
    public function getCustomers(): JsonResponse
    {
        $tenant = $this->tenant();
        if (!$this->planFeature->isEnabled($tenant, 'customer_crm')) {
            return $this->err('Customer CRM feature is not available on your current plan. Please upgrade.', 403);
        }

        $customers = \App\Models\Customer::where('tenant_id', $tenant->id)
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($customers);
    }

    /**
     * GET /api/merchant/customers/{customer}
     * Return customer detail.
     */
    public function showCustomer(\App\Models\Customer $customer): JsonResponse
    {
        $tenant = $this->tenant();
        if (!$this->planFeature->isEnabled($tenant, 'customer_crm')) {
            return $this->err('Customer CRM feature is not available on your current plan. Please upgrade.', 403);
        }

        if ($customer->tenant_id !== $tenant->id) {
            return $this->err('Not found.', 404);
        }
        $customer->load('orders');
        return $this->ok($customer);
    }

    /**
     * GET /api/merchant/export/products
     */
    public function exportProducts(): JsonResponse
    {
        $tenant = $this->tenant();
        if (!$this->planFeature->isEnabled($tenant, 'bulk_import_export')) {
            return $this->err('Bulk Import/Export feature is not available on your current plan. Please upgrade.', 403);
        }

        // Return dummy download link for now
        return $this->ok(['download_url' => url('/storage/exports/dummy.csv')], 'Products exported successfully.');
    }

    /**
     * POST /api/merchant/import/products
     */
    public function importProducts(Request $request): JsonResponse
    {
        $tenant = $this->tenant();
        if (!$this->planFeature->isEnabled($tenant, 'bulk_import_export')) {
            return $this->err('Bulk Import/Export feature is not available on your current plan. Please upgrade.', 403);
        }

        return $this->ok(message: 'Products imported successfully.');
    }

    /**
     * GET /api/merchant/domain/check-availability
     * Check if a subdomain is available.
     */
    public function checkSubdomainAvailability(Request $request): JsonResponse
    {
        $request->validate(['subdomain' => 'required|string|max:50']);
        $taken = \App\Models\Tenant::where('subdomain', strtolower($request->subdomain))->exists();
        return $this->ok(['available' => !$taken]);
    }

    // =========================================================================
    //  STAFF
    // =========================================================================

    /**
     * GET /api/merchant/staff
     * List all staff for this tenant (owner only sees all).
     */
    public function getStaff(): JsonResponse
    {
        $tenant = $this->tenant();
        $staff = User::where('tenant_id', $tenant->id)->where('is_admin', false)->get(['id', 'name', 'email', 'phone', 'tenant_role', 'created_at']);

        return $this->ok($staff);
    }

    /**
     * POST /api/merchant/staff
     * Add a staff member (owner only).
     * Body: { name, email, phone?, password, tenant_role (manager|staff) }
     */
    public function storeStaff(Request $request): JsonResponse
    {
        if (auth()->user()->tenant_role !== 'owner') {
            return $this->err('Only the store owner can add staff members.', 403);
        }

        $tenant = $this->tenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', Password::defaults()],
            'tenant_role' => 'required|in:manager,staff',
        ]);

        $limit = (int) $this->planFeature->getValue($tenant, 'staff_logins_limit', 1);
        // Exclude the owner from the count, or consider all users. Usually, "staff logins limit" means total users or additional users.
        // Let's count all users for the tenant.
        if ($limit > 0 && User::where('tenant_id', $tenant->id)->count() >= $limit) {
            return $this->err("You have reached your plan limit of {$limit} staff members. Please upgrade your plan.", 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'tenant_id' => $tenant->id,
            'tenant_role' => $validated['tenant_role'],
        ]);

        return $this->ok($user->only(['id', 'name', 'email', 'phone', 'tenant_role']), 'Staff member added.', 201);
    }

    /**
     * DELETE /api/merchant/staff/{user}
     * Remove a staff member (owner only).
     */
    public function deleteStaff(User $user): JsonResponse
    {
        if (auth()->user()->tenant_role !== 'owner') {
            return $this->err('Only the store owner can remove staff members.', 403);
        }

        $tenant = $this->tenant();

        if ($user->tenant_id !== $tenant->id || $user->id === auth()->id()) {
            return $this->err('Cannot remove this user.', 403);
        }

        $user->delete();

        return $this->ok(message: 'Staff member removed.');
    }

    // =========================================================================
    //  BILLING
    // =========================================================================

    /**
     * GET /api/merchant/billing
     * Return subscription plan, WhatsApp usage, and order stats.
     */
    public function getBilling(): JsonResponse
    {
        $tenant = $this->tenant();
        $plan = $tenant->subscriptionPlan;

        // WhatsApp overages
        $usage = TenantMessageUsage::where('tenant_id', $tenant->id)->orderByDesc('created_at')->first();
        $quotaLimit = $this->planFeature->getValue($tenant, 'message_quota_monthly') ?? 100;
        $overagePrice = $this->planFeature->getValue($tenant, 'overage_message_price') ?? 1.50;
        $messagesSent = $usage?->messages_sent_count ?? 0;
        $overageCount = $usage?->overage_count ?? 0;
        $waOverageCost = $overageCount * $overagePrice;
        $usagePct = $quotaLimit > 0 ? min(100, round(($messagesSent / $quotaLimit) * 100)) : 0;

        // Order overages — counted from the tenant's billing cycle start, not calendar month
        $orderLimit = (int) $this->planFeature->getValue($tenant, 'order_limit_monthly', 50);
        $extraOrderFee = (float) $this->planFeature->getValue($tenant, 'extra_order_fee', 10.00);

        // Use billing_cycle_start if set; otherwise fall back to subscription start or 30 days ago
        $cycleStart = $tenant->billing_cycle_start
            ?? ($tenant->subscription_expires_at ? $tenant->subscription_expires_at->subDays(30) : now()->subDays(30));

        $ordersThisCycle = Order::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $cycleStart)
            ->count();

        $orderOverageCount = ($orderLimit > 0 && $ordersThisCycle > $orderLimit)
            ? $ordersThisCycle - $orderLimit
            : 0;
        $orderOverageCost = $orderOverageCount * $extraOrderFee;

        $totalOverageCost = $waOverageCost + $orderOverageCost;

        $allPlans = SubscriptionPlan::with('features')->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn($plan) => [
                'name' => $plan->name,
                'price_monthly' => $plan->price_monthly,
                'features' => $plan->features->map(fn($f) => [
                    'key' => $f->feature_key,
                    'label' => $f->feature_label ?? $f->feature_key,
                    'value' => $f->feature_value,
                ])->values(),
            ]);

        return $this->ok([
            'plan' => $plan,
            'all_plans' => $allPlans,
            'billing' => [
                'quota_limit' => $quotaLimit,
                'messages_sent' => $messagesSent,
                'overage_count' => $overageCount, // WA overage count
                'overage_price' => $overagePrice,
                'wa_overage_cost' => $waOverageCost,
                'usage_pct' => $usagePct,

                'order_limit' => $orderLimit,
                'orders_this_cycle' => $ordersThisCycle,
                'order_overage_count' => $orderOverageCount,
                'extra_order_fee' => $extraOrderFee,
                'order_overage_cost' => $orderOverageCost,
                'billing_cycle_start' => $cycleStart,

                'total_overage_cost' => $totalOverageCost,

                'period_start' => $usage?->billing_period_start,
                'period_end' => $usage?->billing_period_end,
                'plan_price_monthly' => $plan?->price_monthly ?? 0,
                'renewal_total' => ($plan?->price_monthly ?? 0) + $totalOverageCost,
                'subscription_status' => $tenant->status,
                'subscription_expires_at' => $tenant->subscription_expires_at?->toDateString(),
            ],
            'payment_accounts' => \App\Models\GlobalSetting::getVal('payment_accounts', []),
            'stats' => [
                'total_orders' => Order::where('tenant_id', $tenant->id)->count(),
                'pending_orders' => Order::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
                'total_revenue' => Order::where('tenant_id', $tenant->id)->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total'),
            ],
        ]);
    }

    /**
     * POST /api/merchant/billing/payment
     * Submit a bank transfer payment proof for plan upgrade/renewal
     */
    public function submitPlanPayment(Request $request): JsonResponse
    {
        $tenant = auth('sanctum')->user();
        if (!$tenant)
            return $this->err('Unauthorized', 401);

        $validated = $request->validate([
            'payment_proof' => 'required|image|max:10240',
            'plan_id' => 'nullable|integer|exists:subscription_plans,id',
        ]);

        $plan = $validated['plan_id']
            ? \App\Models\SubscriptionPlan::findOrFail($validated['plan_id'])
            : $tenant->subscriptionPlan;

        $proofPath = $request->file('payment_proof')->store("payments/{$tenant->id}", 'public');

        // WA Overages
        $overagePrice = $this->planFeature->getValue($tenant, 'overage_message_price') ?? 1.50;
        $usage = $tenant->messageUsages()->orderBy('created_at', 'desc')->first();
        $waOverageCost = ($usage?->overage_count ?? 0) * $overagePrice;

        // Order Overages — snapshot from billing cycle start
        $orderLimit = (int) $this->planFeature->getValue($tenant, 'order_limit_monthly', 50);
        $extraOrderFee = (float) $this->planFeature->getValue($tenant, 'extra_order_fee', 10.00);
        $cycleStart = $tenant->billing_cycle_start
            ?? ($tenant->subscription_expires_at ? $tenant->subscription_expires_at->subDays(30) : now()->subDays(30));

        // Lock in the order count RIGHT NOW so it can't shift after submission
        $ordersSnapshot = Order::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $cycleStart)
            ->count();
        $orderOverageCount = ($orderLimit > 0 && $ordersSnapshot > $orderLimit)
            ? $ordersSnapshot - $orderLimit
            : 0;
        $orderOverageCost = $orderOverageCount * $extraOrderFee;

        $totalOverageCost = $waOverageCost + $orderOverageCost;
        $totalDue = ($plan?->price_monthly ?? 0) + $totalOverageCost;

        \App\Models\SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => $totalDue,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'gateway_reference' => 'BT-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'billing_period_start' => $cycleStart,
            'billing_period_end' => now()->addDays(30),
            'proof_image_path' => $proofPath,
            'orders_count_snapshot' => $ordersSnapshot,
            'order_overage_count' => $orderOverageCount,
            'order_limit_snapshot' => $orderLimit,
        ]);

        try {
            $payment = \App\Models\SubscriptionPayment::where('tenant_id', $tenant->id)->latest()->first();
            if ($payment) {
                (new \App\Services\WhatsAppMessageService())->notifyAdminNewPayment($payment->load(['tenant', 'plan']));
            }
        } catch (\Throwable $e) {
        }

        return $this->ok(['message' => 'Payment proof submitted. Waiting for admin approval.']);
    }

    // =========================================================================
    //  PUBLIC CONTENT (no auth)
    // =========================================================================

    /**
     * GET /api/merchant/public/announcements
     * List published announcements (public, no login required).
     */
    public function publicAnnouncements(Request $request): JsonResponse
    {
        $items = Announcement::with('author:id,name')
            ->where('is_published', true)
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * GET /api/merchant/public/news
     * List published news items (public, no login required).
     */
    public function publicNews(Request $request): JsonResponse
    {
        $items = NewsItem::with('author:id,name')
            ->where('is_published', true)
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * GET /api/merchant/public/events
     * List published upcoming events (public, no login required).
     */
    public function publicEvents(Request $request): JsonResponse
    {
        $items = Event::with('author:id,name')
            ->where('is_published', true)
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * GET /api/merchant/public/community-posts
     * List merchant community posts (public, no login required).
     */
    public function publicCommunityPosts(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user() ?? $request->user();
        $tenantId = $user?->tenant_id;
        $ip = $request->ip();

        $posts = \App\Models\MerchantCommunityPost::with(['tenant:id,name,store_slug', 'user:id,name'])
            ->withCount('likes')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        // Add 'user_has_liked' flag for each post
        $postIds = $posts->pluck('id');
        $likes = collect();

        if ($tenantId) {
            $likes = \App\Models\MerchantPostLike::whereIn('merchant_community_post_id', $postIds)
                ->where('tenant_id', $tenantId)
                ->pluck('merchant_community_post_id');
        } else {
            $likes = \App\Models\MerchantPostLike::whereIn('merchant_community_post_id', $postIds)
                ->where('ip_address', $ip)
                ->pluck('merchant_community_post_id');
        }

        $posts->getCollection()->transform(function ($post) use ($likes) {
            $post->user_has_liked = $likes->contains($post->id);
            return $post;
        });

        return response()->json(['success' => true, 'data' => $posts]);
    }

    /**
     * POST /api/merchant/community-posts
     * Create a new merchant community post.
     */
    public function storeCommunityPost(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return $this->error('Unauthorized', 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'nullable|image|max:5120',
            'video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/webm|max:51200',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('community/merchant_posts/images', 'public');
        }

        if ($request->hasFile('video')) {
            $validated['video_path'] = $request->file('video')->store('community/merchant_posts/videos', 'public');
        }

        $validated['tenant_id'] = $user->tenant_id;
        $validated['user_id'] = $user->id;
        unset($validated['image']);
        unset($validated['video']);

        $post = \App\Models\MerchantCommunityPost::create($validated);

        return $this->ok(['post' => $post], 'Post created successfully');
    }

    /**
     * POST /api/merchant/community-posts/{id}/like
     * Toggle like on a community post.
     */
    public function likeCommunityPost(Request $request, $id): JsonResponse
    {
        $user = auth('sanctum')->user() ?? $request->user();
        $tenantId = $user?->tenant_id;
        $ip = $request->ip();

        $post = \App\Models\MerchantCommunityPost::findOrFail($id);

        $existingLike = clone \App\Models\MerchantPostLike::where('merchant_community_post_id', $id);

        if ($tenantId) {
            $existingLike = $existingLike->where('tenant_id', $tenantId)->first();
        } else {
            $existingLike = $existingLike->where('ip_address', $ip)->first();
        }

        if ($existingLike) {
            $existingLike->delete();
            $post->decrement('likes_count');
            $liked = false;
        } else {
            \App\Models\MerchantPostLike::create([
                'merchant_community_post_id' => $id,
                'tenant_id' => $tenantId,
                'ip_address' => $tenantId ? null : $ip,
            ]);
            $post->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'likes_count' => $post->likes_count,
            'liked' => $liked,
        ]);
    }

    /**
     * GET /api/merchant/public/discover-products
     * Top-selling products from all active merchants.
     */
    public function discoverProducts(Request $request): JsonResponse
    {
        // Get demo store slugs referenced in themes
        $demoStoreSlugs = \App\Models\Theme::pluck('demo_url')
            ->filter()
            ->map(function ($url) {
                return basename(parse_url($url, PHP_URL_PATH));
            })
            ->filter()
            ->toArray();

        // Top 20 selling products from active & trial tenants (excluding demo stores added in themes)
        $products = \App\Models\Product::with('tenant:id,name,store_slug,status')
            ->where('is_active', true)
            ->whereHas('tenant', function ($q) use ($demoStoreSlugs) {
                $q->whereIn('status', ['active', 'trial']);
                if (!empty($demoStoreSlugs)) {
                    $q->whereNotIn('store_slug', $demoStoreSlugs);
                }
            })
            ->withCount([
                'orderItems as total_sold' => function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw('sum(quantity)'));
                }
            ])
            ->orderByDesc('total_sold')
            ->take(20)
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->main_image,
                    'store_name' => $product->tenant?->name ?? 'Store',
                    'store_slug' => $product->tenant?->store_slug ?? '',
                    'total_sold' => $product->total_sold ?? 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $products]);
    }

    // =========================================================================
    //  WHATSAPP OTP PASSWORD RESET & REGISTRATION VERIFICATION
    // =========================================================================

    /**
     * POST /api/merchant/password/send-otp
     * Send WhatsApp OTP for password reset (unauthenticated).
     */
    public function sendPasswordResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = preg_replace('/\D/', '', $request->phone);
        if (str_starts_with($phone, '03')) {
            $phone = '92' . substr($phone, 1);
        }

        // Find user by phone or tenant whatsapp_number
        $user = \App\Models\User::where('phone', $phone)
            ->orWhere('email', $request->phone)
            ->orWhereHas('tenant', function ($q) use ($phone) {
                $q->where('whatsapp_number', 'like', '%' . substr($phone, -10));
            })
            ->first();

        if (!$user && strlen($phone) >= 10) {
            $user = \App\Models\User::where('phone', 'like', '%' . substr($phone, -10))->first();
        }

        if (!$user) {
            return $this->error('No merchant account found with this WhatsApp phone number.', 404);
        }

        $otp = (string) rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('pw_reset_' . $phone, [
            'otp' => $otp,
            'user_id' => $user->id
        ], now()->addMinutes(15));

        $waService = new WhatsAppMessageService();
        $msg = "🔐 *AsaanDukan Password Reset*\n\nYour OTP code is: *{$otp}*\n\nValid for 15 minutes. Do not share this code with anyone.";
        $waService->send($phone, $msg, ['type' => 'password_reset_otp']);

        return $this->ok(['phone' => $phone], 'Password reset OTP sent to your WhatsApp number.');
    }

    /**
     * POST /api/merchant/password/verify-otp
     */
    public function verifyPasswordResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        $phone = preg_replace('/\D/', '', $request->phone);
        if (str_starts_with($phone, '03')) {
            $phone = '92' . substr($phone, 1);
        }

        $cached = \Illuminate\Support\Facades\Cache::get('pw_reset_' . $phone);

        if (!$cached || $cached['otp'] !== trim($request->otp)) {
            return $this->error('Invalid or expired OTP code. Please request a new code.', 422);
        }

        $resetToken = \Illuminate\Support\Str::random(40);
        \Illuminate\Support\Facades\Cache::put('pw_token_' . $resetToken, [
            'user_id' => $cached['user_id']
        ], now()->addMinutes(15));

        \Illuminate\Support\Facades\Cache::forget('pw_reset_' . $phone);

        return $this->ok(['reset_token' => $resetToken], 'OTP verified successfully!');
    }

    /**
     * POST /api/merchant/password/reset
     */
    public function resetPasswordWithOtp(Request $request): JsonResponse
    {
        $request->validate([
            'reset_token' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $cached = \Illuminate\Support\Facades\Cache::get('pw_token_' . $request->reset_token);

        if (!$cached || empty($cached['user_id'])) {
            return $this->error('Invalid or expired reset token. Please restart password reset.', 422);
        }

        $user = \App\Models\User::find($cached['user_id']);
        if (!$user) {
            return $this->error('User account not found.', 404);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->save();

        \Illuminate\Support\Facades\Cache::forget('pw_token_' . $request->reset_token);

        // Send confirmation WhatsApp message if phone available
        if ($user->phone) {
            $waService = new WhatsAppMessageService();
            $waService->send($user->phone, "✅ *AsaanDukan Password Reset Complete*\n\nYour account password has been updated successfully. You can now login.", ['type' => 'password_reset_success']);
        }

        return $this->ok([], 'Password updated successfully! You can now login.');
    }

    /**
     * POST /api/merchant/register/send-otp
     */
    public function sendRegistrationOtp(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
        ]);

        $phone = preg_replace('/\D/', '', $request->whatsapp_number);
        if (str_starts_with($phone, '03')) {
            $phone = '92' . substr($phone, 1);
        }

        $otp = (string) rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('reg_otp_' . $phone, $otp, now()->addMinutes(15));

        $waService = new WhatsAppMessageService();
        $msg = "📲 *AsaanDukan Store Registration*\n\nYour verification OTP code is: *{$otp}*\n\nEnter this code on the registration page to verify your WhatsApp number.";
        $waService->send($phone, $msg, ['type' => 'registration_otp']);

        return $this->ok(['whatsapp_number' => $phone], 'Registration OTP sent to your WhatsApp number.');
    }

    /**
     * POST /api/merchant/register/verify-otp
     */
    public function verifyRegistrationOtp(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
            'otp' => 'required|string',
        ]);

        $phone = preg_replace('/\D/', '', $request->whatsapp_number);
        if (str_starts_with($phone, '03')) {
            $phone = '92' . substr($phone, 1);
        }

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('reg_otp_' . $phone);

        if (!$cachedOtp || $cachedOtp !== trim($request->otp)) {
            return $this->error('Invalid or expired OTP code. Please check your WhatsApp messages or request a new code.', 422);
        }

        \Illuminate\Support\Facades\Cache::put('reg_verified_' . $phone, true, now()->addMinutes(30));

        return $this->ok(['verified' => true], 'WhatsApp number verified successfully!');
    }

    /**
     * POST /api/merchant/change-password (Protected)
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user)
            return $this->error('Unauthorized', 401);

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($validated['new_password']);
        $user->save();

        return $this->ok([], 'Password updated successfully.');
    }
}
