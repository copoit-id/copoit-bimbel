<?php

namespace App\Http\Controllers\tutor;

use App\Http\Controllers\Controller;
use App\Models\TutorLeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorLeaveController extends Controller
{
    public function index(Request $request): View
    {
        $tentor = $request->user()->tentorProfile;
        $leaves = TutorLeaveRequest::query()->where('tentor_id', $tentor->id)->latest('start_at')->paginate(10);
        return view('tutor.leave.index', compact('leaves'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['start_at' => ['required', 'date'], 'end_at' => ['required', 'date', 'after:start_at'], 'reason' => ['required', 'string', 'max:1000']]);
        TutorLeaveRequest::query()->create([...$data, 'tentor_id' => $request->user()->tentorProfile->id]);
        return back()->with('success', 'Pengajuan cuti berhasil dikirim.');
    }
}
