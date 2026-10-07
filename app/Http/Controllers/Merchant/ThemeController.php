<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\TenantThemeSettings;
use App\Services\ThemeRegistry;
use App\Services\PlanFeatureService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThemeController extends Controller
{
    public function __construct(private PlanFeatureService $planFeature)
    {
    }

    public function index()
    {
        $tenant = app('tenant');
        $theme = ThemeRegistry::forTenant($tenant);

        // Whether the tenant can switch to a *premium* theme right now (free
        // themes like "basic" are always switchable regardless of this flag).
        $canChangePremium = ThemeRegistry::canChangeTheme($tenant, $this->planFeature);

        $pendingThemeUnlock = \App\Models\OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'theme_change')
            ->where('status', 'pending')
            ->exists();

        $products = \App\Models\Product::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Merchant/Theme/Index', [
            'themes' => ThemeRegistry::themes(),
            'active_slug' => $theme['slug'],
            'settings' => $theme['settings'],
            'products' => $products,
            'can_change' => $canChangePremium,
            'theme_change_fee' => $this->planFeature->getValue($tenant, 'theme_change_fee', 500),
            'theme_unlock_pending' => $pendingThemeUnlock,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = app('tenant');

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

        // Only gate the actual theme SLUG change (layout change), not colour/copy updates.
        // Switching to a free theme (e.g. "basic") is always allowed. Switching to a
        // premium theme requires the plan to allow it, or a paid+approved one-time unlock.
        $currentSlug = $tenant->themeSettings?->theme_slug;
        $isChangingTheme = $request->theme_slug !== $currentSlug && $currentSlug !== null;

        $hasApprovedThemeUnlock = \App\Models\OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'theme_change')
            ->where('status', 'approved')
            ->where('applied', false)
            ->exists();

        if ($isChangingTheme) {
            if (!ThemeRegistry::canChangeTheme($tenant, $this->planFeature, $request->theme_slug)) {
                return back()->withErrors(['error' => 'Switching to this premium theme requires an upgrade or a one-time unlock payment. Head to Settings to pay the theme change fee.']);
            }

            // If the switch only went through because of a paid unlock, consume it now.
            if (
                ThemeRegistry::isPremium($request->theme_slug)
                && !$this->planFeature->isEnabled($tenant, 'theme_change_allowed')
                && $hasApprovedThemeUnlock
            ) {
                \App\Models\OneTimeUnlockPayment::where('tenant_id', $tenant->id)
                    ->where('unlock_type', 'theme_change')
                    ->where('status', 'approved')
                    ->where('applied', false)
                    ->update(['applied' => true]);
            }
        }

        // Build custom_settings (banner text, description, etc.)
        $customSettings = $tenant->themeSettings?->custom_settings ?? [];
        if ($request->filled('banner_text')) {
            $customSettings['banner_text'] = $request->banner_text;
        }
        if ($request->filled('store_description')) {
            $customSettings['store_description'] = $request->store_description;
        }
        if ($request->filled('bg_color')) {
            $customSettings['bg_color'] = $request->bg_color;
        }
        if ($request->filled('text_color')) {
            $customSettings['text_color'] = $request->text_color;
        }
        if ($request->filled('seo_title')) {
            $customSettings['seo_title'] = $request->seo_title;
        }
        if ($request->filled('seo_description')) {
            $customSettings['seo_description'] = $request->seo_description;
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

        if ($request->hasFile('favicon')) {
            $settingsData['favicon_path'] = $request->file('favicon')->store('favicons/' . $tenant->id, 'public');
        }

        if ($request->hasFile('logo')) {
            $settingsData['logo_override_path'] = $request->file('logo')->store('logos/' . $tenant->id, 'public');
        }

        if ($request->hasFile('banner_image')) {
            $settingsData['banner_image_path'] = $request->file('banner_image')->store('banners/' . $tenant->id, 'public');
        }

        if ($request->hasFile('seo_image')) {
            $settingsData['seo_image_path'] = $request->file('seo_image')->store('seo/' . $tenant->id, 'public');
        }

        TenantThemeSettings::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $settingsData
        );

        return back()->with('success', 'Theme settings saved! Your storefront will reflect the changes immediately.');
    }
}
