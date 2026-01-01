<?php

namespace App\Http\Controllers;

use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        // Share sidebar data with all views
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $sidebarPackages = UserPackageAcces::where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>', Carbon::now());
                    })
                    ->with(['package' => function ($query) {
                        $query->where('status', 'active');
                    }])
                    ->get()
                    ->filter(function ($access) {
                        return $access->package !== null;
                    });

                $view->with('sidebarPackages', $sidebarPackages);
            }
        });
    }
}
