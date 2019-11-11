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
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        if (!optional($request->user())->can($permission)) {
            abort(config('laravel-roles.middleware.abort_code', 403));
        }

        return $next($request);
    }
}
