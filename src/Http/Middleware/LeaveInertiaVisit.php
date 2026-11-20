<?php

namespace WgVn\SettingsUi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns an Inertia visit to a settings page into a full page load.
 *
 * A host whose login is an Inertia form redirects to the intended settings
 * page; the XHR follows that redirect with its X-Inertia header, and Inertia
 * shows the plain HTML it gets back in a dialog where the form cannot save.
 * A 409 with X-Inertia-Location makes Inertia load the page itself instead.
 */
class LeaveInertiaVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('X-Inertia')) {
            return response('', 409, ['X-Inertia-Location' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
