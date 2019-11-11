<?php

namespace Miracuthbert\LaravelRoles\Tests;

use Miracuthbert\LaravelRoles\Models\Permission;
use Miracuthbert\LaravelRoles\Models\Role;

class RoleModelUsageTest extends TestCase
{
    /**
     * @test
     */
    public function can_add_permissions_to_role()
    {
        $role = factory(Role::class)->create();

        // seed permissions table
        $count = 3;
        $permissions = $this->seedPermissions($count);

        // add permissions to role
        $role->addPermissions($permissions);

        // assert role has `n` permissions
        $this->assertCount($count, $role->permissions);

        foreach ($permissions as $permission) {
            $this->assertContains($permission->slug, $role->permissions->pluck('slug'));
        }
    }

    /**
     * @test
     */
    public function can_remove_permissions_from_role()
    {
        $role = factory(Role::class)->create();

        // seed permissions table
        $count = 3;
        $permissions = $this->seedPermissions($count);

        // add permissions to role
        $role->addPermissions($permissions);

        // remove permissions to role
        $role->detachPermissions($permissions->take(2)->pluck('id')->all());

        $role = $role->fresh();

        // assert role has `n` permissions
        $this->assertCount(1, $role->permissions);
    }

    /**
     * @test
     */
    public function can_sync_permissions_to_role()
    {
        $role = factory(Role::class)->create();

        // seed permissions table
        $count = 3;
        $permissions = $this->seedPermissions($count);

        // add permissions to role
        $role->addPermissions($permissions);

        $n = $count + 2;

        // seed more permissions
        $morePermissions = $this->seedPermissions($n);

        // merge permissions
        $permissions = $permissions->merge($morePermissions);

        // sync permissions in role
        $role->syncPermissions($permissions);

        // get a fresh collection of role's permissions
        $freshPermissions = $role->permissions()->pluck('slug')->all();

        // assert role has `n` permissions
        $this->assertCount($permissions->count(), $freshPermissions);

        foreach ($permissions as $permission) {
            $this->assertContains($permission->slug, $freshPermissions);
        }
    }

    /**
     * @test
     */
    public function can_sync_unfiltered_permissions_to_role()
    {
        $role = factory(Role::class)->create();

        // seed permissions table
        $count = 3;
        $permissions = $this->seedPermissions($count);

        // add permissions to role
        $role->addPermissions($permissions);

        $n = $count + 2;

        // seed more permissions
        $morePermissions = $this->seedPermissions($n);

        // merge permissions
        $unfilteredPermissions = array_merge(
            $permissions->pluck('slug')->all(),
            $morePermissions->pluck('id')->all()
        );

        // sync permissions in role
        $role->syncPermissions($unfilteredPermissions);

        // get a fresh collection of role's permissions
        $freshPermissions = $role->permissions()->pluck('slug')->all();

        // assert role has `n` permissions
        $this->assertCount(count($unfilteredPermissions), $freshPermissions);

        foreach ($permissions as $permission) {
            $this->assertContains($permission->slug, $freshPermissions);
        }
    }

    /**
     * Seed permissions table.
     *
     * @param $count
     * @return mixed
     */
    protected function seedPermissions($count = 3)
    {
        return factory(Permission::class, $count)->create();
    }
}
