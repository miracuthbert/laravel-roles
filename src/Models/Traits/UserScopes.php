<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait UserScopes
{
    /**
     * Scope query to include users with matching `permissions`.
     *
     * @param Builder $builder
     * @param string $permission
     * @return Builder|static
     */
    public function scopeOrWherePermissionIs(Builder $builder, $permission = '')
    {
        return $this->scopeWherePermissionIs($builder, $permission, 'or');
    }

    /**
     * Scope query to include users with matching `permissions`.
     *
     * @param Builder $builder
     * @param string $permission
     * @param string $boolean
     * @return Builder|static
     */
    public function scopeWherePermissionIs(Builder $builder, $permission = '', $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'where' : 'orWhere';

        return $builder->{$method}(function ($query) use ($permission) {
            $query->whereHas('roles', function ($query) use ($permission) {
                $query->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->where(function ($query) use ($permission) {
                    $query->whereHas('permissions', function ($permissionQuery) use ($permission) {
                        $permissionQuery->where('name', $permission)->orWhere('slug', $permission);
                    });
                });
            })->orWhereHas('permissions', function ($permissionQuery) use ($permission) {
                $permissionQuery->where('name', $permission)->orWhere('slug', $permission);
            });
        });
    }

    /**
     * Scope query to include users with matching `role`.
     *
     * @param Builder $builder
     * @param $roleSlug
     * @return Builder|static
     */
    public function scopeOrWhereRoleIs(Builder $builder, $roleSlug = '')
    {
        return $this->scopeWhereRoleIs($builder, $roleSlug, 'or');
    }

    /**
     * Scope query to include users with matching `role`.
     *
     * @param Builder $builder
     * @param string $roleSlug
     * @param string $boolean
     * @return Builder|static
     */
    public function scopeWhereRoleIs(Builder $builder, $roleSlug = '', $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'whereHas' : 'orWhereHas';

        return $builder->{$method}('roles', function ($query) use ($roleSlug) {
            $query->where('slug', $roleSlug)
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
