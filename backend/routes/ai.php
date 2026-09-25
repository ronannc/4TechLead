<?php

use App\Http\Middleware\AuthenticateMcp;
use App\Mcp\Http\McpApproveAuthorizationController;
use App\Mcp\Servers\FourTechLeadServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Route::post('oauth/mcp/authorize', McpApproveAuthorizationController::class)
    ->middleware(['web', 'auth:web'])
    ->name('mcp.oauth.authorize.approve');

Route::middleware(AuthenticateMcp::class)->group(function (): void {
    Mcp::web('mcp/4techlead', FourTechLeadServer::class);
});
