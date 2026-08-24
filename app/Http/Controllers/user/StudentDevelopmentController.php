<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\StudentFeedback;
use App\Models\StudentProgressReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDevelopmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $feedback = StudentFeedback::query()
            ->where('is_visible_to_student', true)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($groupQuery) use ($user): void {
                        $groupQuery->whereNull('user_id')
                            ->whereHas(
                                'studyGroup.users',
                                fn ($userQuery) => $userQuery->where('users.id', $user->id)
                            );
                    });
            })
            ->with(['tentor:id,name,expertise', 'studyGroup:id,name'])
            ->latest()
            ->paginate(\App\Support\Pagination::perPage(10), ['*'], 'feedback_page');
        $progress = StudentProgressReport::query()
            ->where('user_id', $user->id)
            ->with([
                'tentor:id,name,expertise',
                'package:package_id,name',
                'studyGroup:id,name',
            ])
            ->latest('period_end')
            ->paginate(\App\Support\Pagination::perPage(10), ['*'], 'progress_page');

        return view('user.pages.development.index', compact('feedback', 'progress'));
    }
}
