<?php

namespace Miracuthbert\LaravelRoles\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Users
{
    /**
     * The cache key for the user model.
     *
     * @param mixed|null $model
     * @return string
     */
    public static function userModelCacheKey($model = null)
    {
        $key = 'user';

        $types = config('laravel-roles.users.types');

        if ($types) {
            $user = array_search($model, Arr::wrap($types));

            if ($user) {
                $key = Str::slug($user, '_');
            }
        }

        return $key;
    }
}
