<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackageAcces;
use App\Models\Payment;
use App\Models\Tryout;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Count data sesuai dengan view
        $count_user = User::where('role', 'user')->count();
        $count_amount = Payment::where('status', 'success')->sum('amount');
        $count_tryout = Tryout::count();
        $count_class = ClassModel::count();

        // Recent users (5 terbaru)
        $users = User::where('role', 'user')
            ->latest()
            ->limit(5)
            ->get();

        // Recent payments (5 terbaru)
        $payments = Payment::with(['user'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.pages.dashboard', compact(
            'count_user',
            'count_amount',
            'count_tryout',
            'count_class',
            'users',
            'payments'
        ));
    }
}
