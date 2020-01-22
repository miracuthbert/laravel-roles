<?php

namespace Miracuthbert\LaravelRoles\Models\Traits;

trait LaravelRolesUserTrait
{
    use HasRoles,
        HasPermissions,
        UserScopes;
}
