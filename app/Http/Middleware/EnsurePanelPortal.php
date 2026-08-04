<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelPortal
{
    /**
     * Prevent a user from opening the shared panel through another role's URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $expectedPortal = $user?->isTutor() ? 'tutor' : 'admin';
        $requestedPortal = (string) $request->route('portal');

        if ($requestedPortal !== $expectedPortal) {
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return $this->redirectToExpectedPortal($request, $expectedPortal);
            }

            abort(403, 'Portal tidak sesuai dengan role pengguna.');
        }

        // The dynamic URL segment is only a portal selector. Removing it keeps
        // implicit model binding aligned with the controller action signature.
        $request->route()->forgetParameter('portal');

        return $next($request);
    }

    private function redirectToExpectedPortal(Request $request, string $expectedPortal): RedirectResponse
    {
        $route = $request->route();
        $parameters = $route->parameters();
        $parameters['portal'] = $expectedPortal;

        return redirect()->route($route->getName(), $parameters + $request->query());
    }
}
