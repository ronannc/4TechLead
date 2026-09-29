<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PrepareOneOnOneAgenda extends Prompt
{
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

        return Response::text(<<<PROMPT
Prepare uma pauta objetiva para o próximo 1:1 da pessoa {$data['person_id']}, com foco em "{$focus}".

Antes de responder, consulte somente estas ferramentas autorizadas do 4TechLead:
1. people.get_context para o contexto atual;
2. one_on_one.get_history para o histórico recente;
3. pdi.get_open_actions para ações abertas de desenvolvimento;
4. kpi.get_summary e kpi.get_evidence quando o foco envolver delivery.

Estruture a resposta em: contexto observado, tópicos prioritários, perguntas abertas, ações a acompanhar e evidências utilizadas. Não invente fatos, diferencie evidência de sugestão e não crie ou altere registros sem uma confirmação explícita do usuário.
PROMPT);
    }
}
