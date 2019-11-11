<?php

namespace Miracuthbert\LaravelRoles\Helpers;

use Miracuthbert\LaravelRoles\Models\Permission;
use Exception;
use Illuminate\Support\Facades\Gate;

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
            return Permission::active()->get()->map(function ($permission) {
                // define gate by `name`
                Gate::define($permission->name, function ($user, $giver = null) use ($permission) {
                    return $user->hasPermissionTo($permission, $giver);
                });

                // define gate by `slug`
                Gate::define($permission->slug, function ($user, $giver = null) use ($permission) {
                    return $user->hasPermissionTo($permission, $giver);
                });
            });
        } catch (Exception $e) {
            logger()->debug($e->getMessage(), $e->getTrace());
        }
    }
}
