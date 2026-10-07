<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\PlanFeature;

class PlanFeatureService
{
    /**
     * Get the value of a feature for a tenant.
     */
    public function getFeatureValue(Tenant $tenant, string $key): mixed
    {
        $planId = $tenant->subscription_plan_id;

        if (!$planId) {
            return FeatureRegistry::get($key)['default'] ?? null;
        }

        $feature = PlanFeature::where('plan_id', $planId)
            ->where('feature_key', $key)
            ->first();

        if (!$feature) {
            return FeatureRegistry::get($key)['default'] ?? null;
        }

        $definition = FeatureRegistry::get($key);
        $type = $definition['type'] ?? 'string';
        $val = $feature->feature_value;

        return match ($type) {
            'boolean' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $val,
            'decimal' => (float) $val,
            'json' => json_decode($val, true),
            default => $val,
        };
    }

    /**
     * Alias: get raw value with fallback.
     */
    public function getValue(Tenant $tenant, string $key, mixed $default = null): mixed
    {
        $val = $this->getFeatureValue($tenant, $key);
        return $val ?? $default;
    }

    /**
     * Check if a tenant can use a boolean feature.
     */
    public function canUseFeature(Tenant $tenant, string $key): bool
    {
        return (bool) $this->getFeatureValue($tenant, $key);
    }

    /**
     * Alias for canUseFeature — more readable in controllers.
     */
    public function isEnabled(Tenant $tenant, string $key): bool
    {
        return $this->canUseFeature($tenant, $key);
    }

    /**
     * Check if a tenant has reached an integer limit.
     */
    public function hasReachedLimit(Tenant $tenant, string $key, int $currentUsage): bool
    {
        $limit = (int) $this->getFeatureValue($tenant, $key);

        // 0 typically means unlimited
        if ($limit === 0) {
            return false;
        }

        return $currentUsage >= $limit;
    }
}
