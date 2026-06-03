<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceItemResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function store(Request $request, string $invoiceId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (! $invoice->isEditable()) {
            return response()->json([
                'message' => 'Only draft invoices can be edited.',
            ], 422);
        }

        $data = $request->validate([
            'description' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'item_type' => 'sometimes|in:service,time_entry,expense',
        ]);

        $item = $invoice->items()->create($data);

        return response()->json([
            'message' => 'Item added successfully',
            'data' => new InvoiceItemResource($item),
        ], 201);
    }

    public function update(Request $request, string $invoiceId, string $itemId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (! $invoice->isEditable()) {
            return response()->json([
                'message' => 'Only draft invoices can be edited.',
            ], 422);
        }

        $item = $invoice->items()->findOrFail($itemId);

        $data = $request->validate([
            'description' => 'sometimes|string',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit_price' => 'sometimes|numeric|min:0',
            'item_type' => 'sometimes|in:service,time_entry,expense',
        ]);

        $item->update($data);

        return response()->json([
            'message' => 'Item updated successfully',
            'data' => new InvoiceItemResource($item->fresh()),
        ]);
    }

    public function destroy(string $invoiceId, string $itemId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (! $invoice->isEditable()) {
            return response()->json([
                'message' => 'Only draft invoices can be edited.',
            ], 422);
        }

        $item = $invoice->items()->findOrFail($itemId);
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }
}
