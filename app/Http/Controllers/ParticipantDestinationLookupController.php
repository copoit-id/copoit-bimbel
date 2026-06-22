<?php

namespace App\Http\Controllers;

use App\Services\OfficialParticipantDestinationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParticipantDestinationLookupController extends Controller
{
    public function institutions(Request $request, OfficialParticipantDestinationService $destinationService)
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['all', 'snbt', 'snbp'])],
        ]);

        return response()->json([
            'data' => $destinationService->institutions($validated['source']),
        ]);
    }

    public function programs(Request $request, OfficialParticipantDestinationService $destinationService)
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['all', 'snbt', 'snbp'])],
            'ptn' => ['required', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'ptn_snbt' => ['nullable', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'ptn_snbp' => ['nullable', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        return response()->json([
            'data' => $destinationService->programs(
                $validated['source'],
                $validated['ptn'],
                $validated['ptn_snbt'] ?? null,
                $validated['ptn_snbp'] ?? null
            ),
        ]);
    }
}
