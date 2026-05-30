<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->when($request->search, fn ($q) =>
                $q->where('name', 'ilike', "%{$request->search}%")
                ->orWhere('email', 'ilike', "%{$request->search}%")
                ->orWhere('company', 'ilike', "%{$request->search}%")
        )
        ->withCount('projects')
        ->latest()
        ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => ClientResource::collection($clients),
            'meta' => [
                'total' => $clients->total(),
                'per_page' => $clients->perPage(),
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $client = Client::create($data);

        return response()->json([
            'message' => 'Client created successfully.',
            'data' => new ClientResource($client),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $client = Client::withCount('projects')
                        ->with(['projects' => fn ($q) => $q->latest()->limit(5)])
                        ->findOrFail($id);

        return response()->json([
            'data' => new ClientResource($client),
        ]);
    }
    
    public function update(Request $request, string $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email',
            'company' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string',
        ]);

        $client->update($data);

        return response()->json([
            'message' => 'Client updated successfully.',
            'data' => new ClientResource($client->fresh()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully.',
        ]);
    }
}
