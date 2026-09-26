<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\KpiGetEvidence;
use App\Mcp\Tools\KpiGetSummary;
use App\Mcp\Tools\OneOnOneGetHistory;
use App\Mcp\Tools\PdiGetOpenActions;
use App\Mcp\Tools\PeopleGetContext;
use App\Mcp\Tools\PeopleSearch;
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
        PeopleSearch::class,
        PeopleGetContext::class,
        OneOnOneGetHistory::class,
        PdiGetOpenActions::class,
        KpiGetSummary::class,
        KpiGetEvidence::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
