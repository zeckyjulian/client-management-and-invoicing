<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $service) {}

    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->client_id, fn ($q) =>
                $q->where('client_id', $request->client_id)
            )
            ->when($request->billing_type, fn ($q) =>
                $q->where('billing_type', $request->billing_type)
            )
            ->with('client')
            ->withCount('tasks')
            ->latest()
            ->paginate($request->per_page ?? 15);
        
        return response()->json([
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => 'required|uuid|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'billing_type' => 'required|in:hourly,fixed,retainer',
            'budget' => 'nullable|numeric|min:0',
            'hourly_rate' => 'required_if:billing_type,hourly|nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = $this->service->create(app('current.workspace'), $data);

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => new ProjectResource($project->load('client')),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with(['client', 'tasks'])
                        ->withCount('tasks')
                        ->findOrFail($id);

        return response()->json([
            'data' => new ProjectResource($project),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,paused,completed,cancelled',
            'billing_type' => 'sometimes|in:hourly,fixed,retainer',
            'budget' => 'sometimes|nullable|numeric|min:0',
            'hourly_rate' => 'sometimes|nullable|numeric|min:0',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
        ]);

        $project = $this->service->update($project, $data);

        return response()->json([
            'message' => 'Project updated successfully.',
            'data' => new ProjectResource($project->load('client')),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->service->delete($project);

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}
