<?php

namespace App\Http\Middleware;

use App\Models\Material;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionBankQuestion;
use App\Models\Tryout;
use App\Models\TryoutDetail;
use App\Services\TutorContentVisibilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTutorContentOwnership
{
    public function __construct(
        private TutorContentVisibilityService $contentVisibility
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->contentVisibility->shouldScopeToOwner($request->user())) {
            return $next($request);
        }

        $ownerId = $this->resolveOwnerId($request);

        if ($this->contentVisibility->canTutorAccessContentOwner($ownerId, $request->user())) {
            return $next($request);
        }

        if ($this->hasContentTarget($request)) {
            abort(403, 'Konten ini bukan milik akun Anda.');
        }

        return $next($request);
    }

    private function hasContentTarget(Request $request): bool
    {
        return $request->route('material') !== null
            || $request->route('tryout') !== null
            || $request->route('tryoutDetail') !== null
            || $request->route('tryout_detail_id') !== null
            || $request->route('questionBank') !== null
            || $request->route('question') !== null;
    }

    private function resolveOwnerId(Request $request): ?int
    {
        $material = $request->route('material');
        if ($material instanceof Material) {
            return $material->created_by;
        }

        $tryout = $request->route('tryout');
        if ($tryout instanceof Tryout) {
            return $tryout->created_by;
        }

        $questionBank = $request->route('questionBank');
        if ($questionBank instanceof QuestionBank) {
            return $questionBank->created_by;
        }

        $question = $request->route('question');
        if ($question instanceof QuestionBankQuestion) {
            return $question->bank?->created_by;
        }
        if ($question instanceof Question) {
            return $question->tryoutDetail?->tryout?->created_by;
        }

        $tryoutDetail = $request->route('tryoutDetail');
        if ($tryoutDetail instanceof TryoutDetail) {
            return $tryoutDetail->tryout?->created_by;
        }

        $tryoutDetailId = $request->route('tryout_detail_id');
        if ($tryoutDetailId !== null) {
            return TryoutDetail::query()
                ->with('tryout:tryout_id,created_by')
                ->find($tryoutDetailId)?->tryout?->created_by;
        }

        return null;
    }
}
