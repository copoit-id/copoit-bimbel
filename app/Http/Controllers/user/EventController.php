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
        // Redirect to package index with free tab - Event merged with Package
        return redirect()->route('user.package.index', ['tab' => 'free']);
    }

    public function joinEvent($package_id)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk mengklaim paket gratis.'
            ], 401);
        }
        
        $package = Package::where('package_id', $package_id)
            ->where('status', 'active')
            ->whereIn('type_price', ['free_unconditional', 'free_conditional'])
            ->firstOrFail();

        // Check if user already has active access (including not expired)
        $existing = UserPackageAcces::where('user_id', Auth::id())
            ->where('package_id', $package_id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', Carbon::now());
            })
            ->first();

        if ($existing) {
            // User already has active access, redirect to roadmap
            return response()->json([
                'success' => true,
                'message' => 'Anda sudah memiliki akses ke paket ini',
                'redirect_url' => route('user.package.show', $package_id)
            ]);
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
            'message' => 'Berhasil mengambil paket gratis!',
            'redirect_url' => route('user.package.show', $package_id)
        ]);
    }

    // Remove joinFreeTryout method since we're not dealing with standalone tryouts anymore
}
