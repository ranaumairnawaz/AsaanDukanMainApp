<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Plan',
                'slug' => 'basic',
                'price_monthly' => 1000.00,
                'price_yearly' => 10000.00,
                'sort_order' => 1,
                'features' => [
                    'product_limit' => 30,
                    'order_limit_monthly' => 100,
                    'analytics_enabled' => false,
                    'customer_whatsapp_notifications' => false,
                    'message_quota_monthly' => 100,
                    'custom_domain_allowed' => false,
                    'subdomain_allowed' => false,
                    'staff_logins_limit' => 1,
                    'theme_change_allowed' => false,
                    'theme_change_fee' => 500.00,
                    'overage_message_price' => 2.00,
                ],
            ],
            [
                'name' => 'Growth Plan',
                'slug' => 'growth',
                'price_monthly' => 2500.00,
                'price_yearly' => 25000.00,
                'sort_order' => 2,
                'features' => [
                    'product_limit' => 150,
                    'order_limit_monthly' => 500,
                    'analytics_enabled' => true,
                    'customer_whatsapp_notifications' => true,
                    'message_quota_monthly' => 1000,
                    'custom_domain_allowed' => false,
                    'subdomain_allowed' => true,
                    'staff_logins_limit' => 3,
                    'theme_change_allowed' => true,
                    'theme_change_fee' => 0.00,
                    'overage_message_price' => 1.50,
                ],
            ],
            [
                'name' => 'Enterprise Plan',
                'slug' => 'enterprise',
                'price_monthly' => 6000.00,
                'price_yearly' => 60000.00,
                'sort_order' => 3,
                'features' => [
                    'product_limit' => 0, // 0 for unlimited
                    'order_limit_monthly' => 0, // 0 for unlimited
                    'analytics_enabled' => true,
                    'customer_whatsapp_notifications' => true,
                    'message_quota_monthly' => 5000,
                    'custom_domain_allowed' => true,
                    'subdomain_allowed' => true,
                    'staff_logins_limit' => 10,
                    'theme_change_allowed' => true,
                    'theme_change_fee' => 0.00,
                    'overage_message_price' => 1.00,
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = \App\Models\SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            foreach ($features as $key => $value) {
                \App\Models\PlanFeature::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $key,
                    ],
                    [
                        'feature_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    ]
                );
            }
        }
    }
}
