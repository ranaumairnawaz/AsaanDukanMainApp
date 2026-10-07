<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = GlobalSetting::all()->keyBy('key')->map(fn($s) => $s->value);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'brand.platform_name' => 'nullable|string',
            'brand.primary_color' => 'nullable|string',
            'brand.secondary_color' => 'nullable|string',
            'brand.typography' => 'nullable|string',
            
            'seo.meta_title' => 'nullable|string',
            'seo.meta_description' => 'nullable|string',

            'payment.currency' => 'nullable|string',
            'payment.accounts' => 'nullable|array',

            'email.smtp_host' => 'nullable|string',
            'email.smtp_port' => 'nullable|string',
            'email.smtp_user' => 'nullable|string',
            'email.smtp_pass' => 'nullable|string',
            'email.from_address' => 'nullable|email',

            'landing.header' => 'nullable|array',
            'landing.hero' => 'nullable|array',
            'landing.footer' => 'nullable|array',
        ]);

        // Flatten the data and save
        foreach ($validated as $group => $values) {
            if (is_array($values) && !in_array($group, ['payment.accounts', 'landing.header', 'landing.hero', 'landing.footer'])) {
                // this is for groups like brand, seo if we sent them as nested objects
                // But the validation above uses dot notation, which means the request actually has these as separate keys
            }
        }

        // To handle files easily, we iterate request->all() but only save safe keys
        $safeKeys = [
            'brand_platform_name', 'brand_primary_color', 'brand_secondary_color', 'brand_typography',
            'seo_meta_title', 'seo_meta_description',
            'payment_currency', 'payment_accounts',
            'email_smtp_host', 'email_smtp_port', 'email_smtp_user', 'email_smtp_pass', 'email_from_address',
            'admin_whatsapp_number', 'custom_domain_connection_fee',
            
            // Landing Page New Keys
            'landing_show_hero', 'landing_show_trusted', 'landing_show_features', 'landing_show_analytics',
            'landing_show_notifications', 'landing_show_stats', 'landing_show_use_cases', 'landing_show_faq',
            'landing_show_testimonials', 'landing_show_pricing', 'landing_show_cta', 'landing_show_contact',
            
            'landing_hero_headline', 'landing_hero_subheadline', 'landing_hero_btn1_text', 'landing_hero_btn2_text',
            'landing_trusted_title', 'landing_trusted_logos_json',
            'landing_features_title', 'landing_features_json',
            'landing_analytics_title', 'landing_analytics_text', 'landing_analytics_bullets_json',
            'landing_notifications_title', 'landing_notifications_text', 'landing_notifications_bullets_json',
            'landing_stats_json',
            'landing_use_cases_title', 'landing_use_cases_json',
            'landing_faq_title', 'landing_faq_json',
            'landing_testimonials_title', 'landing_testimonials_json',
            'landing_pricing_title',
            'landing_cta_title', 'landing_cta_text',
            'landing_contact_title', 'landing_contact_email', 'landing_contact_phone', 'landing_contact_location',
            'landing_support_whatsapp',

            // Maintenance
            'maintenance_mode_enabled', 'maintenance_disable_register',
        ];

        foreach ($safeKeys as $key) {
            if ($request->has($key)) {
                GlobalSetting::setVal($key, $request->input($key));
            }
        }

        // Handle File Uploads separately
        if ($request->hasFile('brand_logo')) {
            GlobalSetting::setVal('brand_logo', $request->file('brand_logo')->store('platform', 'public'));
        }
        if ($request->hasFile('brand_favicon')) {
            GlobalSetting::setVal('brand_favicon', $request->file('brand_favicon')->store('platform', 'public'));
        }
        if ($request->hasFile('seo_og_image')) {
            GlobalSetting::setVal('seo_og_image', $request->file('seo_og_image')->store('platform', 'public'));
        }
        if ($request->hasFile('landing_footer_logo')) {
            GlobalSetting::setVal('landing_footer_logo', $request->file('landing_footer_logo')->store('platform', 'public'));
        }

        // Landing Page Images
        $landingImages = [
            'landing_hero_image_1', 'landing_hero_image_2', 'landing_hero_image_3',
            'landing_analytics_image', 'landing_notifications_image',
            'landing_faq_image', 'landing_cta_image'
        ];
        foreach ($landingImages as $imgKey) {
            if ($request->hasFile($imgKey)) {
                GlobalSetting::setVal($imgKey, $request->file($imgKey)->store('platform', 'public'));
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
