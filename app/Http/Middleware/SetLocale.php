<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale', config('app.locale', 'id'));
        if (!in_array($locale, ['id', 'en'], true)) {
            $locale = 'id';
        }
        app()->setLocale($locale);

        return $next($request);
    }
}
