<?php

namespace Miracuthbert\LaravelRoles\Http\Middleware;

use Closure;

class AbortIfHasNoRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $role
     * @param null $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $role, $permission = null)
    {
        if (!optional(request()->user())->hasRole($role)) {
            abort(config('laravel-roles.middleware.abort_code', 403));
        }

        if (isset($permission) && !$request->user()->can($permission)) {
            abort(config('laravel-roles.middleware.abort_code', 403));
        }

        return $next($request);
    }
}
