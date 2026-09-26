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

#[Description('Searches authorized 4TechLead people by name and returns identifiers for subsequent context or 1:1 history queries.')]
#[Name('people.search')]
#[IsReadOnly]
class PeopleSearch extends Tool
{
    public function __construct(private readonly AgentContextService $contextService) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return McpAuthenticationResponse::required();
        }

        return Response::structured([
            'data' => $this->contextService->searchPeople(
                $user,
                $data['query'],
                (int) ($data['limit'] ?? 10),
            ),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Full or partial name of the 4TechLead person.')->required(),
            'limit' => $schema->integer()->description('Maximum number of people to return, between 1 and 50.')->default(10),
        ];
    }
}
