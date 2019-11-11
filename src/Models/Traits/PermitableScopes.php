<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

use Miracuthbert\LaravelRoles\Permitable;
use Illuminate\Database\Eloquent\Builder;

trait PermitableScopes
{
    /**
     * Scope query to include only `active` records.
     *
     * @param Builder $builder
     * @param string $column
     * @return Builder
     */
    public function scopeActive(Builder $builder, $column = 'usable')
    {
        return $builder->where($column, true);
    }

    /**
     * Scope query to include only the matching `type` of records.
     *
     * @param Builder $builder
     * @param string $type
     * @param string $column
     * @return Builder
     */
    public function scopeType(Builder $builder, $type = Permitable::ADMIN, $column = 'type')
    {
        return $builder->where($column, $type);
    }

    /**
     * Scope query to include only the records matching given `value`.
     *
     * @param Builder $builder
     * @param string $value
     * @return Builder
     */
    public function scopeSearch(Builder $builder, $value)
    {
        if ($value == null) {
            return $builder;
        }

        return $builder->where('name', 'LIKE', "%" . $value . "%")
            ->orWhere('slug', 'LIKE', "%" . $value . "%");
    }
}
