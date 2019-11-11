<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Support\Carbon;
use Miracuthbert\LaravelRoles\Models\Permission;

trait CanUsePermissions
{
    use CanAccessPermissions;

    /**
     * Boot method for trait.
     */
    public static function bootCanUsePermissions()
    {
        //
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
