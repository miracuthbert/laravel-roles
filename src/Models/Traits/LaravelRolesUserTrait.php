<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait LaravelRolesUserTrait
{
    use HasRoles,
        HasPermissions,
        UserScopes;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function bootLaravelRolesUserTrait()
    {
        $flushCache = function ($user) {
            $user->flushCache();
        };

        // If the user doesn't use SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);

        static::deleting(function ($user) {
            if (method_exists($user, 'bootSoftDeletes') && !$user->forceDeleting) {
                return;
            }

            $user->roles()->sync([]);
            $user->permissions()->sync([]);
        });
    }

    /**
     * Flush the user's cache.
     *
     * @return void
     */
    public function flushCache()
    {
        $this->flushUserRolesCache();
        $this->flushUserPermissionsCache();
    }
}
