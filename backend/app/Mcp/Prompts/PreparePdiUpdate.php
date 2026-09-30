<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PreparePdiUpdate extends Prompt
{
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

        return Response::text(<<<PROMPT
Prepare uma proposta para atualizar o PDI {$data['plan_id']} da pessoa {$data['person_id']}.

Mudanças solicitadas pelo usuário:
{$data['requested_changes']}

Consulte people.get_context e pdi.get_open_actions para validar o contexto autorizado e identificar ações relacionadas. Use kpi.get_summary e kpi.get_evidence somente se a mudança depender de evidências de delivery.

        Apresente: estado atual relevante, mudanças propostas campo a campo, ações que seriam criadas, atualizadas ou encerradas, justificativa, impacto, riscos e dúvidas. Não execute pdi.create_action, pdi.update nem pdi.update_action nesta etapa. Depois da revisão do usuário, a escrita deve começar com uma proposta mutável e só pode ser confirmada com approval_id correspondente; qualquer mudança no payload exige nova aprovação.
PROMPT);
    }
}
