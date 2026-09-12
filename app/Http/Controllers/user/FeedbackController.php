<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackSubmission;
use App\Models\Package;
use App\Models\Tryout;
use App\Models\UserAnswer;
use App\Models\UserPackageAcces;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function store(Request $request, $id_package, $id_tryout)
    {
        $now = Carbon::now('Asia/Jakarta');
        $userId = Auth::id();
        $tryout = Tryout::query()->findOrFail($id_tryout);

        if ($id_package !== 'free') {
            $package = Package::query()->findOrFail($id_package);

            abort_unless(
                $package->tryouts()->where('tryouts.tryout_id', $tryout->tryout_id)->exists(),
                404
            );

            $hasAccess = UserPackageAcces::where('user_id', $userId)
                ->where('package_id', $id_package)
                ->where('status', 'active')
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>', $now);
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('user.package.index')
                    ->with('error', 'Anda tidak memiliki akses ke paket ini');
            }
        }

        $hasAttempt = UserAnswer::where('user_id', $userId)
            ->where('tryout_id', $id_tryout)
            ->whereIn('status', ['completed', 'pending_release'])
            ->exists();

        if (!$hasAttempt) {
            return redirect()->route('user.tryout.lobby', [$id_package, $id_tryout])
                ->with('error', 'Belum ada hasil tryout yang dapat dikirimkan feedback');
        }

        $alreadySubmitted = FeedbackSubmission::where('user_id', $userId)
            ->where('tryout_id', $id_tryout)
            ->exists();

        if ($alreadySubmitted) {
            return redirect()->route('user.tryout.result', [$id_package, $id_tryout])
                ->with('success', 'Feedback sudah tersimpan.');
        }

        $questions = FeedbackQuestion::where('tryout_id', $id_tryout)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            return redirect()->route('user.tryout.result', [$id_package, $id_tryout])
                ->with('error', 'Feedback belum tersedia untuk tryout ini.');
        }

        $rules = [];
        foreach ($questions as $question) {
            $rules['scores.' . $question->feedback_question_id] = 'required|integer|min:1|max:5';
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $questions, $userId, $id_tryout, $request) {
            $submission = FeedbackSubmission::create([
                'user_id' => $userId,
                'tryout_id' => $id_tryout,
                'attempt_token' => $request->input('attempt_token'),
                'submitted_at' => Carbon::now('Asia/Jakarta'),
            ]);

            foreach ($questions as $question) {
                FeedbackAnswer::create([
                    'feedback_submission_id' => $submission->feedback_submission_id,
                    'feedback_question_id' => $question->feedback_question_id,
                    'score' => (int) $validated['scores'][$question->feedback_question_id],
                ]);
            }
        });

        return redirect()->route('user.tryout.result', [$id_package, $id_tryout])
            ->with('success', 'Terima kasih! Feedback Anda sudah tersimpan.');
    }
}
