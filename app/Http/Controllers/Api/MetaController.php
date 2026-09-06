<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only lookups the API client needs to render pickers, plus the
 * dashboard summary for the authenticated user.
 */
class MetaController extends Controller
{
    /** GET /api/categories */
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** GET /api/meta - enums + assignable agents. */
    public function meta(): JsonResponse
    {
        return response()->json([
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'assignees' => User::assignable()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** GET /api/dashboard */
    public function dashboard(Request $request, DashboardService $dashboard): JsonResponse
    {
        $data = $dashboard->forUser($request->user());

        return response()->json([
            'cards' => $data['cards'],
            'charts' => $data['charts'],
        ]);
    }
}
