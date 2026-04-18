<?php

use App\Http\Controllers\Api\Admin\TransactionController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth:sanctum', 'super_admin'])->group(function () {

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Workspace
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::get('/workspaces/{id}', [WorkspaceController::class, 'show']);
    Route::patch('/workspaces/{id}/plan', [WorkspaceController::class, 'updatePlan']);
    Route::delete('/workspaces/{id}', [WorkspaceController::class, 'destroy']);

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

        Route::middleware('workspace')->group(function () {
            Route::get('/workspace', [WorkspaceController::class, 'show']);
            Route::patch('/workspace', [WorkspaceController::class, 'update'])->middleware('workspace.role:owner,admin');

            Route::prefix('workspace/members')->group(function () {
                Route::get('/', [WorkspaceMemberController::class, 'index']);
                Route::post('/invite', [WorkspaceMemberController::class, 'invite'])->middleware('workspace.role:owner,admin');
                Route::patch('/{userId}/role', [WorkspaceMemberController::class, 'updateRole'])->middleware('workspace.role:owner');
                Route::delete('/{userId}', [WorkspaceMemberController::class, 'remove'])->middleware('workspace.role:owner');
            });
        });
    });
});
