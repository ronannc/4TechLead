<?php

namespace App\Mcp\Prompts;

use App\Services\Admin\McpPromptConfigurationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PreparePdiPlan extends Prompt
{
    public function __construct(private readonly McpPromptConfigurationService $promptConfigurations) {}

    protected string $name = 'pdi.prepare_plan';

    protected string $title = 'Preparar plano de PDI';

    protected string $description = 'Prepara um rascunho de PDI baseado em dados autorizados, sem gravar alterações.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('person_id', 'Identificador da pessoa no 4TechLead.', true),
            new Argument('focus', 'Foco opcional do desenvolvimento, como autonomia, liderança ou comunicação.', false),
            new Argument('period_months', 'Horizonte sugerido do PDI em meses.', false),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'min:1'],
            'focus' => ['sometimes', 'nullable', 'string', 'max:255'],
            'period_months' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $focus = $data['focus'] ?? 'desenvolvimento geral';
        $period = $data['period_months'] ?? 3;

        return Response::text($this->promptConfigurations->resolve('pdi.prepare_plan', [
            'person_id' => $data['person_id'],
            'focus' => $focus,
            'period_months' => $period,
        ]));
    }
}
