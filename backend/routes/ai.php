<?php

use App\Http\Middleware\AuthenticateMcp;
use App\Mcp\Servers\FourTechLeadServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Route::middleware(AuthenticateMcp::class)->group(function (): void {
    Mcp::web('mcp/4techlead', FourTechLeadServer::class);
});
