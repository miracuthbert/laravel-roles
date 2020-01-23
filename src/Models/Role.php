<?php

namespace Miracuthbert\LaravelRoles\Models;

use Miracuthbert\LaravelRoles\Models\Traits\RoleTrait;
use Miracuthbert\LaravelRoles\Models\Traits\HasSlug;
use Miracuthbert\LaravelRoles\Models\Traits\PermitableScopes;
use Miracuthbert\LaravelRoles\Permitable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Role extends Model implements Permitable
{
    use SoftDeletes,
        NodeTrait,
        HasSlug,
        RoleTrait,
        PermitableScopes;

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
     * The table to be used for permissions relationship.
     *
     * @var string
     */
    protected $permissions_table = 'role_permissions';

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (($parent = $role->parent)) {
                // disable parent
                $parent->update([
                    'usable' => false
                ]);
            }
        });

        static::updating(function ($role) {
            if ($parent = $role->parent) {
                // disable parent
                $parent->update([
                    'usable' => false
                ]);
            }
        });
    }

    /**
     * Get role type from model.
     *
     * @param $model
     * @return mixed
     */
    public static function getRoleTypeFromModel($model)
    {
        $models = config('laravel-roles.models');

        return array_search($model, $models);
    }

    /**
     * Get the owner of the role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function roleable()
    {
        return $this->morphTo();
    }
}
