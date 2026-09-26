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

#[Description('Returns open actions from authorized active development plans for a 4TechLead person.')]
#[Name('pdi.get_open_actions')]
#[IsReadOnly]
class PdiGetOpenActions extends Tool
{
    public function __construct(private readonly AgentContextService $contextService) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return McpAuthenticationResponse::required();
        }

        return Response::structured([
            'data' => $this->contextService->getOpenPdiActions(
                $user,
                (int) $data['person_id'],
                (int) ($data['limit'] ?? 25),
            ),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'person_id' => $schema->integer()->description('The 4TechLead person identifier.')->required(),
            'limit' => $schema->integer()->description('Maximum number of open actions to return, between 1 and 50.')->default(25),
        ];
    }
}
