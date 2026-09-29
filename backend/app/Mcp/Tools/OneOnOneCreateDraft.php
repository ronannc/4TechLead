<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\OneOnOneTemplate;
use App\Models\User;
use App\Services\Agent\AgentApprovalService;
use App\Services\Agent\AgentToolAuditService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Description('Proposes or commits a draft 1:1 document. Commit requires approval_id from proposal.')]
#[Name('one_on_one.create_draft')]
class OneOnOneCreateDraft extends Tool
{
    public function __construct(
        private readonly AgentApprovalService $approvalService,
        private readonly AgentToolAuditService $auditService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'mode' => ['required', 'in:proposal,commit'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['required', 'string', 'max:500'],
            'approval_id' => ['required_if:mode,commit', 'nullable', 'uuid'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return McpAuthenticationResponse::required();
        }

        $startedAt = microtime(true);
        $mode = (string) $data['mode'];
        $approvalId = $data['approval_id'] ?? null;
        $payload = [
            'title' => (string) $data['title'],
            'description' => $data['description'] ?? null,
            'questions' => array_values($data['questions']),
        ];

        try {
            Gate::forUser($user)->authorize('create', OneOnOneTemplate::class);

            if ($mode === 'proposal') {
                $approval = $this->approvalService->create($user, 'one_on_one.create_draft', $payload);
                $result = [
                    'mode' => 'proposal',
                    'requires_confirmation' => true,
                    'approval_id' => $approval['approval_id'],
                    'expires_at' => $approval['expires_at'],
                    'payload_hash' => $approval['payload_hash'],
                    'changes' => [
                        ...$payload,
                        'is_default' => false,
                        'active' => false,
                    ],
                ];
            } else {
                $result = DB::transaction(function () use ($user, $approvalId, $payload): array {
                    $this->approvalService->consume(
                        $user,
                        (string) $approvalId,
                        'one_on_one.create_draft',
                        $payload,
                    );

                    $template = OneOnOneTemplate::query()->create([
                        ...$payload,
                        'tenant_id' => $user->tenant_id,
                        'is_default' => false,
                        'active' => false,
                    ]);

                    return [
                        'mode' => 'commit',
                        'requires_confirmation' => false,
                        'draft' => [
                            'id' => $template->id,
                            'title' => $template->title,
                            'description' => $template->description,
                            'questions' => $template->questions,
                            'is_default' => $template->is_default,
                            'active' => $template->active,
                        ],
                    ];
                });
            }

            $this->auditService->record(
                $user,
                'one_on_one.create_draft',
                $mode,
                'succeeded',
                $payload,
                $result,
                null,
                $approvalId,
                (int) round((microtime(true) - $startedAt) * 1000),
            );

            return Response::structured(['data' => $result]);
        } catch (Throwable $exception) {
            $this->auditService->record(
                $user,
                'one_on_one.create_draft',
                $mode,
                'failed',
                $payload,
                null,
                $exception->getMessage(),
                $approvalId,
                (int) round((microtime(true) - $startedAt) * 1000),
            );

            throw $exception;
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->string()->description('Use proposal to request confirmation or commit with approval_id.')->enum(['proposal', 'commit'])->required(),
            'title' => $schema->string()->description('1:1 document title.')->required(),
            'description' => $schema->string()->description('Optional document description.')->nullable(),
            'questions' => $schema->array()->description('Questions to include in the document.')->items($schema->string())->required(),
            'approval_id' => $schema->string()->description('Required only for commit, from a matching proposal.')->nullable(),
        ];
    }
}
