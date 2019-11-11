<?php

namespace Miracuthbert\LaravelRoles\Http\Requests;

use Miracuthbert\LaravelRoles\Rules\ValidRoleSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'max:250',
                new ValidRoleSlug($this->input('type'), $this->input('parent_id')),
            ],
            'description' => ['nullable', 'max:255'],
            'type' => ['required', Rule::in(array_keys(config('laravel-roles.permitables')))],
            'usable' => ['nullable', 'boolean'],
            'parent_id' => [
                'nullable',
                Rule::exists('roles', 'id')
            ],
            'permissions.*' => [
                'required',
                Rule::exists('permissions', 'id')->where('usable', true)
            ],
        ];
    }
}
