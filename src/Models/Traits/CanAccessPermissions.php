<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Arr;
use Miracuthbert\LaravelRoles\Models\Permission;
use Illuminate\Support\Collection;

trait CanAccessPermissions
{
    /**
     * Boot method for trait.
     */
    public static function bootCanAccessPermissions()
    {
        //
    }

    /**
     * Get the permission ID from the mixed value.
     *
     * @param $value
     * @return mixed
     */
    protected function parsePermissionId($value)
    {
        return $value instanceof Permission ? $value->id : $value;
    }

    /**
     * Get a collection of valid permissions from ids.
     *
     * @param array $permissions
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function getPermissionsCollectionFromIds(array $permissions)
    {
        return Permission::query()->whereIn('id', $permissions)->get();
    }

    /**
     * Get an array of valid permissions ids.
     *
     * @param array $permissions
     * @return array
     */
    protected function getPermissionsIds(array $permissions)
    {
        return Permission::query()
            ->whereIn('name', $permissions)
            ->orWhereIn('slug', $permissions)
            ->orWhereIn('id', $permissions)
            ->get()
            ->pluck('id')
            ->all();
    }

    /**
     * Filter out collection of permissions which are not instance of `Permission` model.
     *
     * @param Collection $permissions
     * @return Collection
     */
    protected function filterPermissionsCollection(Collection $permissions)
    {
        return $permissions->filter(function ($permission) {
            return $permission instanceof Permission;
        });
    }

    /**
     * Check and return an array of permission ids.
     *
     * @param $permissions
     * @return array
     */
    protected function getWorkablePermissions($permissions)
    {
        if (is_int($permissions)) {
            return array($permissions);
        }

        if (is_array($permissions)) {
            return Arr::wrap($this->getPermissionsIds($permissions));
        }

        if ($permissions instanceof Permission) {
            return array($permissions->id);
        }

        if ($permissions instanceof Collection) {
            return Arr::wrap($this->filterPermissionsCollection($permissions)->pluck('id')->all());
        }
    }
}
