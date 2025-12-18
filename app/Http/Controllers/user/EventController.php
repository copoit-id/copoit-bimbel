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
        $freeTypes = ['free_unconditional', 'free_conditional'];

        $kelasPackages = Package::where('type_package', 'bimbel')
            ->where('status', 'active')
            ->whereIn('type_price', $freeTypes)
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->with(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id());
            }])
            ->get();

        $tryoutPackages = Package::where('type_package', 'tryout')
            ->where('status', 'active')
            ->whereIn('type_price', $freeTypes)
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->with(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id());
            }])
            ->get();

        $sertifikasiPackages = Package::where('type_package', 'sertifikasi')
            ->where('status', 'active')
            ->whereIn('type_price', $freeTypes)
            ->withCount(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id())
                    ->where('status', 'active')
                    ->where('end_date', '>', Carbon::now());
            }])
            ->with(['userAccess' => function ($query) {
                $query->where('user_id', Auth::id());
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
            ->where('status', 'active')
            ->where('type_price', 'free_unconditional')
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
        UserPackageAcces::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'package_id' => $package_id,
            ],
            [
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'status' => 'active',
                'payment_amount' => 0,
                'payment_status' => 'free',
                'notes' => 'Free event package access',
                'created_by' => Auth::id(),
                'requirement_proof_path' => null,
                'requirement_review_notes' => null,
                'requirement_status' => 'none',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil paket gratis! Anda akan diarahkan ke halaman paket pembelian.'
        ]);
    }

    // Remove joinFreeTryout method since we're not dealing with standalone tryouts anymore
}
