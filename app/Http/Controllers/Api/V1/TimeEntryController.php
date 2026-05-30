<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeEntryResource;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Services\TimeTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function __construct(private readonly TimeTrackingService $service) {}

    // User's running timer
    public function running(Request $request): JsonResponse
    {
        $timer = $this->service->getRunningTimer($request->user());

        if (! $timer) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => new TimeEntryResource($timer),
        ]);
    }

    // Start a timer
    public function start(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $data = $request->validate([
            'task_id' => 'nullable|uuid|exists:tasks,id',
            'description' => 'required_without:task_id|nullable|string|max:255',
            'billable' => 'sometimes|boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $entry = $this->service->start($project, $request->user(), $data);

        return response()->json([
            'message' => 'Timer started successfully',
            'data' => new TimeEntryResource($entry->load(['project', 'task'])),
        ], 201);
    }

    // Stop a timer
    public function stop(Request $request, string $entryId): JsonResponse
    {
        $entry = TimeEntry::where('user_id', $request->user()->id)->findOrFail($entryId);

        $entry = $this->service->stop($entry);

        return response()->json([
            'message' => 'Timer stopped successfully',
            'data' => new TimeEntryResource($entry->load(['project', 'task'])),
        ]);
    }

    // Add manual time entry
    public function storeManual(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $data = $request->validate([
            'task_id' => 'nullable|uuid|exists:tasks,id',
            'description' => 'required_without:task_id|nullable|string|max:255',
            'billable' => 'sometimes|boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
            'started_at' => 'required|date',
            'ended_at' => 'required|date|after:started_at',
        ]);

        $entry = $this->service->addManual($project, $request->user(), $data);

        return response()->json([
            'message' => 'Time entry added successfully',
            'data' => new TimeEntryResource($entry->load(['project', 'task'])),
        ], 201);
    }

    // List time entries for a project
    public function index(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $entries = $project->timeEntries()
            ->when($request->user_id, fn ($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->billable, fn ($q) =>
                $q->where('billable', filter_var($request->billable, FILTER_VALIDATE_BOOLEAN))
            )
            ->when($request->date_form, fn ($q) =>
                $q->where('started_at', '>=', $request->date_form)
            )
            ->when($request->date_to, fn ($q) =>
                $q->where('started_at', '<=', $request->date_to)
            )
            ->with(['user', 'task'])
            ->latest('started_at')
            ->paginate($request->per_page ?? 15);

        $totalSeconds = $project->timeEntries()->sum('duration_seconds');

        return response()->json([
            'data' => TimeEntryResource::collection($entries),
            'meta' => [
                'total' => $entries->total(),
                'per_page' => $entries->perPage(),
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total_duration' => gmdate('H:i:s', $totalSeconds),
            ],
        ]);
    }

    public function destroy(string $entryId): JsonResponse
    {
        $entry = TimeEntry::findOrFail($entryId);
        $entry->delete();

        return response()->json([
            'message' => 'Time entry deleted successfully',
        ]);
    }
}
