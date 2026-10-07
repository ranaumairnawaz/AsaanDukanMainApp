<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically set the tenant_id when creating a new record
        static::creating(function (Model $model) {
            if (app()->bound('tenant') && ! $model->getAttribute('tenant_id')) {
                $model->setAttribute('tenant_id', app('tenant')->id);
            }
        });

        // Apply a global scope to filter all queries by the active tenant_id
        static::addGlobalScope('tenant_scope', function (Builder $builder) {
            if (app()->bound('tenant')) {
                $builder->where($builder->getQuery()->from . '.tenant_id', app('tenant')->id);
            }
        });
    }

    /**
     * Relationship to the Tenant model.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
