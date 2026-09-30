<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PreparePdiPlan extends Prompt
{
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

        return Response::text(<<<PROMPT
Prepare um rascunho de PDI para a pessoa {$data['person_id']}, com foco em "{$focus}" e horizonte de {$period} meses.

Antes de responder, consulte somente estas ferramentas autorizadas do 4TechLead:
1. people.get_context para o contexto atual e PDIs existentes;
2. pdi.get_open_actions para evitar duplicar ações abertas;
3. kpi.get_summary e kpi.get_evidence somente se o foco envolver delivery ou evidências de entrega.

Não use o histórico de 1:1 automaticamente. Consulte one_on_one.get_history somente se o usuário pedir explicitamente que conversas anteriores sejam consideradas.

Estruture a resposta em: pessoa e período, evidências observadas, leitura atual, objetivos mensuráveis, ações propostas com prazo e evidência, riscos e perguntas em aberto. Diferencie fatos de sugestões, não invente dados e não crie ou altere registros. O resultado deve ser uma proposta para revisão explícita do usuário.
PROMPT);
    }
}
