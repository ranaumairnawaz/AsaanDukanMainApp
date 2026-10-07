<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'store_slug',
        'subdomain',
        'subdomain_status',
        'custom_domain',
        'category',
        'logo_path',
        'status',
        'subscription_plan_id',
        'subscription_expires_at',
        'whatsapp_number',
        'delivery_charge',
        'bank_name',
        'account_title',
        'account_number',
        'billing_cycle_start',
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'billing_cycle_start' => 'datetime',
    ];

    protected $appends = [
        'store_url',
    ];

    public function getStoreUrlAttribute(): string
    {
        $appUrl = config('app.url', 'http://localhost');
        if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
            $appUrl = 'http://' . $appUrl;
        }
        $baseDomain = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';

        if ($this->custom_domain) {
            return 'https://' . $this->custom_domain;
        }

        if ($this->subdomain && $this->subdomain_status === 'active') {
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http';
            return $scheme . '://' . $this->subdomain . '.' . $baseDomain;
        }

        return url('/store/' . $this->store_slug);
    }

    /**
     * Get the subscription plan associated with the tenant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get products owned by the tenant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get customers belonging to this tenant.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get orders received by this tenant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the active theme settings.
     */
    public function themeSettings(): HasOne
    {
        return $this->hasOne(TenantThemeSettings::class);
    }

    /**
     * Get message usage history for this tenant.
     */
    public function messageUsages(): HasMany
    {
        return $this->hasMany(TenantMessageUsage::class);
    }

    /**
     * Get the user accounts belonging to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get subscription payments for this tenant.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function domainRequests(): HasMany
    {
        return $this->hasMany(DomainRequest::class);
    }

    public function activeDomainRequest(string $type): ?DomainRequest
    {
        return $this->domainRequests()
            ->where('type', $type)
            ->whereNotIn('status', ['rejected'])
            ->latest()
            ->first();
    }
}
