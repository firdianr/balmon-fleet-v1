<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleAdminApp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (method_exists($user, 'isAdminApp') && $user->isAdminApp()) {
                return $next($request);
            }
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses sebagai Admin Aplikasi.');
    }
}
