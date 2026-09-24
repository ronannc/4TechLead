<?php

use App\Mcp\Servers\FourTechLeadServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware('auth:sanctum')->group(function (): void {
    Mcp::web('mcp/4techlead', FourTechLeadServer::class);
});
