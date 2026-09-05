<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\TutorLeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorLeaveController extends Controller
{
    public function index(): View
    {
        $leaves = TutorLeaveRequest::query()->with('tentor:id,name')->latest('start_at')->paginate(20);
        return view('admin.pages.tutor-leave.index', compact('leaves'));
    }
    public function approve(Request $request, TutorLeaveRequest $leave): RedirectResponse { $leave->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]); return back()->with('success', 'Cuti disetujui.'); }
    public function reject(Request $request, TutorLeaveRequest $leave): RedirectResponse { $data = $request->validate(['admin_notes' => ['required', 'string', 'max:1000']]); $leave->update([...$data, 'status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]); return back()->with('success', 'Cuti ditolak.'); }
}
