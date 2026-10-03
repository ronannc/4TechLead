<?php

namespace App\Mcp\Prompts;

use App\Services\Admin\McpPromptConfigurationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PrepareOneOnOneAgenda extends Prompt
{
    public function __construct(private readonly McpPromptConfigurationService $promptConfigurations) {}

    protected string $name = 'one_on_one.prepare_agenda';

    protected string $title = 'Preparar pauta de 1:1';

    protected string $description = 'Orienta a preparação de uma pauta de 1:1 baseada somente em dados autorizados do 4TechLead.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('person_id', 'Identificador da pessoa no 4TechLead.', true),
            new Argument('focus', 'Foco opcional da conversa, como autonomia, feedback ou carreira.', false),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'min:1'],
            'focus' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $focus = $data['focus'] ?? 'desenvolvimento geral';

        return Response::text($this->promptConfigurations->resolve('one_on_one.prepare_agenda', [
            'person_id' => $data['person_id'],
            'focus' => $focus,
        ]));
    }
}
