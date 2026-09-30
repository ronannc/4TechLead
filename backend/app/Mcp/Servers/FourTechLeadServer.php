<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\PrepareOneOnOneAgenda;
use App\Mcp\Resources\PersonContextResource;
use App\Mcp\Tools\KpiGetEvidence;
use App\Mcp\Tools\KpiGetSummary;
use App\Mcp\Tools\OneOnOneCreateDraft;
use App\Mcp\Tools\OneOnOneCreateNote;
use App\Mcp\Tools\OneOnOneGetHistory;
use App\Mcp\Tools\PdiCreateDraft;
use App\Mcp\Tools\PdiGetOpenActions;
use App\Mcp\Tools\PdiUpdate;
use App\Mcp\Tools\PdiUpdateAction;
use App\Mcp\Tools\PeopleGetContext;
use App\Mcp\Tools\PeopleSearch;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Four Tech Lead Server')]
#[Version('0.1.0')]
#[Instructions('Access authorized 4TechLead leadership data through read-only tools, plus proposal/commit tools for approved drafts and notes. Writes require explicit confirmation and a matching approval_id.')]
class FourTechLeadServer extends Server
{
    protected array $tools = [
        PeopleSearch::class,
        PeopleGetContext::class,
        OneOnOneGetHistory::class,
        OneOnOneCreateNote::class,
        OneOnOneCreateDraft::class,
        PdiGetOpenActions::class,
        PdiCreateDraft::class,
        PdiUpdate::class,
        PdiUpdateAction::class,
        KpiGetSummary::class,
        KpiGetEvidence::class,
    ];

    protected array $resources = [
        PersonContextResource::class,
    ];

    protected array $prompts = [
        PrepareOneOnOneAgenda::class,
    ];
}
