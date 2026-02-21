<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->input('company_id') ?: $request->header('X-Company-Id');

        if (! $companyId) {
            return $next($request);
        }

        if (! $request->user()->companies()->where('companies.id', $companyId)->exists()) {
            abort(403, __('messages.unauthorized_company'));
        }

        return $next($request);
    }
}
