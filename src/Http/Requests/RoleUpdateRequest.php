<?php

namespace Miracuthbert\LaravelRoles\Http\Requests;

use Miracuthbert\LaravelRoles\Rules\ValidRoleSlug;

class RoleUpdateRequest extends RoleStoreRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'name' => [
                'required',
                'max:250',
                new ValidRoleSlug($this->input('type'), $this->input('parent_id'), $this->role),
            ],
        ]);
    }
}
