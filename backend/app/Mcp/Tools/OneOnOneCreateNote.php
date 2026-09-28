<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\OneOnOneSession;
use App\Models\Person;
use App\Models\PersonOneOnOneNote;
use App\Models\User;
use App\Services\Agent\AgentApprovalService;
use App\Services\Agent\AgentToolAuditService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

#[Description('Proposes or commits a note for a future 1:1. Commit requires a fresh approval_id from proposal.')]
#[Name('one_on_one.create_note')]
class OneOnOneCreateNote extends Tool
{
    public function __construct(
        private readonly AgentApprovalService $approvalService,
        private readonly AgentToolAuditService $auditService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'mode' => ['required', 'in:proposal,commit'],
            'person_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'occurred_at' => ['sometimes', 'nullable', 'date'],
            'one_on_one_session_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
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
            'person_id' => (int) $data['person_id'],
            'title' => (string) $data['title'],
            'body' => $data['body'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? null,
            'one_on_one_session_id' => isset($data['one_on_one_session_id'])
                ? (int) $data['one_on_one_session_id']
                : null,
        ];

        try {
            $this->authorizePayload($user, $payload);

            if ($mode === 'proposal') {
                $approval = $this->approvalService->create($user, 'one_on_one.create_note', $payload);
                $result = [
                    'mode' => 'proposal',
                    'requires_confirmation' => true,
                    'approval_id' => $approval['approval_id'],
                    'expires_at' => $approval['expires_at'],
                    'payload_hash' => $approval['payload_hash'],
                    'changes' => $payload,
                ];
            } else {
                $result = DB::transaction(function () use ($user, $approvalId, $payload): array {
                    $this->approvalService->consume(
                        $user,
                        (string) $approvalId,
                        'one_on_one.create_note',
                        $payload,
                    );

                    $note = PersonOneOnOneNote::query()->create([
                        ...$payload,
                        'tenant_id' => $user->tenant_id,
                        'created_by' => $user->id,
                        'status' => 'open',
                    ]);

                    return [
                        'mode' => 'commit',
                        'requires_confirmation' => false,
                        'note' => $this->noteData($note),
                    ];
                });
            }

            $this->auditService->record(
                $user,
                'one_on_one.create_note',
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
                'one_on_one.create_note',
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
            'person_id' => $schema->integer()->description('The person who owns the future 1:1 note.')->required(),
            'title' => $schema->string()->description('Short title for the note.')->required(),
            'body' => $schema->string()->description('Optional note body.')->nullable(),
            'occurred_at' => $schema->string()->description('Optional date in YYYY-MM-DD format.')->nullable(),
            'one_on_one_session_id' => $schema->integer()->description('Optional related 1:1 session identifier.')->nullable(),
            'approval_id' => $schema->string()->description('Required only for commit, from a matching proposal.')->nullable(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function authorizePayload(User $user, array $payload): void
    {
        Gate::forUser($user)->authorize('create', PersonOneOnOneNote::class);

        $person = Person::query()
            ->when($user->tenant_id !== null, fn ($query) => $query->where('tenant_id', $user->tenant_id))
            ->findOrFail($payload['person_id']);

        Gate::forUser($user)->authorize('view', $person);

        if ($payload['one_on_one_session_id'] === null) {
            return;
        }

        $exists = OneOnOneSession::query()
            ->whereKey($payload['one_on_one_session_id'])
            ->where('person_id', $person->id)
            ->exists();

        if (! $exists) {
            throw (new ModelNotFoundException)->setModel(OneOnOneSession::class, [$payload['one_on_one_session_id']]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function noteData(PersonOneOnOneNote $note): array
    {
        return [
            'id' => $note->id,
            'person_id' => $note->person_id,
            'created_by' => $note->created_by,
            'one_on_one_session_id' => $note->one_on_one_session_id,
            'title' => $note->title,
            'body' => $note->body,
            'status' => $note->status,
            'occurred_at' => $note->occurred_at?->toDateString(),
        ];
    }
}
