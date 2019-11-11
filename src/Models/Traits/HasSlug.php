<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * The field to build slug from.
     *
     * @var string
     */
    protected static $slugFrom = 'name';

    /**
     * The field name for the model's `slug`.
     *
     * @var string
     */
    protected static $slugField = 'slug';

    /**
     * Set whether route key name will be slug.
     *
     * @var bool
     */
    protected $routeBySlug = true;

    /**
     * Boot method for trait.
     */
    public static function bootHasSlug()
    {
        static::creating(function ($model) {

            // check if no slug passed
            if (!$model->{static::$slugField}) {
                // generate model prefix
                $prefix = $model->parent ? $model->parent->{static::$slugFrom} . ' ' : '';

                // generate model slug
                $model->{static::$slugField} = Str::slug($prefix . $model->{static::$slugFrom});
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->routeBySlug ? 'slug' : parent::getRouteKeyName();
    }
}
