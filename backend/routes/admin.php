<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AccessLogController;
use App\Http\Controllers\Admin\McpPromptController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('login.store');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('access-logs', [AccessLogController::class, 'index'])->name('access-logs.index');
        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::post('tenants/{tenant}/block', [TenantController::class, 'block'])->name('tenants.block');
        Route::post('tenants/{tenant}/unblock', [TenantController::class, 'unblock'])->name('tenants.unblock');
        Route::get('mcp/prompts', [McpPromptController::class, 'index'])->name('mcp-prompts.index');
        Route::get('mcp/prompts/{promptKey}', [McpPromptController::class, 'show'])->name('mcp-prompts.show');
        Route::post('mcp/prompts/{promptKey}/draft', [McpPromptController::class, 'saveDraft'])->name('mcp-prompts.draft');
        Route::post('mcp/prompt-versions/{version}/publish', [McpPromptController::class, 'publish'])->name('mcp-prompts.publish');
        Route::post('mcp/prompt-versions/{version}/restore', [McpPromptController::class, 'restore'])->name('mcp-prompts.restore');
    });
});
