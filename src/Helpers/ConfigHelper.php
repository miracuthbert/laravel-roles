<?php

namespace Miracuthbert\LaravelRoles\Helpers;

use Illuminate\Support\Facades\Config;

class ConfigHelper
{
    /**
     * Determine if caching is allowed.
     *
     * @return mixed
     */
    public static function cacheEnabled()
    {
        return Config::get('laravel-roles.cache.enabled', true);
    }

    /**
     * The time in seconds before cache expiry.
     *
     * @return mixed
     */
    public static function cacheExpiryTime()
    {
        return Config::get('laravel-roles.cache.expiration_time', 3600);
    }
}
