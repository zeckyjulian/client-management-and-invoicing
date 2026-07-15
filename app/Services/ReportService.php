<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    private function resolvePeriod(string $period): array
    {
        return match($period) {
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public function financialSummary(Workspace $workspace, string $period = 'this_month'): array
    {
        $cacheKey = "workspace:{$workspace->id}:report:financial:{$period}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($workspace, $period) {
            [$startDate, $endDate] = $this->resolvePeriod($period);

            $invoices = Invoice::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->whereBetween('issue_date', [$startDate, $endDate]);

            $totalRevenue = Invoice::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('total');

            $outstanding = Invoice::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->whereIn('status', ['sent', 'overdue'])
                ->sum('total');

            $overdueAmount = Invoice::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'overdue')
                ->sum('total');

            return [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_revenue' => round($totalRevenue, 2),
                'outstanding' => round($outstanding, 2),
                'overdue_amount' => round($overdueAmount, 2),
                'invoice_count' => [
                    'draft' => (clone $invoices)->where('status', 'draft')->count(),
                    'sent' => (clone $invoices)->where('status', 'sent')->count(),
                    'paid' => (clone $invoices)->where('status', 'paid')->count(),
                    'overdue' => (clone $invoices)->where('status', 'overdue')->count(),
                    'cancelled' => (clone $invoices)->where('status', 'cancelled')->count(),
                ],
            ];
        });
    }

    public function timeReport(Workspace $workspace, string $period = 'this_month'): array
    {
        $cacheKey = "workspace:{$workspace->id}:report:time:{$period}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($workspace, $period) {
            [$startDate, $endDate] = $this->resolvePeriod($period);

            $entries = TimeEntry::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->whereBetween('started_at', [$startDate, $endDate]);

            $totalSeconds = (clone $entries)->sum('duration_seconds');
            $billableSeconds = (clone $entries)->where('billable', true)->sum('duration_seconds');

            return [
                'period' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_hours' => round($totalSeconds / 3600, 2),
                'billable_hours' => round($billableSeconds / 3600, 2),
                'non_billable_hours' => round(($totalSeconds - $billableSeconds) / 3600, 2),
                'utilization_rate' => $totalSeconds > 0
                    ? round(($billableSeconds / $totalSeconds) * 100, 2)
                    : 0,
            ];
        });
    }
}
