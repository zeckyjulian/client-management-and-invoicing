<?php

namespace App\Services;

use App\Events\Invoice\InvoiceCreated;
use App\Events\Invoice\InvoicePaid;
use App\Events\Invoice\InvoiceSent;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    // Generate unique invoice number for workspace
    public function generateInvoiceNumber(Workspace $workspace): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');

        $last = Invoice::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $sequence = str_pad($last + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    // Create manual invoice
    public function create(Workspace $workspace, array $data): Invoice
    {
        return DB::transaction(function () use ($workspace, $data) {
            $invoice = Invoice::create([
                'workspace_id' => $workspace->id,
                'client_id' => $data['client_id'],
                'project_id' => $data['project_id'] ?? null,
                'invoice_number' => $this->generateInvoiceNumber($workspace),
                'status' => 'draft',
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'tax_rate' => $data['tax_rate'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create invoice items
            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'item_type' => $item['item_type'] ?? 'service',
                ]);
            }

            InvoiceCreated::dispatch($invoice->fresh());

            return $invoice->fresh()->load(['items', 'client']);
        });
    }

    // Generate invoice from time entries of a project automatically
    public function generateFromProject(Project $project, array $data): Invoice
    {
        $workspace = $project->workspace;

        $timeEntries = $project->timeEntries()
            ->where('billable', true)
            ->whereNull('invoice_id')
            ->get();

        $expenses = $project->expenses()
            ->where('billable', true)
            ->whereNull('invoice_id')
            ->get();

        if ($timeEntries->isEmpty() && $expenses->isEmpty()) {
            throw ValidationException::withMessages([
                'project' => 'No billable time entries or expenses found for this project.'
            ]);
        }

        return DB::transaction(function () use ($workspace, $project, $data, $timeEntries, $expenses) {
            $invoice = Invoice::create([
                'workspace_id' => $workspace->id,
                'client_id' => $project->client_id,
                'project_id' => $project->id,
                'invoice_number' => $this->generateInvoiceNumber($workspace),
                'status' => 'draft',
                'issue_date' => $data['issue_date'] ?? today(),
                'due_date' => $data['due_date'],
                'tax_rate' => $data['tax_rate'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create invoice items from time entries
            foreach ($timeEntries as $entry) {
                $hours = round($entry->duration_seconds / 3600, 2);
                $rate = $entry->hourly_rate ?? $project->hourly_rate ?? 0;

                $invoice->items()->create([
                    'description' => $entry->description ?? "Time: {$entry->started_at->format('d M Y')}",
                    'quantity' => $hours,
                    'unit_price' => $rate,
                    'item_type' => 'time_entry',
                ]);

                // Mark time entry as invoiced
                $entry->update(['invoice_id' => $invoice->id]);
            }

            // Create items from expenses
            foreach ($expenses as $expense) {
                $invoice->items()->create([
                    'description' => $expense->description ?? $expense->category,
                    'quantity' => 1,
                    'unit_price' => $expense->amount,
                    'item_type' => 'expense',
                ]);

                $expense->update(['invoice_id' => $invoice->id]);
            }

            InvoiceCreated::dispatch($invoice->fresh());

            return $invoice->fresh()->load(['items', 'client', 'project']);
        });
    }

    // Send invoice to client
    public function send(Invoice $invoice): Invoice
    {
        if (! $invoice->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft invoices can be sent.'
            ]);
        }

        if ($invoice->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Cannot send an invoice without items.'
            ]);
        }

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        InvoiceSent::dispatch($invoice->fresh());

        return $invoice->fresh();
    }

    // Mark invoice as paid (manual, before payment gateway integration)
    public function markAsPaid(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, ['sent', 'overdue'])) {
            throw ValidationException::withMessages([
                'status' => 'Only sent or overdue invoices can be marked as paid.'
            ]);
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        InvoicePaid::dispatch($invoice->fresh());

        return $invoice->fresh();
    }

    // Cancel invoice
    public function cancel(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'paid') {
            throw ValidationException::withMessages([
                'status' => 'Paid invoices cannot be cancelled.'
            ]);
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice->fresh();
    }
}
