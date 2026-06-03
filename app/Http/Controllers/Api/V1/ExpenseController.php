<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::query()
            ->when($request->project_id, fn ($q) =>
                $q->where('project_id', $request->project_id)
            )
            ->when($request->billable !== null, fn ($q) =>
                $q->where('billable', filter_var($request->billable, FILTER_VALIDATE_BOOLEAN))
            )
            ->when($request->category, fn ($q) =>
                $q->where('category', $request->category)
            )
            ->with(['project', 'user'])
            ->latest('expense_date')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => ExpenseResource::collection($expenses),
            'meta' => [
                'total' => $expenses->total(),
                'per_page' => $expenses->perPage(),
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'nullable|uuid|exists:projects,id',
            'category' => 'required|in:software,hardware,travel,meal,other',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'billable' => 'sometimes|boolean',
            'receipt_url' => 'nullable|url',
            'expense_date' => 'required|date',
        ]);

        $expense = Expense::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Expense added successfully',
            'data' => new ExpenseResource($expense->load(['project', 'user'])),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);

        $data = $request->validate([
            'category' => 'sometimes|in:software,hardware,travel,meal,other',
            'description' => 'sometimes|nullable|string',
            'amount' => 'sometimes|numeric|min:0',
            'billable' => 'sometimes|boolean',
            'receipt_url' => 'sometimes|nullable|url',
            'expense_date' => 'sometimes|date',
        ]);

        $expense->update($data);

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => new ExpenseResource($expense->fresh()->load(['project', 'user'])),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully',
        ]);
    }
}
