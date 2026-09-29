<?php

namespace App\Mcp\Resources;

use App\Mcp\McpAuthenticationResponse;
use App\Models\User;
use App\Services\Agent\AgentContextService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Authorized 4TechLead context for one person, including recent 1:1 sessions and active development plans.')]
#[Name('person_context')]
class PersonContextResource extends Resource implements HasUriTemplate
{
    protected string $mimeType = 'application/json';

    public function __construct(private readonly AgentContextService $contextService) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('fourtechlead://people/{person_id}/context');
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return McpAuthenticationResponse::required();
        }

        $personId = $request->get('person_id');

        if (! is_numeric($personId) || (int) $personId < 1) {
            return Response::error('A valid person_id is required.');
        }

        return Response::json([
            'data' => $this->contextService->getPersonContext($user, (int) $personId),
        ]);
    }
}
