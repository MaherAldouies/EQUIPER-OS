<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level enforcement of the Ontology's Permission entity
 * ("every permission check defaults to deny unless explicitly granted",
 * PRD F2). Usage: ->middleware('permission:product.manage_category').
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        abort_unless(
            $request->user()?->hasPermission($permissionKey) ?? false,
            403,
            "Missing required permission: {$permissionKey}"
        );

        return $next($request);
    }
}
