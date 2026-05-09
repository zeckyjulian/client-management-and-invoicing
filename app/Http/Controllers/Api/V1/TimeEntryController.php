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
            'description' => 'nullable|string',
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
            'description' => 'nullable|string',
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
}
