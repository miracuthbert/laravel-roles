<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use Miracuthbert\LaravelRoles\Models\Permission;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Permission::class, function (Faker $faker) {
    return [
        'name' => $name = $faker->unique()->safeColorName,
        'slug' => Str::slug($name),
        'type' => \Miracuthbert\LaravelRoles\Permitable::ADMIN,
        'usable' => true,
        'description' => $faker->paragraph,
    ];
});
