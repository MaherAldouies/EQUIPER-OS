<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Populates request->attributes['organization'] from the authenticated
 * user, which every Web controller reads instead of hardcoding
 * Organization::first() — this is the seam the Business Ontology's
 * multi-tenant future plugs into without touching controller code.
 */
class SetCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('organization', $request->user()?->organization);

        return $next($request);
    }
}
