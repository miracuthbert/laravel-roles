<?php

namespace Miracuthbert\LaravelRoles\Helpers;

use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Miracuthbert\LaravelRoles\Models\Permission;

class Permissions
{
    /**
     * Define active permissions as gates.
     *
     * @return mixed
     */
    public static function gates()
    {
        try {
            if (Schema::hasTable('permissions')) {
                Permission::active()->get()->map(function ($permission) {
                    // define gate by `name`
                    Gate::define($permission->name, function ($user, $giver = null) use ($permission) {
                        return $user->hasPermissionTo($permission, $giver);
                    });

                    // define gate by `slug`
                    Gate::define($permission->slug, function ($user, $giver = null) use ($permission) {
                        return $user->hasPermissionTo($permission, $giver);
                    });
                });
            }
        } catch (Exception $e) {
            // todo: add key in config to enable debug
            logger()->debug($e->getMessage(), $e->getTrace());
        }
    }

    /**
     * Create new permissions.
     *
     * @param $newPermissions
     * @return void
     */
    public static function createPermissions($newPermissions)
    {
        foreach ($newPermissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'type' => $permission['type'] ?? Permission::ADMIN,
            ]);
        }
    }
    /**
     * Filter new permissions.
     *
     * @param $permissions
     * @param $existingPermissions
     * @param null $type
     * @return array
     */
    public static function newPermissions($permissions, $existingPermissions, $type = null): array
    {
        return Roles::permissionsMap($permissions, $type)->whereNotIn(
            'name', $existingPermissions->pluck('name')->toArray()
        )->toArray();
    }


    /**
     * Get existing permissions.
     *
     * @param $permissions
     * @return mixed
     */
    public static function existingPermissions($permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }
}
