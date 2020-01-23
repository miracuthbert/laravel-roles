<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Miracuthbert\LaravelRoles\Models\Permission;

trait RoleTrait
{
    use CanAccessPermissions;

    /**
     * Boot method for trait.
     *
     * @return void
     */
    public static function bootRoleTrait()
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

            $model->permissions()->sync([]);

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
     * Handle adding and deleting of model permissions.
     *
     * @param $permissions
     */
    public function syncPermissions($permissions)
    {
        $this->deleteRemovedPermissions($permissions);

        $this->addPermissions($permissions);
    }

    /**
     * Add permissions to model.
     *
     * @param $permissions
     */
    public function addPermissions($permissions)
    {
        $this->permissions()->syncWithoutDetaching($this->getWorkablePermissions($permissions));
    }

    /**
     * Remove permissions from model.
     *
     * @param $permissions
     */
    public function detachPermissions($permissions)
    {
        $this->permissions()->detach($this->getWorkablePermissions($permissions));
    }

    /**
     * Delete removed permissions from model based on passed ones.
     *
     * @param $permissions
     */
    public function deleteRemovedPermissions($permissions)
    {
        if (!$this->permissions->count()) {
            return;
        }

        $oldPermissions = $this->permissions()
            ->whereNotIn('id', $this->getWorkablePermissions($permissions))
            ->pluck('id')
            ->toArray();

        $this->permissions()->detach($oldPermissions);
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
     * The all of the model's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, $this->getPermissionsTable())
            ->withTimestamps();
    }

    /**
     * Get the related permissions table.
     *
     * @return string
     */
    public function getPermissionsTable(): string
    {
        return $this->permissions_table;
    }
}
