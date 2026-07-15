<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $service)
    {
        // 
    }

    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->when($request->client_id, fn ($q) =>
                $q->where('client_id', $request->client_id)
            )
            ->when($request->project_id, fn ($q) =>
                $q->where('project_id', $request->project_id)
            )
            ->with(['client', 'project'])
            ->latest('issue_date')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => InvoiceResource::collection($invoices),
            'meta' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => 'required|uuid|exists:clients,id',
            'project_id' => 'nullable|uuid|exists:projects,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'discount' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.item_type' => 'sometimes|in:service,time_entry,expense',
        ]);

        $invoice = $this->service->create(app('current.workspace'), $data);

        return response()->json([
            'message' => 'Invoice created successfully',
            'data' => new InvoiceResource($invoice),
        ], 201);
    }

    public function generateFromProject(Request $request, string $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $data = $request->validate([
            'due_date' => 'required|date|after_or_equal:today',
            'issue_date' => 'sometimes|date',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'discount' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $invoice = $this->service->generateFromProject($project, $data);

        return response()->json([
            'message' => 'Invoice generated successfully from project',
            'data' => new InvoiceResource($invoice),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with(['client', 'project', 'items'])
                    ->findOrFail($id);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if (! $invoice->isEditable()) {
            return response()->json([
                'message' => 'Only draft invoices can be edited',
            ], 422);
        }

        $data = $request->validate([
            'due_date' => 'sometimes|date|after_or_equal:issue_date',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'discount' => 'sometimes|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
        ]);

        $invoice->update($data);

        if (isset($data['tax_rate']) || isset($data['discount'])) {
            $invoice->recalculateTotals();
        }

        return response()->json([
            'message' => 'Invoice updated successfully.',
            'data' => new InvoiceResource($invoice->fresh()->load(['client', 'project', 'items'])),
        ]);
    }

    public function send(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->send($invoice);

        return response()->json([
            'message' => 'Invoice sent successfully.',
            'data' => new InvoiceResource($invoice->fresh()->load(['client', 'project', 'items'])),
        ]);
    }

    public function markAsPaid(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->markAsPaid($invoice);

        return response()->json([
            'message' => 'Invoice marked as paid successfully.',
            'data' => new InvoiceResource($invoice->fresh()->load(['client', 'project', 'items'])),
        ]);
    }

    public function cancel(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->service->cancel($invoice);

        return response()->json([
            'message' => 'Invoice cancelled successfully.',
            'data' => new InvoiceResource($invoice->fresh()->load(['client', 'project', 'items'])),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json([
                'message' => 'Paid invoices cannot be deleted.',
            ], 422);
        }

        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully.',
        ]);
    }
}
