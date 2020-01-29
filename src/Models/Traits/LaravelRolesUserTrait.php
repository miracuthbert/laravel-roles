<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Miracuthbert\LaravelRoles\Helpers\ConfigHelper;
use Miracuthbert\LaravelRoles\Helpers\Users;
use Miracuthbert\LaravelRoles\Models\Role;

trait LaravelRolesUserTrait
{
    use CanAccessPermissions,
        LaravelRolesHelperTrait,
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

    /**
     * Flush the user's permissions cache.
     *
     * @return void
     */
    public function flushUserPermissionsCache()
    {
        $cacheKey = 'laravelroles_permissions_' . Users::userModelCacheKey() . '_' . $this->getKey();

        Cache::forget($cacheKey);
    }

    /**
     * Check if given model has given permission.
     *
     * @param $permission
     * @param string|\Illuminate\Database\Eloquent\Model|null $giver
     *
     * If `string` is passed the format should be: 'type:id'.
     *
     * The `type` is a key with a matching value
     * in the package's config `models` key.
     *
     * eg. If type is `team` it will resolve for 'App\Team' model
     *
     * Note: 'team' => \App\Team::class must be registered in the
     * config model's key or the check will return false
     *
     * The `id` is a value of the corresponding model id.
     *
     * @return bool
     */
    public function hasPermissionTo($permission, $giver = null)
    {
        return $this->hasPermissionThroughRole($permission, $giver) || $this->hasPermission($permission);
    }

    /**
     * Check if model has permission through role.
     *
     * @param mixed $permission
     * @param string|\Illuminate\Database\Eloquent\Model|null $giver
     *
     * If `string` is passed the format should be: 'type:id'.
     *
     * The `type` is a key with a matching value
     * in the package's config `models` key.
     *
     * eg. If type is `team` it will resolve for 'App\Team' model
     *
     * Note: 'team' => \App\Team::class must be registered in the
     * config model's key or the check will return false
     *
     * The `id` is a value of the corresponding model id.
     *
     * @return mixed
     */
    public function hasPermissionThroughRole($permission, $giver = null)
    {
        if (isset($giver)) {

            // check if giver is an array
            if (count($params = explode(':', $giver)) == 2) {

                // split type and id
                list($type, $id) = $params;

                // find related model based on type in config
                $model = Arr::get(config('laravel-roles.models'), strtolower($type));

                // check if null or not set
                if (!$model) {
                    return false;
                }

                // model key
                $modelKey = (new $model)->getRouteKeyName();

                // find model based on id or route key
                $giver = isset($modelKey) ? $model::where($modelKey, $id)->first() : $model::find($id);

                // check if giver is not instance of Eloquent Model
                if (!$giver instanceof Model) {
                    return false;
                }
            } else {

                // check if giver is not instance of Eloquent Model
                if (!$giver instanceof Model) {
                    return false;
                }
            }

            // get roles with given permission
            $loadedPermission = $this->findPermissionFromCollection($permission);

            $giverRoles = $this->getGiverRoles($giver);

            $roles = $loadedPermission->roles->whereIn('slug', $this->parseRolesToArray($giverRoles));

            $count = $this->checkHasRoles($this->parseRolesToArray($roles));

            if ($count > 0) {
                return true;
            }

            return false;
        } else {
            if (!($roles = $this->getPermissionRoles($permission))) {
                return false;
            }

            $count = $this->checkHasRoles($this->parseRolesToArray($roles));

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if model has permission and it is valid (not expired).
     *
     * @param $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!ConfigHelper::cacheEnabled()) {
            return (bool)$this->validPermissions()
                ->where('slug', $permission->slug)
                ->count();
        }

        $count = $this->validPermissions()->where('slug', $permission->slug)->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * Delete all permissions for the given model.
     *
     * @return bool
     */
    public function detachPermissions()
    {
        $this->permissions()->detach();

        $this->flushUserPermissionsCache();

        return true;
    }

    /**
     * Revoke given permission now or at given time.
     *
     * @param $permission
     * @param null $expiresAt
     * @return bool
     */
    public function revokePermissionAt($permission, $expiresAt = null)
    {
        $expiresAt = $expiresAt !== null ? Carbon::parse($expiresAt)->toDateTimeString() : Carbon::now();

        $id = $this->parsePermissionId($permission);

        $this->permissions()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', Carbon::now())
            ->updateExistingPivot($id, [
                'expires_at' => $expiresAt
            ]);

        $this->flushUserPermissionsCache();

        return true;
    }

    /**
     * Revoke all or given permissions for the model.
     *
     * @param array $permissions
     * @return bool
     */
    public function revokePermissions($permissions = [])
    {
        $count = $this->validPermissions()->count();

        // stop if no valid permissions
        if (!$count) {
            return false;
        }

        // revoke if `permissions` param is `bool` and `true`
        if (count($permissions) === 0) {

            // set expired
            $this->permissions()
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now()->toDateTimeString())
                ->each(function ($permission) {
                    $this->revokePermissionAt($permission);
                });

            $this->flushUserPermissionsCache();

            return true;
        }

        // set given permissions as expired
        $this->permissions()
            ->whereIn('permissions.id', $this->getWorkablePermissions($permissions))
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', Carbon::now()->toDateTimeString())
            ->each(function ($permission) {
                $this->revokePermissionAt($permission);
            });

        $this->flushUserPermissionsCache();

        return true;
    }

    /**
     * Add permission to model.
     *
     * @param $permission
     * @param null $expiresAt
     * @return bool
     */
    public function assignPermission($permission, $expiresAt = null)
    {
        // check if expiry date is less than current time
        if (isset($expiresAt) && Carbon::now()->gte($expiresAt)) {
            return false;
        }

        $workablePermissions = $this->getWorkablePermissions($permission);

        if (!$workablePermissions) {
            return false;
        }

        $this->permissions()->attach($workablePermissions, [
            'expires_at' => $expiresAt
        ]);

        $this->flushUserPermissionsCache();

        return true;
    }

    /**
     * Add given permissions to model.
     *
     * @param $permissions
     * @return array|mixed
     */
    public function assignPermissions($permissions)
    {
        // ids
        $ids = $this->getWorkablePermissions($permissions);

        // get existing permissions
        $existingPermissions = $this->permissions()
            ->whereIn('permissions.id', $ids)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', Carbon::now())
            ->get();

        // map out ids for new permissions
        $mapped = $this->getMappedPermissions($ids, $existingPermissions);

        // sync permissions
        $changes = $this->permissions()->sync($mapped);

        // check if any attached
        if (count($changes['attached']) === 0) {
            return false;
        }

        $this->flushUserPermissionsCache();

        return true;
    }

    /**
     * Get an array of mapped permissions.
     *
     * @param $ids
     * @param $existingPermissions
     * @return array
     */
    public function getMappedPermissions($ids, $existingPermissions)
    {
        // filter and map by `id`
        $raw = collect($ids)->filter(function ($id) use ($existingPermissions) {
            return $existingPermissions->whereNotIn('id', $id);
        })->map(function ($id) {
            return [
                'id' => $id
            ];
        })->all();

        $data = collect($raw)->keyBy('id')->map(function ($id) {
            return [
                'expires_at' => null,
            ];
        })->toArray();

        return $data;
    }

    /**
     * Flush the user's roles cache.
     *
     * @return void
     */
    public function flushUserRolesCache()
    {
        $cacheKey = 'laravelroles_roles_' . Users::userModelCacheKey() . '_' . $this->getKey();

        Cache::forget($cacheKey);
    }

    /**
     * Determine if the entity is the last Admin of its kind.
     *
     * @param \Miracuthbert\LaravelRoles\Models\Role $role
     * @param array $permissions
     * @return bool
     */
    public function isLastAdmin(Role $role, $permissions = [])
    {
        $permissions = count($permissions) !== 0 ? $permissions : ['browse-admin', 'assign-roles', 'delete-admins'];

        if (!$role->permissions->whereIn('slug', $permissions)->count()) {
            return false;
        }

        return $role->users->count() == 1;
    }

    /**
     * Delete all roles for the given entity.
     *
     * @param array $roles
     * @param null $giver
     * @return bool
     */
    public function detachRoles($roles = [], $giver = null)
    {
        // check if giver passed
        if ($giver) {
            // delete only passed roles
            if (count($roles) > 0) {
                // fetch valid roles ids
                $roles = $this->getWorkableRoles($roles);

                $detachableRoles = $giver->roles()
                    ->whereIn('id', $roles)
                    ->get();

                // detach if roles are available
                if ($detachableRoles->count() > 0) {
                    $this->roles()->detach(
                        $detachableRoles->pluck('id')->toArray()
                    );
                }
            } else { // delete all roles
                $this->roles()->detach(
                    $giver->roles->pluck('id')->toArray()
                );
            }

            $this->flushUserRolesCache();

            return true;
        }

        // query builder
        $builder = Role::query()->where('type', Role::ADMIN);

        // detach only `ADMIN` based roles
        if (count($roles) > 0) {
            // fetch valid roles ids
            $adminRoles = $this->getWorkableRoles($roles);

            // query for `ADMIN` roles
            $query = $builder->whereIn('id', $adminRoles)->get();

            // detach if roles are available
            if ($query->count() > 0) {
                $this->roles()->detach(
                    $query->pluck('id')->toArray()
                );

                $this->flushUserRolesCache();

                return true;
            }

            return false;
        } else { // detach all `ADMIN` based roles
            $this->roles()->detach(
                $builder->get()->pluck('id')->toArray()
            );
        }

        $this->flushUserRolesCache();

        return true;
    }

    /**
     * Revoke given role now or at the given time.
     *
     * @param \Miracuthbert\LaravelRoles\Models\Role $role
     * @param null $expiresAt
     * @param bool $never
     * @return bool
     */
    public function revokeRoleAt($role, $expiresAt = null, $never = false)
    {
        $expiresAt = isset($expiresAt) ? Carbon::parse($expiresAt)->toDateTimeString() : Carbon::now();

        $id = $this->parseRoleId($role);

        // set expiry null if never expires if never expires
        $expiry = $never ? null : $expiresAt;

        $updated = $this->roles()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', Carbon::now())
            ->updateExistingPivot($id, [
                'expires_at' => $expiry
            ]);

        // check if updated
        if (!$updated) {
            return false;
        }

        $this->flushUserRolesCache();

        return true;
    }

    /**
     * Revoke all or given roles for the model.
     *
     * @param array $roles
     * @param null $giver
     * @return bool
     */
    public function revokeRoles($roles = [], $giver = null)
    {
        // check if has `GIVER` passed
        if (isset($giver)) {
            // revoke only passed roles
            if (count($roles) > 0) {
                $detachableRoles = $giver->roles()->whereIn('id', $roles)->get()->pluck(['id'])->toArray();

                $detachableRoles->each(function ($role) {
                    $this->revokeRoleAt($role);
                });
            } else { // revoke all roles
                $this->roles()
                    ->whereIn('roles.id', $giver->roles->pluck('id')->toArray())
                    ->each(function ($role) {
                        $this->revokeRoleAt($role);
                    });
            }

            $this->flushUserRolesCache();

            return true;
        }

        // stop if user has no roles
        if (!$this->roles->count()) {
            return false;
        }

        // revoke all `ADMIN` roles if no `roles` passed
        if (count(Arr::wrap($roles)) === 0) {
            // get users roles
            $this->roles()
                ->where('type', Role::ADMIN)
                ->each(function ($role) {
                    $this->revokeRoleAt($role);
                });

            $this->flushUserRolesCache();

            return true;
        }

        // get an array of role ids
        $ids = $this->getWorkableRoles($roles);

        // set given `ADMIN` roles as expired
        $this->roles()
            ->where('type', Role::ADMIN)
            ->whereIn('roles.id', $ids)
            ->each(function ($role) {
                $this->revokeRoleAt($role);
            });

        $this->flushUserRolesCache();

        return true;
    }

    /**
     * Assign role to model.
     *
     * @param \Miracuthbert\LaravelRoles\Models\Role $role
     * @param $expiresAt
     * @return bool
     */
    public function assignRole($role, $expiresAt = null)
    {
        // stop if role exists and is valid
        if ($this->hasRole($role->slug)) {
            return false;
        }

        $id = $this->parseRoleId($role);

        // stop if not valid id
        if (!$id) {
            return false;
        }

        // check if expiry date is less than current time
        if (isset($expiresAt) && Carbon::now()->gte($expiresAt)) {
            return false;
        }

        // assign role to user
        $this->roles()->attach($id, ['expires_at' => $expiresAt]);

        $this->flushUserRolesCache();

        return true;
    }

    /**
     * Determine if user has any of the given roles.
     *
     * @param string|array|null $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        return (bool)$this->checkHasRoles(Arr::wrap($roles));
    }
}
