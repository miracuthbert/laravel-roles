# Laravel Roles

An Eloquent roles and permissions package for Laravel 5.8 and up.

## Installation

To can install the package via composer:

```
composer require miracuthbert/laravel-roles
```

## Setup

The package takes advantage of Laravel Auto-Discovery, so it doesn't require you to manually add the ServiceProvider.

If you don't use auto-discovery, add the ServiceProvider to the providers array in config/app.php

```php
Miracuthbert\LaravelRoles\LaravelRolesServiceProvider::class
```

### Migrations

You first need to publish the packages migrations:

```
php artisan vendor:publish --tag=laravel-roles-migrations
```

For those who had published migrations earlier use command(s) below to publish updated changes:

```
php artisan vendor:publish --tag=laravel-roles-permitable-migrations
```

Then run the migrate command:

```
php artisan migrate
```

### Seeding

By default the package includes a seeder for roles and permissions, `RolesAndPermissionsTableSeeder`.

You can add it to your `DatabaseSeeder` or use the command below to run it in isolation.

```
php artisan db:seed --class=RolesAndPermissionsTableSeeder
```

> See the [usage](#usage) section on how to assign a role to a user via the console.

## Configuration

To customize the package usage, copy the package config to your local config by using the publish command:

```
php artisan vendor:publish --tag=laravel-roles-config
```

The package includes configuration for:  

### Role Sharing

`allow_shared_roles` key enables the package to query for roles that can be shared by [models](#models).

Example, an `admin` role for `Team` model can be shared and easily managed by the app admin, compared to
a `manager` role that may vary between teams.

### User Model

The default user model is `App\Models\User::class` the default namespace of `User` model.

You can change the default user model by changing `model` key under `users` in the config file.

> See the [usage](#usage) section on how to enable roles and permissions on the User model.

### Middleware

Under middleware you can configure the `abort_code`, which by default is `403`.

### Permitable Types

You can setup the types of role and permissions allowed to be created within your app.
 
This can be useful in a multi-tenant app for filtering out `Admin` and `Tenant` based permissions and roles. 

For example if your application has `teams` and each member performs unique tasks you can define a permission or role 
type `team` under `permitables` in the config file which can be used to filter out only `team` permissions or roles.

```php
'team' => 'Team'
```

> Permitable types for non admin roles need to be linked to added as a key in the models array in the config file. 

### Models

You can also map `permitables` types (mentioned above) to related models.

```php
'team' => \App\Models\Team::class
```

> Each model can only be related to a single permitable type.

## Usage

To start assigning roles and permissions to users, you must first add the `LaravelRolesUserTrait` trait to the `User` model.

> The User model should match the one indicated in the package's config file. See [configuration](#configuration).

### Assigning a Role

#### Basic
To assign a role to a user you can call the `assignRole` method on a User model instance. 

The `assignRole` method accepts a `role` (Role model instance), `expiresAt` a date which is optional and
 `giver` an instance Eloquent Model among `models` registered in [configuration](#models) which is optional.

```php
$user->assignRole($role, $expiresAt, $giver);
```

> The expiresAt value must be a future date.

> giver value can omitted when role assigned belongs to a registered model
#### Via Console

To assign role via console use command below, passing in a registered User's `email` and a valid Role `slug`.

For example a user with email `johndoe@example.org` and a Role named `Admin Root` with slug `admin-root` will be:

```
php artisan role:assign johndoe@example.org admin-root
```

> You can seed the roles and permissions table with the seeder that comes with the package. See [Seeding](#seeding).

### Revoking a Role

Revoking a role is the same as assign, where the `revokeRoleAt` method is called on a User model instance. 

The method accepts a `role` (Role model instance) and `expiresAt` a date which is optional.

> If the expiresAt date is not given the role will be revoked immediately.

```php
$user->revokeRoleAt($role, $expiresAt);
```

### Revoking all Roles

To revoke all roles from a user, call the `revokeRoles` method on a User model instance. 

The method accepts an optional array of `roles` (or a collection of Role model instances).

> If no value is passed to method, the user's valid roles will be revoked.

_Note:_ Revoking a role just set's the expired timestamp, not deleting the history of user's roles.

```php
$user->revokeRoles(); 

// with an array of values
$user->revokeRoles(['admin-root', 'admin-basic']);
```

### Detaching all Roles

To detach all roles from a user, call the `detachRoles` method on a User model instance. 

The method accepts an optional array of `roles` (or a collection of Role model instances).

> Detaching roles from a user with completely delete the history of user's roles.

```php
$user->detachRoles(); 

// with an array of values
$user->detachRoles(['admin-root', 'admin-basic']);
```

## Authorization

There are various ways you can authorize a user using the package:

### Roles

To check a user has a valid role use the `slug` of a valid Role model. 

> The role must be `usable` (active).

#### Via User Model

```php
if($user->hasRole('admin-root')) {
    // user can do something as admin
}
```

Checking for multiple roles:

```php
if($user->hasRole(['admin-root', 'manager', 'editor'])) {
    // user can do something as admin
}
```

Or

```php
if($user->hasRole('admin-root', 'manager', 'editor')) {
    // user can do something as admin
}
```
#### Via Blade Helpers

```blade
@role('admin-root')
    <!-- The user can do root admin stuff -->
@endrole
```

#### Middleware

You can user the `role` middleware to check if user has access. 

It accepts a role `slug` and permission `slug` or `name`.

##### In Routes

```php
// Just role
Route::middleware(['role:admin-root'])->get('/admin/logs');

// Via role and permission 
Route::middleware(['role:admin-root,delete-admins'])->delete('/admin/roles/editors/revoke');
```

##### In Controller

```php
public function __construct() {
    // Via permission name `browse admin`
    $this->middleware(['role:admin-root']);
    
    // Via role and permission (slug or name)
    $this->middleware(['role:admin-root,delete-admins']);
}
```

> See permissions section to learn how to authorize a user via permissions. 

### Permissions

To check a user has access via permission use a `slug` or `name` of a valid Permission model. 

eg. a permission named `browse admin` can be passed as `browse-admin` or `browse admin`.

> The permission must be `usable` (active).

An additional parameter `giver` is accepted, if you have to check for permissions based on the model that assigned it.

For example, to check a user has a permission through a role of type `team`:
 
- You can be pass an instance of `Team` model 
- Or pass a type with a corresponding id: `team:1`. 

Whether the `id` is  a `slug` or a basic `id`, it will be resolved by the package.

> When passing a type make sure it has a corresponding model registered under the package's config in the `models` key.

#### Via Gates

```php
if(Gate::allows('browse-admin')) {
    // do something
}

// With giver
if(Gate::allows('browse-admin', $team)) {
    // do something
}
```

```php
if(Gate::denies('browse-admin')) {
    return abort(403);
}

// With giver
if(Gate::denies('browse-admin', $team)) {
    return abort(403);
}
```

#### Via User model
```php
if($user->can('assign roles')) {
    // do something
}

// With giver
if($user->can('assign roles', $team)) {
    // do something
}
```

#### Via Blade Helpers

```blade
@can('impersonate user')
    <!-- The user can impersonate another user -->
@endcan

// With giver
@can('impersonate user', $team)
    <!-- The user can impersonate another user -->
@endcan
```

```blade
@cannot('impersonate user')
    <!-- The user can't impersonate another user -->
@endcannot

// With giver
@cannot('impersonate user', $team)
    <!-- The user can't impersonate another user -->
@endcannot
```

#### Middleware

Using middleware there are two ways:

##### In Routes

```php

// Via permission name `browse admin`
Route::middleware(['permission: browse admin'])->get('/admin/dashboard');

// Via permission slug `browse-admin`
Route::middleware(['permission: browse-admin'])->get('/admin/dashboard');
```


##### In Controller

```php
public function __construct() {
    // Via permission name `browse admin`
    $this->middleware(['permission: browse admin']);
    
    // Via permission slug `browse-admin`
    $this->middleware(['permission: browse-admin']);

    // Via permission name `browse admin` with giver, the id should be fetched from the request or dynamically resolved
    $this->middleware(['permission: browse admin, team:' . $request->team]);
    
    // Via permission slug `browse-admin` with giver, the id should be fetched from the request or dynamically resolved
    $this->middleware(['permission: browse-admin, team:' . $request->team]);
}
```

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to Cuthbert Mirambo via [miracuthbert@gmail.com](mailto:miracuthbert@gmail.com). All security vulnerabilities will be promptly addressed.

## Credits

- [Cuthbert Mirambo](https://github.com/miracuthbert)

## License

The project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).