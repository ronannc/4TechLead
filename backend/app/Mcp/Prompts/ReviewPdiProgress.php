<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class ReviewPdiProgress extends Prompt
{
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

        return Response::text(<<<PROMPT
Revise o progresso do PDI da pessoa {$data['person_id']}, com foco em "{$focus}".

Consulte people.get_context e pdi.get_open_actions. Consulte kpi.get_summary e kpi.get_evidence somente quando houver relação com delivery ou quando forem necessárias evidências objetivas de entrega.

Informe: objetivos e ações encontrados, avanços, ações atrasadas ou sem evidência, bloqueios, riscos, recomendações e perguntas para a próxima revisão. Diferencie dados retornados pelo MCP de inferências, não invente progresso e não altere nenhum registro. Se uma alteração parecer necessária, apresente-a apenas como proposta e indique qual ferramenta mutável seria usada depois da confirmação.
PROMPT);
    }
}
