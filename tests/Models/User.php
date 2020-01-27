<?php

namespace Miracuthbert\LaravelRoles\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Miracuthbert\LaravelRoles\Models\Traits\LaravelRolesUserTrait;

class User extends Authenticatable
{
    use LaravelRolesUserTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
