<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\OneOnOneGetHistory;
use App\Mcp\Tools\PeopleGetContext;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Four Tech Lead Server')]
#[Version('0.1.0')]
#[Instructions('Read-only access to authorized 4TechLead leadership context, 1:1 history and development plans.')]
class FourTechLeadServer extends Server
{
    protected array $tools = [
        PeopleGetContext::class,
        OneOnOneGetHistory::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
