<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index()
    {
        // Get free packages (events) - same structure as package purchase but only free ones
        $kelasPackages = Package::where('type_package', 'bimbel')
            ->where('status', 'active')
            ->where('price', 0) // Only free packages
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->get();

        $tryoutPackages = Package::where('type_package', 'tryout')
            ->where('status', 'active')
            ->where('price', 0) // Only free packages
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->get();

        $sertifikasiPackages = Package::where('type_package', 'sertifikasi')
            ->where('status', 'active')
            ->where('price', 0) // Only free packages
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->get();

        return view('user.pages.event.index', compact(
            'kelasPackages',
            'tryoutPackages',
            'sertifikasiPackages'
        ));
    }

    public function joinEvent($package_id)
    {
        $package = Package::where('package_id', $package_id)
            ->where('price', 0)
            ->where('status', 'active')
            ->firstOrFail();

        // Check if user already joined
        $existing = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $package_id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah terdaftar di event ini'
            ], 400);
        }

        // Give free access - same as free package in PackageController
        UserPackageAcces::create([
            'user_id' => Auth::id(),
            'package_id' => $package_id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(30), // Default 30 days for free packages
            'status' => 'active',
            'payment_amount' => 0,
            'payment_status' => 'free',
            'notes' => 'Free event package access',
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil paket gratis! Anda akan diarahkan ke halaman paket pembelian.'
        ]);
    }

    // Remove joinFreeTryout method since we're not dealing with standalone tryouts anymore
}
