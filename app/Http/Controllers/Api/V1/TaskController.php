<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $tasks = $project->tasks()
                ->when($request->status, fn ($q) =>
                    $q->where('status', $request->status)
                )
                ->when($request->assigned_to, fn ($q) =>
                    $q->where('assigned_to', $request->assigned_to)
                )
                ->with('assignedTo')
                ->orderBy('priority', 'desc')
                ->orderBy('due_date')
                ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'status' => 'sometimes|in:todo,in_progress,review,done',
            'estimated_minutes' => 'nullable|integer|min:1',
            'priority' => 'sometimes|integer|in:0,1,2',
            'due_date' => 'nullable|date',
        ]);

        $task = $project->tasks()->create($data);

        return response()->json([
            'message' => 'Task created successfully',
            'data' => new TaskResource($task->load('assignedTo')),
        ], 201);
    }

    public function show(string $projectId, string $taskId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $task = $project->tasks()
                        ->with(['assignedTo', 'timeEntries.user'])
                        ->findOrFail($taskId);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function update(Request $request, string $projectId, string $taskId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $task = $project->tasks()->findOrFail($taskId);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'assigned_to' => 'sometimes|nullable|uuid|exists:users,id',
            'status' => 'sometimes|in:todo,in_progress,review,done',
            'estimated_minutes' => 'sometimes|nullable|integer|min:1',
            'priority' => 'sometimes|integer|in:0,1,2',
            'due_date' => 'sometimes|nullable|date',
        ]);

        $task->update($data);

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => new TaskResource($task->fresh()->load('assignedTo')),
        ]);
    }

    public function destroy(string $projectId, string $taskId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $task = $project->tasks()->findOrFail($taskId);
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}
