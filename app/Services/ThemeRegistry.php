<?php

namespace App\Services;

use App\Models\OneTimeUnlockPayment;
use App\Models\TenantThemeSettings;
use App\Models\Tenant;

class ThemeRegistry
{
    /**
     * Fallback preset CSS vars per theme slug, used whenever a tenant hasn't
     * overridden a color/font. Each theme gets its own distinct look instead
     * of every theme rendering with the same hardcoded green/dark palette.
     */
    protected static array $presets = [
        'basic' => [
            'description' => 'A clean, minimal storefront — just your logo, name, and category.',
            'accent' => '#1F7A4D',
            'preview_css_vars' => [
                '--color-accent' => '#1F7A4D',
                '--color-accent-hover' => '#186139',
                '--color-on-accent' => '#FBF6EC',
                '--color-bg' => '#FBF6EC',
                '--color-surface' => '#FFFFFF',
                '--color-border' => '#E7DFCF',
                '--color-text' => '#141B2E',
                '--color-muted' => '#6B6152',
                '--font-heading' => "'Fraunces', serif",
                '--font-body' => "'Plus Jakarta Sans', sans-serif",
            ],
        ],
        'fashion' => [
            'description' => 'Editorial, dark, and elegant — built for apparel and lawn suits.',
            'accent' => '#10b981',
            'preview_css_vars' => [
                '--color-accent' => '#10b981',
                '--color-accent-hover' => '#059669',
                '--color-on-accent' => '#0a0a0a',
                '--color-bg' => '#0a0a0a',
                '--color-surface' => '#111111',
                '--color-border' => '#222222',
                '--color-text' => '#f9fafb',
                '--color-muted' => '#9ca3af',
                '--font-heading' => "'Inter', sans-serif",
                '--font-body' => "'Inter', sans-serif",
            ],
        ],
        'cosmetics' => [
            'description' => 'Soft, warm, and glossy — built for beauty & cosmetics brands.',
            'accent' => '#C97B84',
            'preview_css_vars' => [
                '--color-accent' => '#C97B84',
                '--color-accent-hover' => '#B4636D',
                '--color-on-accent' => '#2B1518',
                '--color-bg' => '#1A1113',
                '--color-surface' => '#241619',
                '--color-border' => '#3A2226',
                '--color-text' => '#F7EAE9',
                '--color-muted' => '#C7ABAA',
                '--font-heading' => "'Playfair Display', serif",
                '--font-body' => "'Lato', sans-serif",
            ],
        ],
        'decor' => [
            'description' => 'Warm and earthy — built for home decor & furniture stores.',
            'accent' => '#B5764B',
            'preview_css_vars' => [
                '--color-accent' => '#B5764B',
                '--color-accent-hover' => '#9C6440',
                '--color-on-accent' => '#241A10',
                '--color-bg' => '#1C1712',
                '--color-surface' => '#26201A',
                '--color-border' => '#3A3025',
                '--color-text' => '#F3ECE2',
                '--color-muted' => '#C2B49F',
                '--font-heading' => "'Cormorant Garamond', serif",
                '--font-body' => "'Nunito', sans-serif",
            ],
        ],
        'electronics' => [
            'description' => 'Sharp, techy, and high-contrast — built for gadgets & electronics.',
            'accent' => '#3B82F6',
            'preview_css_vars' => [
                '--color-accent' => '#3B82F6',
                '--color-accent-hover' => '#2563EB',
                '--color-on-accent' => '#0B1220',
                '--color-bg' => '#0B1220',
                '--color-surface' => '#121A2B',
                '--color-border' => '#1E2A44',
                '--color-text' => '#EAF1FB',
                '--color-muted' => '#8CA0C2',
                '--font-heading' => "'Inter', sans-serif",
                '--font-body' => "'Inter', sans-serif",
            ],
        ],
    ];

    protected static function defaultPreset(string $slug): array
    {
        return static::$presets[$slug] ?? [
            'description' => 'Dynamic DB Theme',
            'accent' => '#10b981',
            'preview_css_vars' => [
                '--color-accent' => '#10b981',
                '--color-accent-hover' => '#059669',
                '--color-on-accent' => '#0a0a0a',
                '--color-bg' => '#0a0a0a',
                '--color-surface' => '#111111',
                '--color-border' => '#222222',
                '--color-text' => '#f9fafb',
                '--color-muted' => '#9ca3af',
                '--font-heading' => "'Inter', sans-serif",
                '--font-body' => "'Inter', sans-serif",
            ],
        ];
    }

    /**
     * All registered themes and their metadata from DB.
     */
    public static function themes(): array
    {
        $dbThemes = \App\Models\Theme::where('is_active', true)->get();

        $themes = [];
        foreach ($dbThemes as $t) {
            $preset = static::defaultPreset($t->slug);

            $themes[$t->slug] = [
                'name' => $t->name,
                'description' => $preset['description'],
                'accent' => $preset['accent'],
                'screenshot' => $t->screenshot_path,
                'demo_url' => $t->demo_url,
                'is_premium' => (bool) $t->is_premium,
                'preview_css_vars' => $preset['preview_css_vars'],
            ];
        }

        // Add a hardcoded default just in case DB is empty to prevent crashes
        if (empty($themes)) {
            $preset = static::defaultPreset('basic');
            $themes['basic'] = [
                'name' => 'Basic',
                'description' => 'Fallback theme.',
                'accent' => $preset['accent'],
                'is_premium' => false,
                'preview_css_vars' => $preset['preview_css_vars'],
            ];
        }

        return $themes;
    }

    /**
     * Get config for a specific theme slug.
     */
    public static function get(string $slug): ?array
    {
        return static::themes()[$slug] ?? null;
    }

    /**
     * Is the given theme slug a premium (paid-tier) theme?
     */
    public static function isPremium(string $slug): bool
    {
        return (bool) (static::get($slug)['is_premium'] ?? true);
    }

    /**
     * Get the active theme config for a tenant.
     * Falls back to 'basic' (or the first available theme) if settings not found.
     */
    public static function forTenant(Tenant $tenant): array
    {
        $settings = $tenant->themeSettings;
        $all = static::themes();
        $fallbackSlug = isset($all['basic']) ? 'basic' : (array_key_first($all) ?? 'basic');

        $slug = $settings?->theme_slug ?? $fallbackSlug;
        $theme = static::get($slug) ?? static::get($fallbackSlug) ?? collect($all)->first();

        // Override accent if tenant has customized it
        if ($settings?->accent_color) {
            $theme['preview_css_vars']['--color-accent'] = $settings->accent_color;
            $theme['accent'] = $settings->accent_color; // used inline in Vue templates
        }

        // Override secondary/surface color
        if ($settings?->secondary_color) {
            $theme['preview_css_vars']['--color-surface'] = $settings->secondary_color;
            $theme['secondary_color'] = $settings->secondary_color;
        }

        // Override custom text/bg colors if set in custom_settings
        $customSettings = $settings?->custom_settings ?? [];
        if (!empty($customSettings['bg_color'])) {
            $theme['preview_css_vars']['--color-bg'] = $customSettings['bg_color'];
            $theme['background'] = $customSettings['bg_color'];
        }
        if (!empty($customSettings['text_color'])) {
            $theme['preview_css_vars']['--color-text'] = $customSettings['text_color'];
            $theme['text_color'] = $customSettings['text_color'];
        }

        // Override typography
        if ($settings?->typography) {
            $theme['preview_css_vars']['--font-body'] = "'{$settings->typography}', sans-serif";
            $theme['typography'] = $settings->typography;
        }

        if ($settings?->heading_font) {
            $theme['preview_css_vars']['--font-heading'] = "'{$settings->heading_font}', sans-serif";
            $theme['heading_font'] = $settings->heading_font;
        } else if ($settings?->typography) {
            // Fallback for existing settings where typography was applied to headings too
            $theme['preview_css_vars']['--font-heading'] = "'{$settings->typography}', sans-serif";
        }

        // Expose custom copy fields (banner text, store description)
        $customSettings = $settings?->custom_settings ?? [];
        if (!empty($customSettings['banner_text'])) {
            $theme['banner_text'] = $customSettings['banner_text'];
        }
        if (!empty($customSettings['store_description'])) {
            $theme['store_description'] = $customSettings['store_description'];
        }
        if (!empty($customSettings['seo_title'])) {
            $theme['seo_title'] = $customSettings['seo_title'];
        }
        if (!empty($customSettings['seo_description'])) {
            $theme['seo_description'] = $customSettings['seo_description'];
        }

        // Expose favicon path
        if ($settings?->favicon_path) {
            $theme['favicon_path'] = $settings->favicon_path;
        }

        // Expose logo path
        if ($settings?->logo_override_path) {
            $theme['logo_path'] = $settings->logo_override_path;
        }

        // Expose hero banner image
        if ($settings?->banner_image_path) {
            $theme['banner_image_url'] = asset('storage/' . $settings->banner_image_path);
        }

        // Expose seo image
        if ($settings?->seo_image_path) {
            $theme['seo_image_url'] = asset('storage/' . $settings->seo_image_path);
        }

        $theme['slug'] = $slug;
        $theme['settings'] = $settings;

        return $theme;
    }

    /**
     * Build a CSS :root { ... } string from theme vars.
     */
    public static function cssVars(array $themeConfig): string
    {
        $vars = $themeConfig['preview_css_vars'] ?? [];
        $lines = [':root {'];
        foreach ($vars as $key => $value) {
            $lines[] = "  {$key}: {$value};";
        }
        $lines[] = '}';
        return implode("\n", $lines);
    }

    /**
     * Check if a tenant is allowed to switch to the given target theme.
     *
     * Free (non-premium) themes are always switchable — the "first plan" gate
     * only ever applies to premium themes (fashion, electronics, decor, cosmetics, ...).
     *
     * For premium themes, the tenant needs either:
     *   - a plan that includes free theme switching ('theme_change_allowed'), or
     *   - an approved-but-not-yet-applied one-time 'theme_change' unlock payment.
     */
    public static function canChangeTheme(Tenant $tenant, PlanFeatureService $planService, ?string $targetSlug = null): bool
    {
        // Always allow on first setup (no settings yet) regardless of target theme.
        if (!$tenant->themeSettings) {
            return true;
        }

        // Switching to a free/basic theme is never gated.
        if ($targetSlug !== null && !static::isPremium($targetSlug)) {
            return true;
        }

        // Plan-level free theme switching.
        if ($planService->isEnabled($tenant, 'theme_change_allowed')) {
            return true;
        }

        // A paid, admin-approved, not-yet-used unlock for this specific change.
        return OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'theme_change')
            ->where('status', 'approved')
            ->where('applied', false)
            ->exists();
    }

    /**
     * @deprecated Use canChangeTheme() instead. Kept temporarily as an alias
     * in case other code still references the old (misspelled) method name.
     */
    public static function canChangTheme(Tenant $tenant, PlanFeatureService $planService): bool
    {
        return static::canChangeTheme($tenant, $planService);
    }
}
