<?php

namespace Miracuthbert\LaravelRoles\Helpers;

use Miracuthbert\LaravelRoles\Models\Permission;
use Miracuthbert\LaravelRoles\Models\Role;

class Roles
{
    /**
     * A list of all `ADMIN` permissions.
     *
     * @var array
     */
    public static $adminPermissions = [
        'browse admin',
        'view permission',
        'browse permissions',
        'create permission',
        'update permission',
        'delete permission',
        'view permission',
        'browse roles',
        'create role',
        'update role',
        'delete role',
        'assign roles',
        'delete admins',
        'view user',
        'browse users',
        'impersonate user',
        'create user',
        'update user',
        'delete user',
        'view plan',
        'browse plans',
        'create plan',
        'update plan',
        'delete plan',
    ];

    /**
     * Get a list of default `app` roles.
     *
     * @return array
     */
    public static function roles()
    {
        return [
            'admin' => [
                'name' => 'Admin',
                'type' => Role::ADMIN,
                'permissions' => [],
            ],
            'admin-root' => [
                'name' => 'Root',
                'type' => Role::ADMIN,
                'parent' => 'admin',
                'permissions' => self::permissionsMap(static::$adminPermissions, Permission::ADMIN)->all(),
            ],
            'admin-basic' => [
                'name' => 'Basic Admin',
                'type' => Role::ADMIN,
                'parent' => 'admin',
                'permissions' => self::permissionsMap(static::$adminPermissions, Permission::ADMIN)
                    ->whereNotIn('name', [  // add permissions that role should not be assigned
                        'delete admins',
                    ])->all(),
            ],
        ];
    }

    /**
     * Get a collection of `permissions`.
     *
     * @param array $permissions
     * @param null $type
     * @return static
     */
    private static function permissionsMap(array $permissions, $type = null)
    {
        return collect($permissions)->map(function ($item) use ($type) {
            $newPermission = [
                'name' => $item,
            ];

            if (isset($type)) {
                $newPermission['type'] = $type;
            }

            return $newPermission;
        });
    }
}
