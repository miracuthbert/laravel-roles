<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Miracuthbert\LaravelRoles\Models\Role;
use Illuminate\Support\Collection;

trait HasRoles
{
    /**
     * Determine if the entity is the last Admin of its kind.
     *
     * @param Role $role
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
            }
        } else { // detach all `ADMIN` based roles
            $this->roles()->detach(
                $builder->get()->pluck('id')->toArray()
            );
        }

        return true;
    }

    /**
     * Revoke given role now or at the given time.
     *
     * @param $role
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

        return true;
    }

    /**
     * Assign role to model.
     *
     * @param $role
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

        return true;
    }

    /**
     * Get count of role instance.
     *
     * @param array ...$roles
     * @return bool
     */
    public function hasRole(...$roles)
    {
        return (bool)$this->roles()
            ->whereIn('slug', Arr::wrap($roles))
            ->where('usable', true)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()->toDateTimeString())
            ->count();
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
    protected function parseRoleId($value)
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
    protected function findRole($value)
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
    protected function getRolesIds(array $role)
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
    private function filterRolesCollection(Collection $roles)
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
    private function getWorkableRoles($values)
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
    private function isInstanceOfRoleModel($value): bool
    {
        return $value instanceof Role;
    }
}
