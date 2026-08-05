<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackQuestion;
use App\Models\Tryout;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $tryouts = Tryout::withCount([
            'feedbackQuestions as feedback_questions_count' => function ($query) {
                $query->where('is_active', true);
            },
            'feedbackSubmissions as feedback_submissions_count'
        ])->latest()->paginate(\App\Support\Pagination::perPage(10));

        return view('admin.pages.feedback.index', compact('tryouts'));
    }

    public function show(Tryout $tryout)
    {
        $questions = $tryout->feedbackQuestions()
            ->orderBy('sort_order')
            ->get();

        return view('admin.pages.feedback.show', compact('tryout', 'questions'));
    }

    public function responses(Tryout $tryout)
    {
        $submissions = $tryout->feedbackSubmissions()
            ->with(['user'])
            ->withCount('answers')
            ->withAvg('answers', 'score')
            ->latest('submitted_at')
            ->paginate(\App\Support\Pagination::perPage(15));

        return view('admin.pages.feedback.responses', compact('tryout', 'submissions'));
    }

    public function responseDetail(Tryout $tryout, \App\Models\FeedbackSubmission $submission)
    {
        if ($submission->tryout_id !== $tryout->tryout_id) {
            abort(404);
        }

        $submission->load(['user', 'answers.question']);

        return view('admin.pages.feedback.response-detail', compact('tryout', 'submission'));
    }

    public function create(Tryout $tryout)
    {
        return view('admin.pages.feedback.form', [
            'tryout' => $tryout,
            'question' => null,
        ]);
    }

    public function store(Request $request, Tryout $tryout)
    {
        $data = $request->validate([
            'question_text' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->has('is_active');
        $data['tryout_id'] = $tryout->tryout_id;

        FeedbackQuestion::create($data);

        return redirect()->route('admin.feedback.show', $tryout->tryout_id)
            ->with('success', 'Pertanyaan feedback berhasil ditambahkan.');
    }

    public function edit(Tryout $tryout, FeedbackQuestion $question)
    {
        if ($question->tryout_id !== $tryout->tryout_id) {
            abort(404);
        }

        return view('admin.pages.feedback.form', [
            'tryout' => $tryout,
            'question' => $question,
        ]);
    }

    public function update(Request $request, Tryout $tryout, FeedbackQuestion $question)
    {
        if ($question->tryout_id !== $tryout->tryout_id) {
            abort(404);
        }

        $data = $request->validate([
            'question_text' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->has('is_active');

        $question->update($data);

        return redirect()->route('admin.feedback.show', $tryout->tryout_id)
            ->with('success', 'Pertanyaan feedback berhasil diperbarui.');
    }

    public function destroy(Tryout $tryout, FeedbackQuestion $question)
    {
        if ($question->tryout_id !== $tryout->tryout_id) {
            abort(404);
        }

        $question->delete();

        return redirect()->route('admin.feedback.show', $tryout->tryout_id)
            ->with('success', 'Pertanyaan feedback berhasil dihapus.');
    }
}
