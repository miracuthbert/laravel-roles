<?php

namespace Miracuthbert\LaravelRoles\Models;

use Miracuthbert\LaravelRoles\Models\Traits\HasSlug;
use Miracuthbert\LaravelRoles\Models\Traits\PermissionTrait;
use Miracuthbert\LaravelRoles\Models\Traits\PermitableScopes;
use Miracuthbert\LaravelRoles\Permitable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model implements Permitable
{
    use SoftDeletes,
        HasSlug,
        PermitableScopes,
        PermissionTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'usable',
    ];

    /**
     * Format the permission name.
     *
     * @param $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }

    /**
     * Get role type from model.
     *
     * @param $model
     * @return mixed
     */
    public static function getPermissionTypeFromModel($model)
    {
        $models = config('laravel-roles.models');

        return array_search($model, $models);
    }
}
