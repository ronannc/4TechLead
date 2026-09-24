<?php

namespace App\Mcp\Tools;

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

#[Description('Returns authorized context for a 4TechLead person, including recent 1:1 sessions and active development plans.')]
#[Name('people.get_context')]
#[IsReadOnly]
class PeopleGetContext extends Tool
{
    public function __construct(private readonly AgentContextService $contextService) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication is required to access 4TechLead data.');
        }

        return Response::structured([
            'data' => $this->contextService->getPersonContext($user, (int) $data['person_id']),
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
            'person_id' => $schema->integer()->description('The 4TechLead person identifier.')->required(),
        ];
    }
}
