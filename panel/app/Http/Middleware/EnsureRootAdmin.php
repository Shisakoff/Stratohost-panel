<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 1 has no non-admin users yet - every Application API route is
 * admin-only, gated on the same root_admin flag CreateNodeCommand's
 * eventual web equivalent will require.
 */
class EnsureRootAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->root_admin) {
            abort(403, 'This action requires an administrator account.');
        }

        return $next($request);
    }
}
