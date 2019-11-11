<?php

use Miracuthbert\LaravelRoles\Helpers\Roles;
use Miracuthbert\LaravelRoles\Models\Permission;
use Miracuthbert\LaravelRoles\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolesAndPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // pull default roles
        $roles = Roles::roles();

        foreach ($roles as $slug => $data) {

            // get or create role
            $role = Role::firstOrCreate(['slug' => $slug], [
                    'name' => $data['name'],
                    'type' => $data['type']
                ]
            );

            // setup parent
            if (isset($data['parent'])) {
                $parent = Role::where('slug', $data['parent'])->first();

                // append role to group
                $parent->appendNode($role);
            }

            // create permissions from role
            foreach ($data['permissions'] as $permission) {
                $permissionSlug = Str::slug(($name = $permission['name']));

                $model = Permission::firstOrCreate(['slug' => $permissionSlug], [
                        'name' => $name,
                        'type' => $permission['type'],
                        'slug' => $permissionSlug,
                    ]
                );

                // add permission to role
                $role->addPermissions($model);
            }
        }
    }
}
