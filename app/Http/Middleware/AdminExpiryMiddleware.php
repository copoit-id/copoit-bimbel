<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminExpiryMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin_demo') {
                if (!$user->admin_expires_at) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Akses admin demo belum memiliki batas waktu.'], 403);
                    }

                    return redirect()->route('login')->with('error', 'Akses admin demo belum memiliki batas waktu. Silakan hubungi super admin.');
                }

                $now = Carbon::now('Asia/Jakarta');
                if ($now->gte($user->admin_expires_at)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Akses admin sudah berakhir.'], 403);
                    }

                    return redirect()->route('login')->with('error', 'Akses admin sudah berakhir. Silakan hubungi super admin.');
                }
            }
        }

        return $next($request);
    }
}
