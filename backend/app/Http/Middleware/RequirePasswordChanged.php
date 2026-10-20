<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->must_change_password && ! $request->routeIs('api.v1.auth.me', 'api.v1.auth.logout', 'api.v1.auth.password.update'), 403, 'Anda wajib mengganti sandi sementara.');

        return $next($request);
    }
}
