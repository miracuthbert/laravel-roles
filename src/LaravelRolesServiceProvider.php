<?php

namespace Miracuthbert\LaravelRoles;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Miracuthbert\LaravelRoles\Console\AssignRole;
use Miracuthbert\LaravelRoles\Helpers\Permissions;
use Miracuthbert\LaravelRoles\Http\Middleware\AbortIfHasNoPermission;
use Miracuthbert\LaravelRoles\Http\Middleware\AbortIfHasNoRole;

class LaravelRolesServiceProvider extends ServiceProvider
{
    private $migrationCount = 5;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-roles.php', 'laravel-roles'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // publish config
        $this->publishes([
            __DIR__ . '/../config/laravel-roles.php' => config_path('laravel-roles.php'),
        ], 'laravel-roles-config');

        if ($this->app->runningInConsole()) {

            // publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/create_permissions_table.php.stub' => $this->migratePath('create_permissions_table'),
                __DIR__ . '/../database/migrations/create_user_permissions_table.php.stub' => $this->migratePath('create_user_permissions_table'),
                __DIR__ . '/../database/migrations/create_roles_table.php.stub' => $this->migratePath('create_roles_table'),
                __DIR__ . '/../database/migrations/create_role_permissions_table.php.stub' => $this->migratePath('create_role_permissions_table'),
                __DIR__ . '/../database/migrations/create_user_roles_table.php.stub' => $this->migratePath('create_user_roles_table'),
                __DIR__ . '/../database/migrations/add_permitable_id_to_user_roles_table.php.stub' => $this->migratePath('add_permitable_id_to_user_roles_table'),
            ], 'laravel-roles-migrations');

            $this->publishes([
                __DIR__ . '/../database/migrations/add_permitable_id_to_user_roles_table.php.stub' => $this->migratePath('add_permitable_id_to_user_roles_table'),
            ], 'laravel-roles-permitable-migrations');


            // commands
            $this->commands([
                AssignRole::class,
            ]);
        }

        // middleware
        $this->registerMiddleware();

        // blade directives
        Blade::if('role', function (...$roles) {
            return auth()->check() && auth()->user()->hasRole($roles);
        });

        // gates
        Permissions::gates();
    }

    /**
     * Register the package middleware.
     * 
     * @return void
     */
    protected function registerMiddleware()
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('permission', AbortIfHasNoPermission::class);
        $router->aliasMiddleware('role', AbortIfHasNoRole::class);
    }

    private function migratePath(string $file): string
    {
        $timeKludge = date('Y_m_d_His', time() - --$this->migrationCount);
        return database_path(
            'migrations/' . $timeKludge . "_$file.php"
        );
    }
}
