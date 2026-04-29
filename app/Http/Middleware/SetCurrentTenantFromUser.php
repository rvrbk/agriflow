<?php

namespace App\Http\Middleware;

use App\Models\Corporation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenantFromUser
{
    /**
     * Set the current tenant from the authenticated user's corporation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->corporation_id) {
            $tenant = Corporation::query()->find($user->corporation_id);

            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        return $next($request);
    }
}