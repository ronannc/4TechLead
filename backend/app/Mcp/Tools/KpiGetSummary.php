<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\User;
use App\Services\Agent\AgentContextService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Returns authorized delivery KPI aggregates for the 4TechLead tenant, optionally filtered by person and period.')]
#[Name('kpi.get_summary')]
#[IsReadOnly]
class KpiGetSummary extends Tool
{
    public function __construct(private readonly AgentContextService $contextService) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'person_id' => ['sometimes', 'integer', 'min:1'],
            'period_start' => ['sometimes', 'date'],
            'period_end' => ['sometimes', 'date', 'after_or_equal:period_start'],
            'sprint' => ['sometimes', 'string', 'max:255'],
            'minimum_confidence' => ['sometimes', 'in:high,medium,low'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return McpAuthenticationResponse::required();
        }

        return Response::structured([
            'data' => $this->contextService->getKpiSummary($user, $data),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'person_id' => $schema->integer()->description('Optional 4TechLead person identifier.')->nullable(),
            'period_start' => $schema->string()->description('Optional inclusive period start date.')->nullable(),
            'period_end' => $schema->string()->description('Optional inclusive period end date.')->nullable(),
            'sprint' => $schema->string()->description('Optional sprint reference.')->nullable(),
            'minimum_confidence' => $schema->string()->description('Minimum association confidence: high, medium or low.')->enum(['high', 'medium', 'low'])->default('low'),
        ];
    }
}
