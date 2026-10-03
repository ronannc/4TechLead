<?php

namespace App\Mcp\Prompts;

use App\Services\Admin\McpPromptConfigurationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PreparePdiUpdate extends Prompt
{
    public function __construct(private readonly McpPromptConfigurationService $promptConfigurations) {}

    protected string $name = 'pdi.prepare_update';

    protected string $title = 'Preparar atualização de PDI';

    protected string $description = 'Prepara uma proposta de alteração em um PDI autorizado sem executar a alteração.';

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('person_id', 'Identificador da pessoa no 4TechLead.', true),
            new Argument('plan_id', 'Identificador do PDI que será analisado.', true),
            new Argument('requested_changes', 'Descrição das mudanças desejadas pelo usuário.', true),
        ];
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'person_id' => ['required', 'integer', 'min:1'],
            'plan_id' => ['required', 'integer', 'min:1'],
            'requested_changes' => ['required', 'string', 'max:2000'],
        ]);

        return Response::text($this->promptConfigurations->resolve('pdi.prepare_update', [
            'person_id' => $data['person_id'],
            'plan_id' => $data['plan_id'],
            'requested_changes' => $data['requested_changes'],
        ]));
    }
}
