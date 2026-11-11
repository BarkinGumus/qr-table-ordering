<?php

namespace App\Http\Middleware;

use App\Tenancy\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * For logged-in staff: fixes the request to the user's own restaurant, and
 * signs the user out if the account or the restaurant has been switched off
 * since they logged in.
 */
class SetTenantFromUser
{
    public function __construct(private Tenant $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            if (! $user->mayUseTheApp()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => __('ui.login.failed')]);
            }

            if ($user->restaurant !== null) {
                $this->tenant->set($user->restaurant);
            }
        }

        return $next($request);
    }
}
