<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Miracuthbert\LaravelRoles\Models\Role;

trait PermissionTrait
{
    public static function PermissionTrait()
    {
        $flushCache = function ($model) {
            $model->flushCache();
        };

        // If the model doesn't use SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);

        static::deleting(function ($model) {
            if (method_exists($model, 'bootSoftDeletes') && !$model->forceDeleting) {
                return;
            }

            $model->roles()->sync([]);

            $model->users()->sync([]);
        });
    }

    /**
     * Flush the permissions cache.
     *
     * @return void
     */
    public function flushCache()
    {
        Cache::forget('laravelroles_permissions_map');
    }

    /**
     * Get all the users that are assigned this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        $model = config('laravel-roles.users.model', 'App\\User');

        $model = new $model;

        return $this->belongsToMany(get_class($model), 'user_roles')
            ->withTimestamps()
            ->withPivot(['expires_at']);
    }

    /**
     * Get all the roles with this permission.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
