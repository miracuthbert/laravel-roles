<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Miracuthbert\LaravelRoles\Helpers\Users;
use Miracuthbert\LaravelRoles\Models\Permission;
use Miracuthbert\LaravelRoles\Models\Role;

trait LaravelRolesHelperTrait
{
    /**
     * Determine if caching is allowed.
     *
     * @return mixed
     */
    public function cacheEnabled()
    {
        return Config::get('laravel-roles.cache.enabled', true);
    }

    /**
     * The time in seconds before cache expiry.
     *
     * @return mixed
     */
    public function cacheExpiryTime()
    {
        return Config::get('laravel-roles.cache.expiration_time', 3600);
    }

    /**
     * Determine if user has given roles.
     *
     * @param array $roles
     * @return mixed
     */
    public function checkHasRoles($roles)
    {
        $collection = $this->getUserRoles();

        return $collection->whereIn('slug', $roles)->count();
    }

    /**
     * Get the user's roles.
     *
     * @return mixed
     */
    public function getUserRoles()
    {
        if (!$this->cacheEnabled()) {
            return $this->getCurrentUserRoles();
        }

        $cacheKey = 'laravelroles_roles_' . Users::userModelCacheKey() . '_' . $this->getKey();

        return Cache::remember($cacheKey, $this->cacheExpiryTime(), function () {
            return $this->getCurrentUserRoles();
        });
    }

    /**
     * Converts a collection of roles to array.
     *
     * @param $roles
     * @return mixed
     */
    public function parseRolesToArray($roles)
    {
        return $roles->pluck('slug')->toArray();
    }

    /**
     * Get roles assigned to the entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps()
            ->withPivot(['expires_at']);
    }

    /**
     * Get the role ID from the mixed value.
     *
     * @param $value
     * @return mixed
     */
    public function parseRoleId($value)
    {
        if ($this->isInstanceOfRoleModel($value)) {
            return $value->id;
        }

        return optional($this->findRole($value))->id;
    }

    /**
     * Get role from value.
     *
     * @param $value
     * @return mixed
     */
    public function findRole($value)
    {
        if ($this->isInstanceOfRoleModel($value)) {
            return $value;
        }

        if (is_int($value)) {
            return Role::find($value);
        }

        return Role::where('slug', $value)->first();
    }

    /**
     * Get an array of valid roles ids.
     *
     * @param array $role
     * @return array
     */
    public function getRolesIds(array $role)
    {
        return Role::whereIn('slug', $role)
            ->get(['id'])
            ->all();
    }

    /**
     * Filter out collection of permissions which are not instance of `Role` model.
     *
     * @param Collection $roles
     * @return Collection
     */
    public function filterRolesCollection(Collection $roles)
    {
        return $roles->filter(function ($role) {
            return $role instanceof Role;
        });
    }

    /**
     * Check and return an array of role ids.
     *
     * @param $values
     * @return array
     */
    public function getWorkableRoles($values)
    {
        if (is_int($values)) {
            return array(
                optional($this->findRole($values))->id
            );
        }

        if (is_array($values)) {
            return Arr::wrap($this->getRolesIds($values));
        }

        if ($this->isInstanceOfRoleModel($values)) {
            return array($values->id);
        }

        if ($values instanceof Collection) {
            return Arr::wrap($this->filterRolesCollection($values)->pluck('id')->all());
        }
    }

    /**
     * Determine if given value is instance of role model.
     *
     * @param $value
     * @return bool
     */
    public function isInstanceOfRoleModel($value): bool
    {
        return $value instanceof Role;
    }

    /**
     * Get roles for given permission.
     *
     * @param $permission
     * @return mixed
     */
    public function getPermissionRoles($permission)
    {
        $permissions = $this->getAllPermissions();

        $record = $permissions->where('id', $permission->id)->first();

        return $record->roles;
    }

    /**
     * Get all valid permissions.
     *
     * @return mixed
     */
    public function validPermissions()
    {
        if (!$this->cacheEnabled()) {
            return $this->getCurrentUserPermissions();
        }

        $cacheKey = 'laravelroles_permissions_' . Users::userModelCacheKey() . '_' . $this->getKey();

        return Cache::remember($cacheKey, $this->cacheExpiryTime(), function () {
            return $this->getCurrentUserPermissions();
        });
    }

    /**
     * Get all permissions.
     *
     * @return mixed
     */
    public function getAllPermissions()
    {
        if (!$this->cacheEnabled()) {
            return Permission::with([
                'roles' => function ($query) {
                    $query->active();
                },
            ])->active()->get();
        }

        return Cache::remember('laravelroles_permissions_map', $this->cacheExpiryTime(), function () {
            return Permission::with([
                'roles' => function ($query) {
                    $query->active();
                },
            ])->active()->get();
        });
    }

    /**
     * Get permissions assigned to the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps()
            ->withPivot(['expires_at']);
    }

    /**
     * Get current user's roles.
     *
     * @return mixed
     */
    public function getCurrentUserRoles()
    {
        $this->load([
            'roles' => function($query) {
                $query->active()
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            },
        ]);

        return $this->roles;
    }

    /**
     * Get current user's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getCurrentUserPermissions()
    {
        return $this->permissions()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->get();
    }

    /**
     * Get role's owned by a given entity.
     *
     * @param \Illuminate\Database\Eloquent\Model $giver
     * @return mixed
     */
    public function getGiverRoles($giver)
    {
        if ($this->cacheEnabled()) {
            $cacheKey = array_search(get_class($giver), config('laravel-roles.models')) . '_' . $giver->getKey();

            return Cache::remember('laravelroles_roles_' . $cacheKey,
                $this->cacheExpiryTime(),
                function () use ($giver) {
                    return $giver->roles;
                });
        }

        return $giver->roles;
    }

    /**
     * Find a permission by id from all permissions.
     *
     * @param $permission
     * @return mixed
     */
    public function findPermissionFromCollection($permission)
    {
        return $this->getAllPermissions()->where('id', $permission->id)->first();
    }
}
