<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAssistantController extends Controller
{
    public function __construct(
        private AdminAssistantService $assistantService
    ) {}

    public function chat(Request $request): JsonResponse
    {
        abort_unless(config('client.branding.admin_assistant_enabled', false), 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($this->assistantService->chat($validated['message']));
    }
}
