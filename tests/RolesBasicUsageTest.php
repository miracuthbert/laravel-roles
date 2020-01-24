<?php

namespace Miracuthbert\LaravelRoles\Tests;

use Miracuthbert\LaravelRoles\Models\Role;
use Miracuthbert\LaravelRoles\Permitable;
use Miracuthbert\LaravelRoles\Tests\Models\User;
use RolesAndPermissionsTableSeeder;

class RolesBasicUsageTest extends TestCase
{
    /**
     * @test
     */
    public function can_assign_role_to_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find role
        $role = Role::where('slug', 'admin-root')->where('usable', true)->first();

        // assert role exists
        $this->assertNotEmpty($role);

        // assert role assigned
        $this->assertTrue($user->assignRole($role), $user->name . ' has been assigned ' . $role->name . ' role');
    }

    /**
     * @test
     */
    public function can_assign_timed_role_to_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find role
        $role = Role::where('slug', 'admin-root')->where('usable', true)->first();

        // assert role exists
        $this->assertNotEmpty($role);

        $date = now()->addDay();

        // assert role assigned
        $this->assertTrue($user->assignRole($role, $date));

        $user->refresh();

        // assert date matches
        $this->assertTrue($date->eq($user->roles->where('slug', $role->slug)->first()->pivot->expires_at));
    }

    /**
     * @test
     */
    public function can_revoke_role_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find role
        $role = Role::where('slug', 'admin-root')->where('usable', true)->first();

        // assert role exists
        $this->assertNotEmpty($role);

        // assert role assigned
        $this->assertTrue($user->assignRole($role));

        // assert role revoked
        $this->assertTrue($user->revokeRoleAt($role));
    }

    /**
     * @test
     */
    public function can_revoke_role_in_future_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find role
        $role = Role::where('slug', 'admin-root')->where('usable', true)->first();

        // assert role exists
        $this->assertNotEmpty($role);

        $date = now()->addDay();

        // assert role assigned
        $this->assertTrue($user->assignRole($role, $date));

        // assert role will be revoked in future
        $this->assertTrue($user->hasRole($role->slug));
    }

    /**
     * @test
     */
    public function can_revoke_all_roles_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find a parent role
        $parent = Role::with(['children'])->where('slug', 'admin')->first();

        // assert role exists
        $children = $parent->children;

        $this->assertNotEmpty($children);

        // assert role assigned
        $children->each(function ($role) use ($user) {
            $this->assertTrue($user->assignRole($role));
        });

        // revoke user's default roles
        $this->assertTrue($user->revokeRoles($children));

        // assert user's roles have been revoked
        $this->assertCount(0, $user->roles()->where('type', Permitable::ADMIN)
                ->whereIn('slug', $children->pluck('slug')->all())
                ->where('expires_at', '>', now())->get()
        );
    }

    /**
     * @test
     */
    public function can_detach_roles_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find a parent role
        $parent = Role::with(['children'])->where('slug', 'admin')->first();

        // assert role exists
        $children = $parent->children;

        $this->assertNotEmpty($children);

        // assert role assigned
        $children->each(function ($role) use ($user) {
            $this->assertTrue($user->assignRole($role));
        });

        // delete user's roles history
        $this->assertTrue($user->detachRoles());

        $user->refresh();

        // assert no roles available
        $this->assertCount(0, $user->roles->where('type', Permitable::ADMIN)->all());
    }

    /**
     * @test
     */
    public function user_role_is_valid()
    {
        // create user
        $user = factory(User::class)->create();

        // seed roles
        $this->seedRoles();

        // find role
        $role = Role::where('slug', 'admin-root')->where('usable', true)->first();

        // assert role exists
        $this->assertNotEmpty($role);

        // assert role assigned
        $this->assertTrue($user->assignRole($role));

        // assert role will be revoked in future
        $this->assertTrue($user->hasRole($role->slug));
    }

    /**
     * Seed default roles and permissions.
     */
    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsTableSeeder::class);
    }
}
