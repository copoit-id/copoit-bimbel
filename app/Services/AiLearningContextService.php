<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Tryout;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserAnswerDetail;
use App\Models\UserPackageAcces;
use Illuminate\Support\Carbon;

class AiLearningContextService
{
    /**
     * @return array{package: ?Package, tryout: Tryout, question: Question, answer_detail: ?UserAnswerDetail, context: array<string, mixed>}
     */
    public function resolve(
        User $user,
        string $packageId,
        int $tryoutId,
        string $attemptToken,
        int $questionId,
    ): array {
        $isFreeTryout = $packageId === 'free';
        $package = $isFreeTryout ? null : Package::query()->findOrFail($packageId);
        $tryout = Tryout::query()->with('tryoutDetails')->findOrFail($tryoutId);

        abort_unless($tryout->show_discussion, 403, 'Pembahasan tryout ini tidak tersedia.');

        if ($package) {
            $hasAccess = UserPackageAcces::query()
                ->where('user_id', $user->id)
                ->where('package_id', $package->package_id)
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>', Carbon::now()))
                ->exists();
            $containsTryout = $package->tryouts()
                ->where('tryouts.tryout_id', $tryout->tryout_id)
                ->exists();

            abort_unless($hasAccess && $containsTryout, 403, 'Anda tidak memiliki akses ke tryout ini.');
        }

        $userAnswers = UserAnswer::query()
            ->where('user_id', $user->id)
            ->where('tryout_id', $tryout->tryout_id)
            ->where('status', 'completed')
            ->where('attempt_token', $attemptToken)
            ->with('tryoutDetail')
            ->latest()
            ->get();

        abort_if($userAnswers->isEmpty(), 404, 'Data pengerjaan tidak ditemukan.');

        $question = Question::query()
            ->with(['questionOptions', 'tryoutDetail'])
            ->where('question_id', $questionId)
            ->whereIn('tryout_detail_id', $userAnswers->pluck('tryout_detail_id'))
            ->firstOrFail();
        $answerDetail = UserAnswerDetail::query()
            ->with('questionOption')
            ->whereIn('user_answer_id', $userAnswers->pluck('user_answer_id'))
            ->where('question_id', $question->question_id)
            ->first();

        return [
            'package' => $package,
            'tryout' => $tryout,
            'question' => $question,
            'answer_detail' => $answerDetail,
            'context' => [
                'tryout_name' => $tryout->name,
                'subtest_name' => (string) ($question->tryoutDetail?->type_subtest ?? '-'),
                'question' => $question,
                'answer_detail' => $answerDetail,
            ],
        ];
    }

    /**
     * Materi ini seluruhnya berasal dari database yang dikelola admin. AI tidak
     * diperbolehkan membuat URL rekomendasi sendiri.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recommendedMaterials(User $user, ?Package $package, Tryout $tryout, Question $question): array
    {
        $categoryIds = collect([
            $question->tryoutDetail?->material_category_id,
            $tryout->material_category_id,
        ])->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $materials = Material::query()
            ->active()
            ->where('is_displayed', true)
            ->with('categories:category_id,name')
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereHas(
                'categories',
                fn ($categoryQuery) => $categoryQuery->whereIn('material_categories.category_id', $categoryIds),
            ))
            ->where(function ($query) use ($user, $package) {
                if ($package) {
                    $query->where(function ($packageQuery) use ($package) {
                        $packageQuery
                            ->whereHas('detailPackages', fn ($detailQuery) => $detailQuery
                                ->where('package_id', $package->package_id))
                            ->orWhereHas('packages', fn ($packageMaterialQuery) => $packageMaterialQuery
                                ->where('packages.package_id', $package->package_id));
                    });
                }

                $query->orWhereHas('userAccess', fn ($accessQuery) => $accessQuery
                    ->where('user_id', $user->id)
                    ->where('access_source', 'direct')
                    ->whereIn('access_type', ['free', 'purchased', 'paid'])
                    ->where('status', '!=', 'not_started')
                    ->where(fn ($expiryQuery) => $expiryQuery
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now())));
                $query->orWhere(fn ($freeQuery) => $freeQuery
                    ->where('is_for_sale', true)
                    ->where('type_price', 'free_unconditional'));
            })
            ->ordered()
            ->limit(6)
            ->get();

        return $materials->map(fn (Material $material) => [
            'id' => $material->material_id,
            'title' => $material->title,
            'description' => strip_tags((string) $material->description),
            'type' => $material->type,
            'type_label' => $material->type_label,
            'url' => route('user.material.show', $material->material_id),
            'source' => 'Materi Bimbel',
            'categories' => $material->categories->pluck('name')->values()->all(),
        ])->values()->all();
    }
}
