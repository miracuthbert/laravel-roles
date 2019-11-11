<?php

namespace Miracuthbert\LaravelRoles\Rules;

use Miracuthbert\LaravelRoles\Models\Role;
use Illuminate\Contracts\Validation\Rule;

class ValidRoleSlug implements Rule
{
    use CustomRuleMethods;

    /**
     * Role type.
     *
     * @var $type
     */
    protected $type;

    /**
     * Role id or instance.
     *
     * @var null|int|Role
     */
    protected $model;

    /**
     * Create a new rule instance.
     *
     * @param string $type
     * @param $parentId
     * @param null|int|Role $model
     * @return void
     */
    public function __construct($type, $parentId, $model = null)
    {
        $this->type = $type;

        $this->parent = $this->parent($parentId);
        $this->model = $this->ignore($model);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $slug = $this->getSlug($value);

        if (isset($this->ignore)) {
            return Role::where('slug', $slug)->where('id', $this->ignore)->count() <= 1;
        }

        return Role::where('slug', $slug)->count() == 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Role already exists.';
    }

    /**
     * Get parent.
     *
     * @param $parentId
     * @return mixed
     */
    public function parent($parentId)
    {
        return $this->parent = Role::find($parentId);
    }
}
