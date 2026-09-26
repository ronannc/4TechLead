<?php

use App\Enums\UserRole;
use App\Mcp\McpAuthenticationResponse;
use App\Mcp\Servers\FourTechLeadServer;
use App\Mcp\Tools\OneOnOneGetHistory;
use App\Mcp\Tools\PeopleGetContext;
use App\Mcp\Tools\PeopleSearch;
use App\Models\OneOnOneSession;
use App\Models\Person;
use App\Models\User;
use App\Services\Agent\AgentContextService;
use Laravel\Passport\ClientRepository;

it('exposes the first read-only tools with structured responses', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Ada Lovelace']);
    $session = OneOnOneSession::factory()->create([
        'person_id' => $person->id,
        'title' => '1:1 sobre autonomia',
        'held_at' => '2026-09-20',
        'notes' => 'Revisamos decisões técnicas e próximos passos.',
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PeopleGetContext::class, ['person_id' => $person->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.person.id', $person->id)
            ->where('data.person.name', 'Ada Lovelace')
            ->where('data.recent_one_on_ones.0.id', $session->id)
            ->etc());

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneGetHistory::class, [
            'person_id' => $person->id,
            'limit' => 1,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.person_id', $person->id)
            ->has('data.sessions', 1)
            ->where('data.sessions.0.title', '1:1 sobre autonomia')
            ->etc());
});

it('publishes stable domain tool names and read-only annotations', function () {
    $searchTool = new PeopleSearch(app(AgentContextService::class));
    $peopleTool = new PeopleGetContext(app(AgentContextService::class));
    $historyTool = new OneOnOneGetHistory(app(AgentContextService::class));

    expect($searchTool->toArray())
        ->name->toBe('people.search')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($peopleTool->toArray())
        ->name->toBe('people.get_context')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($historyTool->toArray())
        ->name->toBe('one_on_one.get_history')
        ->annotations->toHaveKey('readOnlyHint', true);
});

it('searches authorized people by partial name through MCP', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Lucas Farias']);
    Person::factory()->create(['name' => 'Mariana Souza']);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PeopleSearch::class, ['query' => 'lucas'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.query', 'lucas')
            ->has('data.people', 1)
            ->where('data.people.0.id', $person->id)
            ->where('data.people.0.name', 'Lucas Farias')
            ->etc());
});

it('limits a member MCP person search to their linked person', function () {
    $ownedPerson = Person::factory()->create(['name' => 'Lucas Farias']);
    Person::factory()->create(['name' => 'Lucas Martins']);
    $member = User::factory()->member()->create(['person_id' => $ownedPerson->id]);

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(PeopleSearch::class, ['query' => 'Lucas'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->has('data.people', 1)
            ->where('data.people.0.id', $ownedPerson->id)
            ->where('data.people.0.name', 'Lucas Farias')
            ->etc());
});

it('does not allow a member to read another person through MCP', function () {
    $ownedPerson = Person::factory()->create();
    $otherPerson = Person::factory()->create();
    $member = User::factory()->member()->create([
        'person_id' => $ownedPerson->id,
        'role' => UserRole::Member,
    ]);

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(OneOnOneGetHistory::class, ['person_id' => $otherPerson->id])
        ->assertHasErrors(['This action is unauthorized.']);
});

it('rejects an unauthenticated MCP tool call', function () {
    FourTechLeadServer::tool(PeopleGetContext::class, ['person_id' => 1])
        ->assertHasErrors(['Authentication is required']);
});

it('advertises OAuth reauthentication for unauthenticated MCP tool calls', function () {
    expect(McpAuthenticationResponse::required()->getMeta())
        ->toHaveKey('mcp/www_authenticate.0')
        ->and(McpAuthenticationResponse::required()->getMeta()['mcp/www_authenticate'][0])
        ->toContain('resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/4techlead').'"')
        ->toContain('scope="mcp:use"');
});

it('protects the remote MCP endpoint with Sanctum', function () {
    $this->postJson('/mcp/4techlead', [])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/4techlead').'", scope="mcp:use"');
});

it('publishes OAuth metadata for the remote MCP endpoint', function () {
    $this->getJson('/.well-known/oauth-protected-resource/mcp/4techlead')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp/4techlead'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');

    $this->getJson('/.well-known/oauth-authorization-server/mcp/4techlead')
        ->assertOk()
        ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
        ->assertJsonPath('token_endpoint', url('/oauth/token'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');
});

it('registers the MCP authorization view and web login entrypoint', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Entrar no 4TechLead');

    expect(view()->exists('mcp.authorize'))->toBeTrue();
});

it('completes the Passport MCP authorization flow with the real consent screen', function () {
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($key, $privateKey);
    $publicKey = openssl_pkey_get_details($key)['key'];
    config([
        'passport.private_key' => $privateKey,
        'passport.public_key' => $publicKey,
    ]);

    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Codex',
        ['https://client.example/callback'],
        confidential: false,
    );
    $verifier = 'verifier-that-is-long-enough-for-pkce-1234567890';
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $query = http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://client.example/callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'test-state',
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]);

    $authorization = $this->actingAs($user, 'web')
        ->get('/oauth/authorize?'.$query)
        ->assertOk();

    $authorization->assertSee('Autorizar acesso ao Codex');
    $authToken = $authorization->viewData('authToken');

    $approval = $this->post('/oauth/mcp/authorize', [
        '_token' => csrf_token(),
        'auth_token' => $authToken,
    ])->assertRedirect();

    parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

    expect($redirectQuery)
        ->toHaveKey('code')
        ->toHaveKey('state', 'test-state');

    $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://client.example/callback',
        'code' => $redirectQuery['code'],
        'code_verifier' => $verifier,
    ])->assertOk()
        ->assertJsonStructure(['token_type', 'expires_in', 'access_token']);
});

it('keeps concurrent MCP authorizations valid for the same signed-in user', function () {
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($key, $privateKey);
    $publicKey = openssl_pkey_get_details($key)['key'];
    config([
        'passport.private_key' => $privateKey,
        'passport.public_key' => $publicKey,
    ]);

    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Codex',
        ['https://client.example/callback'],
        confidential: false,
    );
    $query = http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://client.example/callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'first-request',
        'code_challenge' => 'iyjvC_1uGXib37nE6TMkVFLrjFwsDE-NKfQx2mJHPPY',
        'code_challenge_method' => 'S256',
    ]);

    $firstAuthorization = $this->actingAs($user, 'web')
        ->get('/oauth/authorize?'.$query)
        ->assertOk();
    $firstToken = $firstAuthorization->viewData('authToken');

    $this->get('/oauth/authorize?'.$query)
        ->assertOk();

    $this->post('/oauth/mcp/authorize', [
        '_token' => csrf_token(),
        'auth_token' => $firstToken,
    ])->assertRedirect()
        ->assertRedirectContains('state=first-request');
});

it('completes the MCP initialize handshake for an authenticated client', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/mcp/4techlead', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => '4techlead-test-client',
                    'version' => '1.0.0',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.serverInfo.name', 'Four Tech Lead Server')
        ->assertJsonPath('result.instructions', 'Read-only access to authorized 4TechLead leadership context, 1:1 history and development plans.');
});

it('propagates the authenticated HTTP guard to MCP tool calls', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Lucas Farias']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/mcp/4techlead', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'people.search',
                'arguments' => ['query' => 'Lucas'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.data.people.0.id', $person->id)
        ->assertJsonPath('result.structuredContent.data.people.0.name', 'Lucas Farias');
});
