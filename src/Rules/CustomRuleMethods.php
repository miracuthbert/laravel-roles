<?php

namespace Miracuthbert\LaravelRoles\Rules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait CustomRuleMethods
{
    /**
     * Parent relation instance.
     *
     * @var null|Role
     */
    protected $parent;

    /**
     * Role id to ignore.
     *
     * @var null|int
     */
    protected $ignore;

    /**
     * Get value to be ignored.
     *
     * @param $id
     * @return mixed
     */
    protected function ignore($id)
    {
        if ($id instanceof Model) {
            $id = $id->id;
        }

        $this->ignore = $id;
    }

    /**
     * Get slug from value.
     *
     * @param $value
     * @return string
     */
    protected function getSlug($value): string
    {
        $prefix = $this->parent ? $this->parent->name : '';

        $slug = Str::slug($prefix . ' ' . $value);

        return $slug;
    }
}
