<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'user')->paginate(10);
        return view('admin.pages.user.index', compact('users'));
    }

    public function create()
    {
        return view('admin.pages.user.create', [
            'user' => null
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'required|in:aktif,nonaktif',
            'role' => 'required|in:admin,user'
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? 'aktif',
            'role' => $validated['role']
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User created successfully.');
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.pages.user.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.pages.user.create', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:aktif,nonaktif',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::findOrFail($id);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'status' => $validated['status'],
            'role' => $validated['role'],
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil diperbarui');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || count($ids) === 0) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Pilih minimal satu user untuk dihapus.');
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Data user tidak valid.');
        }

        $deleted = User::where('role', 'user')
            ->whereIn('id', $ids)
            ->delete();

        if ($deleted === 0) {
            return redirect()->route('admin.user.index')
                ->with('error', 'Tidak ada user yang dihapus.');
        }

        return redirect()->route('admin.user.index')
            ->with('success', "{$deleted} user berhasil dihapus.");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil dihapus');
    }

    public function report($id)
    {
        $user = User::with([
            'userAnswers' => function ($query) {
                $query->where('status', 'completed')
                    ->with(['tryout', 'userAnswerDetails'])
                    ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        $completedTryouts = $user->userAnswers->where('status', 'completed');
        $totalTryouts = $completedTryouts->count();
        $avgScore = $completedTryouts->avg('score') ?? 0;

        $recentTryouts = $completedTryouts->take(5)->map(function ($answer) {
            return [
                'name' => $answer->tryout->name ?? 'Unknown Tryout',
                'score' => round($answer->score ?? 0, 1),
                'date' => Carbon::parse($answer->finished_at ?? $answer->created_at),
                'is_passed' => $answer->is_passed ?? false
            ];
        });

        $totalStudyMinutes = $completedTryouts->sum(function ($answer) {
            if ($answer->started_at && $answer->finished_at) {
                return Carbon::parse($answer->started_at)->diffInMinutes(Carbon::parse($answer->finished_at));
            }

            return optional($answer->tryoutDetail)->duration ?? 60;
        });

        $totalStudyHours = round($totalStudyMinutes / 60, 1);

        $certificates = collect();
        $activities = collect();

        foreach ($completedTryouts->take(4) as $answer) {
            $activities->push([
                'type' => 'tryout',
                'text' => 'Menyelesaikan tryout ' . ($answer->tryout->name ?? 'Unknown') . ' dengan skor ' . round($answer->score ?? 0, 1),
                'icon' => 'ri-file-list-line',
                'color' => 'blue',
                'date' => Carbon::parse($answer->finished_at ?? $answer->created_at)
            ]);
        }

        $activities->push([
            'type' => 'login',
            'text' => 'Login ke sistem',
            'icon' => 'ri-login-box-line',
            'color' => 'green',
            'date' => Carbon::now()->subHours(2)
        ]);

        $activities = $activities->sortByDesc('date')->take(8);

        $statistics = [
            'total_tryouts' => $totalTryouts,
            'avg_score' => round($avgScore, 1),
            'total_certificates' => $certificates->count(),
            'study_hours' => $totalStudyHours
        ];

        return view('admin.pages.user.report', compact(
            'user',
            'statistics',
            'recentTryouts',
            'certificates',
            'activities'
        ));
    }
}
