<?php

namespace Miracuthbert\LaravelRoles\Http\Middleware;

use Closure;

class AbortIfHasNoPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $permission
     * @param mixed $giver
     * @return mixed
     */
    public function handle($request, Closure $next, $permission, $giver = null)
    {
        if (!optional($request->user())->can($permission, $giver)) {
            abort(config('laravel-roles.middleware.abort_code', 403));
        }

        return $next($request);
    }
}
