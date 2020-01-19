<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Str;
use Miracuthbert\LaravelRoles\Models\Role;

trait CanUseRoles
{
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
