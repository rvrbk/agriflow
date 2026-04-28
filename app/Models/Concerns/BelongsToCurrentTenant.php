<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCurrentTenant
{
    public static function bootBelongsToCurrentTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = static::resolveCurrentTenantId();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.corporation_id', $tenantId);
            }
        });

        static::creating(function ($model): void {
            $tenantId = static::resolveCurrentTenantId();

            if ($tenantId !== null && empty($model->corporation_id)) {
                $model->corporation_id = $tenantId;
            }
        });
    }

    private static function resolveCurrentTenantId(): ?int
    {
        $tenantModelClass = config('multitenancy.tenant_model');

        if (! is_string($tenantModelClass) || ! class_exists($tenantModelClass)) {
            return null;
        }

        $tenant = $tenantModelClass::current();

        return $tenant?->id;
    }
}
