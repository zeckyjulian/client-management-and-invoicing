<?php

use App\Http\Controllers\Api\Admin\TransactionController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\WorkspaceController as AdminWorkspaceController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InvoiceItemController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'super_admin'])->group(function () {

    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::patch('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // Workspace
    Route::get('/workspaces', [AdminWorkspaceController::class, 'index']);
    Route::get('/workspaces/{id}', [AdminWorkspaceController::class, 'show']);
    Route::patch('/workspaces/{id}/plan', [AdminWorkspaceController::class, 'updatePlan']);
    Route::delete('/workspaces/{id}', [AdminWorkspaceController::class, 'destroy']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
});

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);

        Route::middleware('workspace')->group(function () {
            Route::get('/workspace', [WorkspaceController::class, 'show']);
            Route::patch('/workspace', [WorkspaceController::class, 'update'])->middleware('workspace.role:owner,admin');

            Route::prefix('workspace/members')->group(function () {
                Route::get('/', [WorkspaceMemberController::class, 'index']);
                Route::post('/invite', [WorkspaceMemberController::class, 'invite'])->middleware('workspace.role:owner,admin');
                Route::patch('/{userId}/role', [WorkspaceMemberController::class, 'updateRole'])->middleware('workspace.role:owner');
                Route::delete('/{userId}', [WorkspaceMemberController::class, 'remove'])->middleware('workspace.role:owner');
            });

            // Clients
            Route::apiResource('clients', ClientController::class);

            // Projects
            Route::apiResource('projects', ProjectController::class);

            // Tasks (nested under projects)
            Route::apiResource('projects/{projectId}/tasks', TaskController::class)->except(['index']);
            Route::get('projects/{projectId}/tasks', [TaskController::class, 'index']);

            // Time Entries
            Route::get('time-entries/running', [TimeEntryController::class, 'running']);
            Route::post('projects/{projectId}/time-entries/start', [TimeEntryController::class, 'start']);
            Route::patch('time-entries/{entryId}/stop', [TimeEntryController::class, 'stop']);
            Route::post('projects/{projectId}/time-entries/manual', [TimeEntryController::class, 'storeManual']);
            Route::get('projects/{projectId}/time-entries', [TimeEntryController::class, 'index']);
            Route::delete('time-entries/{entryId}', [TimeEntryController::class, 'destroy']);

            // Expenses
            Route::apiResource('expenses', ExpenseController::class)->except(['show']);

            // Invoices
            Route::get('invoices', [InvoiceController::class, 'index']);
            Route::post('invoices', [InvoiceController::class, 'store']);
            Route::post('invoices/generate/{projectId}', [InvoiceController::class, 'generateFromProject']);
            Route::get('invoices/{id}', [InvoiceController::class, 'show']);
            Route::patch('invoices/{id}', [InvoiceController::class, 'update']);
            Route::post('invoices/{id}/send', [InvoiceController::class, 'send']);
            Route::post('invoices/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);
            Route::post('invoices/{id}/cancel', [InvoiceController::class, 'cancel']);
            Route::delete('invoices/{id}', [InvoiceController::class, 'destroy']);

            // Invoice Items
            Route::post('invoices/{invoiceId}/items', [InvoiceItemController::class, 'store']);
            Route::patch('invoices/{invoiceId}/items/{itemId}', [InvoiceItemController::class, 'update']);
            Route::delete('invoices/{invoiceId}/items/{itemId}', [InvoiceItemController::class, 'destroy']);

            // Reports
            Route::get('reports/financial', [ReportController::class, 'financial']);
            Route::get('reports/time', [ReportController::class, 'time']);
        });
    });
});
