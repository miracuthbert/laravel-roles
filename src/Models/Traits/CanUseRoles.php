<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Miracuthbert\LaravelRoles\Models\Role;

trait CanUseRoles
{
    /**
     * The "booting" method for trait.
     *
     * @return void
     */
    public static function bootCanUseRoles()
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

            $model->roles()->forceDelete();
        });
    }

    /**
     * Flush the entity cache.
     *
     * @return void
     */
    public function flushCache()
    {
        $type = array_search(get_class($this), config('laravel-roles.models'));

        if (!$type) {
            return;
        }

        $cacheKey = $type . '_' . $this->getKey();

        Cache::forget('laravelroles_roles_' . $cacheKey);
    }

    /**
     * Create a new role under the entity.
     *
     * @param $role
     * @param \Illuminate\Support\Collection|array $permissions
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newRole($role, $permissions = [])
    {
        $class = get_class($this);

        $id = $this->getOriginal('id');

        $newRole = $this->roles()->create([
            'name' => $role instanceof Role ? $role->name : $role,
            'slug' => $role instanceof Role ? Str::slug($role->slug .' '. $id) : Str::slug($role . ' ' . $id),
            'type' => array_search($class, config('laravel-roles.models')),
        ]);

        // add permissions to role
        $newRole->addPermissions(
            $role instanceof Role ? $role->permissions->pluck('id')->toArray() : $permissions
        );

        return $newRole->refresh();
    }

    /**
     * Get all of the entities roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function roles()
    {
        return $this->morphMany(Role::class, 'roleable');
    }
}
