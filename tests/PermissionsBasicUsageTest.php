<?php

namespace Miracuthbert\LaravelRoles\Tests;

use Miracuthbert\LaravelRoles\Models\Permission;
use Miracuthbert\LaravelRoles\Tests\Models\User;

class PermissionsBasicUsageTest extends TestCase
{
    /**
     * @test
     */
    public function can_assign_permissions_to_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed permissions
        $count = 3;
        $permissions = $this->seedPermissions($count);

        // assert permissions exists
        $this->assertCount($count, $permissions);

        $slugs = $permissions->pluck('slug')->all();

        // assert permissions assigned
        $this->assertTrue($user->assignPermissions($slugs));

        $assigned = $user->permissions->pluck('slug')->all();

        // check if user has given permissions
        foreach ($slugs as $slug) {
            $this->assertContains($slug, $assigned);
        }
    }

    /**
     * @test
     */
    public function can_assign_permission_to_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed permissions
        $count = 1;
        $permissions = $this->seedPermissions($count);

        $slugs = $permissions->pluck('slug')->all();

        // assert permissions assigned
        $this->assertTrue(
            $user->assignPermission($permissions->first())
        );

        // get user permissions
        $assigned = $user->permissions->pluck('slug')->all();

        // assert user has `n` permissions
        $this->assertCount($count, $assigned);

        // check if user has given permissions
        foreach ($slugs as $slug) {
            $this->assertContains($slug, $assigned);
        }
    }

    /**
     * @test
     */
    public function can_revoke_permission_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed permissions
        $permissions = $this->seedPermissions(3);

        $slugs = $permissions->pluck('slug')->all();

        // assert permissions assigned
        $this->assertTrue($user->assignPermissions($slugs));

        // revoke last permission
        $this->assertTrue(
            $user->revokePermissionAt($permissions->last())
        );

        // get user permissions
        $assigned = $this->activeUserPermissions($user);

        $this->assertCount(2, $assigned);

        // check if user has given permissions
        foreach (collect($slugs)->take(2) as $slug) {
            $this->assertContains($slug, $assigned->pluck('slug')->all());
        }
    }

    /**
     * @test
     */
    public function can_revoke_all_permissions_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed permissions
        $permissions = $this->seedPermissions(3);

        $slugs = $permissions->pluck('slug')->all();

        // assert permissions assigned
        $this->assertTrue($user->assignPermissions($slugs));

        // revoke permissions
        $this->assertTrue($user->revokePermissions());

        // get user permissions
        $assigned = $this->activeUserPermissions($user);

        $this->assertCount(0, $assigned);
    }

    /**
     * @test
     */
    public function can_revoke_given_permissions_from_user()
    {
        // create user
        $user = factory(User::class)->create();

        // seed permissions
        $permissions = $this->seedPermissions(3);

        $slugs = $permissions->pluck('slug')->all();

        // assert permissions assigned
        $this->assertTrue($user->assignPermissions($slugs));

        // revoke permissions
        $this->assertTrue($user->revokePermissions(collect($slugs)->take(2)->all()));

        // get user permissions
        $assigned = $this->activeUserPermissions($user);

        $this->assertCount(1, $assigned);
    }

    /**
     * @test
     */
    public function non_existing_permissions_are_ignored_on_assign()
    {
        // create user
        $user = factory(User::class)->create();

        // seed permissions
        $permissions = $this->seedPermissions(3);

        $slugs = $permissions->pluck('slug')->all();

        // assert permissions assigned
        $this->assertTrue(
            $user->assignPermissions(
                array_merge($slugs, ['download-reports', 'browse-reports', 100])
            )
        );

        // get user permissions
        $assigned = $this->activeUserPermissions($user);

        // assert only seeded permissions added
        $this->assertCount(3, $assigned);
    }

    /**
     * Seed default roles and permissions.
     */
    protected function seedRoles(): void
    {
        $this->seed(\Miracuthbert\LaravelRoles\Database\Seeders\RolesAndPermissionsTableSeeder::class);
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

    /**
     * Get user's valid permissions.
     *
     * @param $user
     * @return mixed
     */
    protected function activeUserPermissions($user)
    {
        return $user->validPermissions();
    }
}
