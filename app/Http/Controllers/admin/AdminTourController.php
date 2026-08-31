<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTourProgressService;
use App\Support\AdminTours\AdminTourRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTourController extends Controller
{
    public function __construct(
        private readonly AdminTourRegistry $registry,
        private readonly AdminTourProgressService $progressService,
    ) {}

    public function show(Request $request, string $tourKey): JsonResponse
    {
        $portal = $this->portalFor($request);
        return response()->json([
            'tour' => $this->registry->payload($this->tourForRequest($request, $portal, $tourKey)),
        ]);
    }

    public function start(Request $request, string $tourKey): JsonResponse
    {
        $portal = $this->portalFor($request);
        $tour = $this->tourForRequest($request, $portal, $tourKey);
        $progress = $this->progressService->restart($request->user(), $tour);

        return response()->json([
            'status' => $progress->status,
            'current_step_id' => $progress->current_step_id,
        ]);
    }

    public function storeStep(Request $request, string $tourKey, string $stepId): JsonResponse
    {
        $request->validate([
            'event' => ['required', Rule::in(['completed', 'skipped', 'dismissed'])],
        ]);

        $portal = $this->portalFor($request);
        $tour = $this->tourForRequest($request, $portal, $tourKey);
        $stepIds = array_column($tour['steps'], 'id');
        abort_unless(in_array($stepId, $stepIds, true), 404);

        $progress = match ($request->input('event')) {
            'completed' => $this->progressService->completeStep($request->user(), $tour, $stepId),
            'skipped', 'dismissed' => $this->progressService->close($request->user(), $tour, $request->input('event')),
        };

        return response()->json([
            'status' => $progress->status,
            'current_step_id' => $progress->current_step_id,
        ]);
    }

    public function complete(Request $request, string $tourKey): JsonResponse
    {
        $portal = $this->portalFor($request);
        $tour = $this->tourForRequest($request, $portal, $tourKey);
        $progress = $this->progressService->close($request->user(), $tour, 'completed');

        return response()->json([
            'status' => $progress->status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tourForRequest(Request $request, string $portal, string $tourKey): array
    {
        $tour = $this->registry->forUser($tourKey, $request->user(), $portal);
        abort_unless($tour, 404);

        return $tour;
    }

    private function portalFor(Request $request): string
    {
        return $request->user()?->isTutor() ? 'tutor' : 'admin';
    }
}
