<?php

namespace App\Services;

class FeatureRegistry
{
    protected static array $features = [
        'product_limit' => [
            'type' => 'integer',
            'label' => 'Product Limit (max products)',
            'default' => 20,
        ],
        'order_limit_monthly' => [
            'type' => 'integer',
            'label' => 'Monthly Order Limit (0 for unlimited)',
            'default' => 50,
        ],
        'analytics_enabled' => [
            'type' => 'boolean',
            'label' => 'Enable Merchant Analytics',
            'default' => false,
        ],
        'customer_whatsapp_notifications' => [
            'type' => 'boolean',
            'label' => 'Send WhatsApp Notifications to Customers',
            'default' => false,
        ],
        'message_quota_monthly' => [
            'type' => 'integer',
            'label' => 'Monthly WhatsApp Message Quota',
            'default' => 100,
        ],
        'custom_domain_allowed' => [
            'type' => 'boolean',
            'label' => 'Allow Custom Domains',
            'default' => false,
        ],
        'subdomain_allowed' => [
            'type' => 'boolean',
            'label' => 'Allow Custom Subdomains',
            'default' => false,
        ],
        'staff_logins_limit' => [
            'type' => 'integer',
            'label' => 'Staff Logins Limit',
            'default' => 1,
        ],
        'theme_change_allowed' => [
            'type' => 'boolean',
            'label' => 'Allow Free Theme Changes after Onboarding',
            'default' => false,
        ],
        'theme_change_fee' => [
            'type' => 'decimal',
            'label' => 'Theme Change Overage Fee (Rs.)',
            'default' => 500.00,
        ],
        'overage_message_price' => [
            'type' => 'decimal',
            'label' => 'WhatsApp Overage Message Price (Rs.)',
            'default' => 1.50,
        ],
        'extra_order_fee' => [
            'type' => 'decimal',
            'label' => 'Overage Fee per Extra Order (Rs.)',
            'default' => 10.00,
        ],
        'customer_crm' => [
            'type' => 'boolean',
            'label' => 'Enable Customer CRM (Order History)',
            'default' => false,
        ],
        'bulk_import_export' => [
            'type' => 'boolean',
            'label' => 'Enable Bulk Product Import/Export',
            'default' => false,
        ],
        'store_info_change_allowed' => [
            'type' => 'boolean',
            'label' => 'Allow Free Store Name & Slug Changes',
            'default' => false,
        ],
        'store_info_change_fee' => [
            'type' => 'decimal',
            'label' => 'Store Name/Slug Change Fee (Rs.)',
            'default' => 1000.00,
        ],
        'premium_themes_allowed' => [
            'type' => 'boolean',
            'label' => 'Allow Premium Themes at Registration & Theme Switcher',
            'default' => false,
        ],
    ];

    /**
     * Get all registered features.
     */
    public static function all(): array
    {
        return self::$features;
    }

    /**
     * Get a specific feature definition.
     */
    public static function get(string $key): ?array
    {
        return self::$features[$key] ?? null;
    }
}
