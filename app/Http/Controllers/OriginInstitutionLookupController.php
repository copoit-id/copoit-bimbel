<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OriginInstitutionLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $institutions = User::query()
            ->whereNotNull('origin_institution')
            ->whereRaw('LOWER(origin_institution) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->selectRaw('MIN(origin_institution) as name')
            ->groupByRaw('LOWER(origin_institution)')
            ->orderBy('name')
            ->limit(10)
            ->pluck('name')
            ->values();

        return response()->json(['data' => $institutions]);
    }
}
