<?php

namespace App\Mcp\Prompts;

use App\Services\Admin\McpPromptConfigurationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class ReviewPdiProgress extends Prompt
{
    public function __construct(private readonly McpPromptConfigurationService $promptConfigurations) {}

    protected string $name = 'pdi.review_progress';

    protected string $title = 'Revisar progresso do PDI';

    protected string $description = 'Analisa o progresso de um PDI autorizado sem alterar seus registros.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('person_id', 'Identificador da pessoa no 4TechLead.', true),
            new Argument('focus', 'Aspecto opcional da revisão, como atrasos, evidências ou próximos passos.', false),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'min:1'],
            'focus' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $focus = $data['focus'] ?? 'progresso geral';

        return Response::text($this->promptConfigurations->resolve('pdi.review_progress', [
            'person_id' => $data['person_id'],
            'focus' => $focus,
        ]));
    }
}
