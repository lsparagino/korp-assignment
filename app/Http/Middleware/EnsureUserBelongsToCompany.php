<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->input('company_id') ?: $request->header('X-Company-Id');

        if (! $companyId) {
            return $next($request);
        }

        if (! $request->user()->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, 'Unauthorized access to company.');
        }

        return $next($request);
    }
}
