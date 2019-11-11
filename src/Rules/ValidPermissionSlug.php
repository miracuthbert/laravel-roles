<?php

namespace Miracuthbert\LaravelRoles\Rules;

use Miracuthbert\LaravelRoles\Models\Permission;
use Illuminate\Contracts\Validation\Rule;

class ValidPermissionSlug implements Rule
{
    use CustomRuleMethods;

    /**
     * Permission type.
     *
     * @var $type
     */
    protected $type;

    /**
     * Permission id or instance.
     *
     * @var null|int|Permission
     */
    protected $model;

    /**
     * Create a new rule instance.
     *
     * @param string $type
     * @param null|int|Permission $model
     * @return void
     */
    public function __construct($type, $model = null)
    {
        $this->type = $type;

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
            return Permission::where('slug', $slug)->where('id', $this->ignore)->count() <= 1;
        }

        return Permission::where('slug', $slug)->count() == 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Permission already exists.';
    }
}
