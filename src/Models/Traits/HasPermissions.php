<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Carbon;
use Miracuthbert\LaravelRoles\Models\Permission;
use Illuminate\Database\Eloquent\Model;

trait HasPermissions
{
    use CanAccessPermissions;

    /**
     * Boot method for trait.
     */
    public static function bootHasPermissions()
    {
        //
    }

    /**
     * Check if given model has given permission.
     *
     * @param $permission
     * @param null $giver
     * @return bool
     */
    public function hasPermissionTo($permission, $giver = null)
    {
        return $this->hasPermissionThroughRole($permission, $giver) || $this->hasPermission($permission);
    }

    /**
     * Check if model has permission through role.
     *
     * @param $permission
     * @param string|null $giver
     * @return mixed
     */
    public function hasPermissionThroughRole($permission, $giver = null)
    {
        if (isset($giver)) {

            // check if giver is an array
            if (is_array($giver) && count($params = explode(':', $giver)) == 2) {
                list($id, $model) = $params;

                // find model based on id
                $giver = $model::find($id);

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
            $roles = $giver->roles()->whereHas('permissions', function ($query) use ($permission) {
                $query->where('id', $permission->id);
            })->get();

            foreach ($roles as $role) {
                if ($this->roles()->where('roles.usable', true)
                    ->where('slug', $role->slug)
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now())->count()) {
                    return true;
                }
            }

            return false;
        } else {
            if (!($roles = $permission->roles)) {
                return false;
            }

            foreach ($roles as $role) {
                if ($this->roles()->where('roles.usable', true)
                    ->where('slug', $role->slug)
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now())
                    ->count()) {
                    return true;
                }
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
        return (bool)$this->permissions()
            ->where('name', $permission->name)
            ->orWhere('slug', $permission->slug)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', Carbon::now())
            ->count();
    }

    /**
     * Delete all permissions for the given model.
     *
     * @return bool
     */
    public function deletePermissions()
    {
        $this->permissions()->detach();

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

        $this->permissions()->attach($this->getWorkablePermissions($permission), [
            'expires_at' => $expiresAt
        ]);

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

        return true;
    }

    /**
     * Get all valid permissions.
     *
     * @return mixed
     */
    public function validPermissions()
    {
        return $this->permissions()
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->get();
    }

    /**
     * Get an array of mapped permissions.
     *
     * @param $ids
     * @param $existingPermissions
     * @return array
     */
    protected function getMappedPermissions($ids, $existingPermissions)
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
}
