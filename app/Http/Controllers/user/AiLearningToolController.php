<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\AiDiscussionUsageLog;
use App\Models\AiLearningArtifact;
use App\Services\AiLearningContextService;
use App\Services\AiLearningToolService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AiLearningToolController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $currentSource = trim((string) $request->query('source', ''));
        $currentTool = in_array($request->query('tool'), ['note', 'recommendation', 'question', 'flashcard'], true)
            ? $request->query('tool')
            : 'note';
        $showPinned = $request->boolean('pinned') || $request->boolean('saved');

        $artifacts = AiLearningArtifact::query()
            ->with(['tryout:tryout_id,name', 'question:question_id,question_text'])
            ->where('user_id', $user->id)
            ->where('tool', $currentTool)
            ->when($showPinned, fn ($query) => $query->whereNotNull('saved_at'))
            ->when($currentSource === 'independent', fn ($query) => $query->where('source_type', 'independent'))
            ->when($currentSource === 'discussion', fn ($query) => $query->where('source_type', 'discussion'))
            ->when(
                str_starts_with($currentSource, 'tryout:'),
                fn ($query) => $query->where('tryout_id', (int) Str::after($currentSource, 'tryout:')),
            )
            ->when(
                $currentSource !== ''
                    && ! in_array($currentSource, ['independent', 'discussion'], true)
                    && ! str_starts_with($currentSource, 'tryout:'),
                fn ($query) => $query->where('source_label', $currentSource),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sourceOptions = AiLearningArtifact::query()
            ->where('user_id', $user->id)
            ->where('tool', $currentTool)
            ->select('source_type', 'source_label')
            ->whereNotNull('source_label')
            ->distinct()
            ->orderBy('source_label')
            ->get()
            ->toBase()
            ->map(fn (AiLearningArtifact $artifact): array => [
                'value' => (string) $artifact->source_label,
                'label' => (string) $artifact->source_label,
            ]);
        $legacyTryoutOptions = AiLearningArtifact::query()
            ->with('tryout:tryout_id,name')
            ->where('user_id', $user->id)
            ->where('tool', $currentTool)
            ->whereNull('source_label')
            ->whereNotNull('tryout_id')
            ->select('tryout_id')
            ->distinct()
            ->get()
            ->toBase()
            ->filter(fn (AiLearningArtifact $artifact) => $artifact->tryout !== null)
            ->map(fn (AiLearningArtifact $artifact): array => [
                'value' => 'tryout:'.$artifact->tryout_id,
                'label' => 'Pembahasan · '.$artifact->tryout->name,
            ])
            ->unique('value')
            ->values();
        $sourceOptions = $sourceOptions
            ->merge($legacyTryoutOptions)
            ->unique('value')
            ->sortBy('label')
            ->values();

        $pinnedArtifactsCount = AiLearningArtifact::query()
            ->where('user_id', $user->id)
            ->where('tool', $currentTool)
            ->whereNotNull('saved_at')
            ->count();

        return view('user.pages.ai-learning.index', compact('artifacts', 'sourceOptions', 'currentSource', 'currentTool', 'showPinned', 'pinnedArtifactsCount'));
    }

    public function history(
        Request $request,
        string $idPackage,
        int $idTryout,
        string $token,
        AiLearningContextService $contextService,
    ): JsonResponse {
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'tool' => ['required', 'in:note,recommendation,question,flashcard'],
        ]);
        $user = $request->user();
        $resolved = $contextService->resolve(
            $user,
            $idPackage,
            $idTryout,
            $token,
            (int) $data['question_id'],
        );

        $artifacts = AiLearningArtifact::query()
            ->where('user_id', $user->id)
            ->where('tryout_id', $resolved['tryout']->tryout_id)
            ->where('question_id', $resolved['question']->question_id)
            ->where('attempt_token', $token)
            ->where('tool', $data['tool'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'artifacts' => $artifacts->map(fn (AiLearningArtifact $artifact): array => [
                'id' => $artifact->id,
                'tool' => $artifact->tool,
                'title' => $artifact->title,
                'created_at' => $artifact->created_at?->format('d M Y, H:i'),
                'html' => view('user.pages.ai-learning.partials.result', [
                    'artifact' => $artifact,
                    'payload' => $artifact->payload,
                ])->render(),
            ])->values(),
        ]);
    }

    public function generate(
        Request $request,
        string $idPackage,
        int $idTryout,
        string $token,
        AiLearningContextService $contextService,
        AiLearningToolService $toolService,
    ): JsonResponse {
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'tool' => ['required', 'in:note,recommendation,question,flashcard'],
            'difficulty' => ['nullable', 'in:mudah,sedang,sulit'],
            'variation' => ['nullable', 'in:konteks,angka,hots'],
            'hots_level' => ['nullable', 'in:rendah,sedang,tinggi'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);
        $user = $request->user();
        $resolved = $contextService->resolve(
            $user,
            $idPackage,
            $idTryout,
            $token,
            (int) $data['question_id'],
        );

        try {
            $result = $toolService->generate($data['tool'], [
                'difficulty' => $data['difficulty'] ?? 'sedang',
                'variation' => $data['variation'] ?? 'konteks',
                'hots_level' => $data['hots_level'] ?? 'sedang',
                'question_count' => $data['question_count'] ?? 1,
            ], $resolved['context']);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $payload = $result['payload'];
        if ($data['tool'] === 'recommendation') {
            $payload['materials'] = $contextService->recommendedMaterials(
                $user,
                $resolved['package'],
                $resolved['tryout'],
                $resolved['question'],
            );
        }

        $artifact = DB::transaction(function () use ($user, $resolved, $token, $data, $payload, $result) {
            $artifact = AiLearningArtifact::query()->create([
                'user_id' => $user->id,
                'tryout_id' => $resolved['tryout']->tryout_id,
                'question_id' => $resolved['question']->question_id,
                'attempt_token' => $token,
                'source_type' => 'discussion',
                'source_label' => $resolved['package']?->name
                    ? 'Paket · '.$resolved['package']->name
                    : 'Pembahasan · '.$resolved['tryout']->name,
                'tool' => $data['tool'],
                'title' => Str::limit((string) ($payload['title'] ?? $this->toolLabel($data['tool'])), 255, ''),
                'payload' => $payload,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'input_tokens' => $result['usage']['input'],
                'output_tokens' => $result['usage']['output'],
                'total_tokens' => $result['usage']['total'],
            ]);

            AiDiscussionUsageLog::query()->create([
                'user_id' => $user->id,
                'tryout_id' => $resolved['tryout']->tryout_id,
                'question_id' => $resolved['question']->question_id,
                'attempt_token' => $token,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'input_tokens' => $result['usage']['input'],
                'output_tokens' => $result['usage']['output'],
                'total_tokens' => $result['usage']['total'],
                'response_time_ms' => $result['response_time_ms'],
                'user_message' => 'AI Learning Tool: '.$this->toolLabel($data['tool']),
                'assistant_message' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            return $artifact;
        });

        return response()->json([
            'artifact_id' => $artifact->id,
            'tool' => $artifact->tool,
            'title' => $artifact->title,
            'html' => view('user.pages.ai-learning.partials.result', [
                'artifact' => $artifact,
                'payload' => $payload,
            ])->render(),
            'quota' => $result['quota'],
        ]);
    }

    public function generateIndependent(
        Request $request,
        AiLearningToolService $toolService,
    ): JsonResponse {
        $data = $request->validate([
            'tool' => ['required', 'in:note,recommendation,question,flashcard'],
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'min:20', 'max:10000'],
            'difficulty' => ['nullable', 'in:mudah,sedang,sulit'],
            'variation' => ['nullable', 'in:konteks,angka,hots'],
            'hots_level' => ['nullable', 'in:rendah,sedang,tinggi'],
            'question_count' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);
        $user = $request->user();
        $sourceTitle = trim((string) ($data['title'] ?? '')) ?: 'Input mandiri';
        $context = [
            'tryout_name' => 'AI Learning Tools',
            'subtest_name' => $sourceTitle,
            'question_type' => 'external_input',
            'question_text' => trim($data['content']),
            'options' => [],
            'selected_answer' => '-',
            'explanation' => 'Tidak tersedia. Buat hasil belajar berdasarkan input pengguna.',
            'question_reference' => 'external-'.$user->id,
        ];

        try {
            $result = $toolService->generate($data['tool'], [
                'difficulty' => $data['difficulty'] ?? 'sedang',
                'variation' => $data['variation'] ?? 'konteks',
                'hots_level' => $data['hots_level'] ?? 'sedang',
                'question_count' => $data['question_count'] ?? 1,
            ], $context);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $payload = $result['payload'];
        if ($data['tool'] === 'recommendation') {
            $payload['materials'] = [];
        }

        $artifact = DB::transaction(function () use ($user, $data, $sourceTitle, $payload, $result) {
            $artifact = AiLearningArtifact::query()->create([
                'user_id' => $user->id,
                'source_type' => 'independent',
                'source_label' => 'Input mandiri',
                'tool' => $data['tool'],
                'title' => Str::limit((string) ($payload['title'] ?? $sourceTitle), 255, ''),
                'payload' => $payload,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'input_tokens' => $result['usage']['input'],
                'output_tokens' => $result['usage']['output'],
                'total_tokens' => $result['usage']['total'],
            ]);

            AiDiscussionUsageLog::query()->create([
                'user_id' => $user->id,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'input_tokens' => $result['usage']['input'],
                'output_tokens' => $result['usage']['output'],
                'total_tokens' => $result['usage']['total'],
                'response_time_ms' => $result['response_time_ms'],
                'user_message' => 'AI Learning Tool mandiri: '.$this->toolLabel($data['tool']),
                'assistant_message' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            return $artifact;
        });

        return response()->json([
            'artifact_id' => $artifact->id,
            'html' => view('user.pages.ai-learning.partials.result', [
                'artifact' => $artifact,
                'payload' => $payload,
            ])->render(),
            'quota' => $result['quota'],
        ]);
    }

    public function notes(Request $request): RedirectResponse
    {
        return redirect()->route('user.ai-learning.index', [
            'tool' => 'note',
            'pinned' => 1,
        ]);
    }

    public function save(Request $request, AiLearningArtifact $artifact): JsonResponse
    {
        abort_unless($artifact->user_id === $request->user()->id, 404);
        $data = $request->validate(['pinned' => ['nullable', 'boolean']]);
        $pinned = array_key_exists('pinned', $data) ? (bool) $data['pinned'] : true;
        $artifact->update(['saved_at' => $pinned ? ($artifact->saved_at ?? now()) : null]);

        return response()->json([
            'message' => $pinned ? 'Hasil AI berhasil dipin.' : 'Pin hasil AI dilepas.',
            'pinned' => $pinned,
            'saved_at' => $artifact->saved_at?->toIso8601String(),
            'pdf_url' => $pinned && $artifact->tool === 'note'
                ? route('user.ai-learning.notes.pdf', $artifact)
                : null,
        ]);
    }

    public function exportPdf(Request $request, AiLearningArtifact $artifact): Response
    {
        abort_unless(
            $artifact->user_id === $request->user()->id
                && $artifact->tool === 'note'
                && $artifact->saved_at !== null,
            404,
        );
        $artifact->loadMissing(['tryout:tryout_id,name', 'question:question_id,question_text', 'user:id,name,email']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('user.pages.ai-learning.note-pdf', compact('artifact'))->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = Str::slug($artifact->title ?: 'catatan-materi').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function destroy(Request $request, AiLearningArtifact $artifact): JsonResponse|RedirectResponse
    {
        abort_unless($artifact->user_id === $request->user()->id && $artifact->tool === 'note', 404);
        $artifact->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Catatan berhasil dihapus.']);
        }

        return back()->with('success', 'Catatan berhasil dihapus.');
    }

    private function toolLabel(string $tool): string
    {
        return match ($tool) {
            'note' => 'Catatan Materi',
            'recommendation' => 'Rekomendasi Belajar',
            'question' => 'Generate Soal',
            'flashcard' => 'Flashcard',
            default => 'AI Learning Tool',
        };
    }
}
