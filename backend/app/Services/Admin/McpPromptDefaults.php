<?php

namespace App\Services\Admin;

final class McpPromptDefaults
{
    /**
     * @return array<string, array{title: string, description: string, content: string}>
     */
    public static function all(): array
    {
        return [
            'mcp.server.instructions' => [
                'title' => 'Instruções gerais do MCP',
                'description' => 'Orientações globais enviadas ao cliente ao iniciar uma sessão MCP.',
                'content' => 'Use ferramentas somente de leitura para consultar dados autorizados do 4TechLead e ferramentas de proposta/commit para rascunhos e notas aprovados. Escritas exigem confirmação explícita do usuário e o approval_id correspondente.',
            ],
            'one_on_one.prepare_agenda' => [
                'title' => 'Preparar pauta de 1:1',
                'description' => 'Orienta a preparação de uma pauta de 1:1 com contexto autorizado.',
                'content' => <<<'PROMPT'
Prepare uma pauta objetiva para o próximo 1:1 da pessoa {{person_id}}, com foco em "{{focus}}".

Antes de responder, consulte somente estas ferramentas autorizadas do 4TechLead:
1. people.get_context para o contexto atual;
2. one_on_one.get_history para o histórico recente;
3. pdi.get_open_actions para ações abertas de desenvolvimento;
4. kpi.get_summary e kpi.get_evidence quando o foco envolver delivery.

Estruture a resposta em: contexto observado, tópicos prioritários, perguntas abertas, ações a acompanhar e evidências utilizadas. Não invente fatos, diferencie evidência de sugestão e não crie ou altere registros sem uma confirmação explícita do usuário.
PROMPT,
            ],
            'pdi.prepare_plan' => [
                'title' => 'Preparar plano de PDI',
                'description' => 'Prepara um rascunho de PDI sem gravar alterações.',
                'content' => <<<'PROMPT'
Prepare um rascunho de PDI para a pessoa {{person_id}}, com foco em "{{focus}}" e horizonte de {{period_months}} meses.

Antes de responder, consulte somente estas ferramentas autorizadas do 4TechLead:
1. people.get_context para o contexto atual e PDIs existentes;
2. pdi.get_open_actions para evitar duplicar ações abertas;
3. kpi.get_summary e kpi.get_evidence somente se o foco envolver delivery ou evidências de entrega.

Não use o histórico de 1:1 automaticamente. Consulte one_on_one.get_history somente se o usuário pedir explicitamente que conversas anteriores sejam consideradas.

Estruture a resposta em: pessoa e período, evidências observadas, leitura atual, objetivos mensuráveis, ações propostas com prazo e evidência, riscos e perguntas em aberto. Diferencie fatos de sugestões, não invente dados e não crie ou altere registros. O resultado deve ser uma proposta para revisão explícita do usuário.
PROMPT,
            ],
            'pdi.prepare_update' => [
                'title' => 'Preparar atualização de PDI',
                'description' => 'Prepara uma proposta de alteração em um PDI autorizado sem executá-la.',
                'content' => <<<'PROMPT'
Prepare uma proposta para atualizar o PDI {{plan_id}} da pessoa {{person_id}}.

Mudanças solicitadas pelo usuário:
{{requested_changes}}

Consulte people.get_context e pdi.get_open_actions para validar o contexto autorizado e identificar ações relacionadas. Use kpi.get_summary e kpi.get_evidence somente se a mudança depender de evidências de delivery.

Apresente: estado atual relevante, mudanças propostas campo a campo, ações que seriam criadas, atualizadas ou encerradas, justificativa, impacto, riscos e dúvidas. Não execute pdi.create_action, pdi.update nem pdi.update_action nesta etapa. Depois da revisão do usuário, a escrita deve começar com uma proposta mutável e só pode ser confirmada com approval_id correspondente; qualquer mudança no payload exige nova aprovação.
PROMPT,
            ],
            'pdi.review_progress' => [
                'title' => 'Revisar progresso do PDI',
                'description' => 'Analisa o progresso de um PDI autorizado sem alterar registros.',
                'content' => <<<'PROMPT'
Revise o progresso do PDI da pessoa {{person_id}}, com foco em "{{focus}}".

Consulte people.get_context e pdi.get_open_actions. Consulte kpi.get_summary e kpi.get_evidence somente quando houver relação com delivery ou quando forem necessárias evidências objetivas de entrega.

Informe: objetivos e ações encontrados, avanços, ações atrasadas ou sem evidência, bloqueios, riscos, recomendações e perguntas para a próxima revisão. Diferencie dados retornados pelo MCP de inferências, não invente progresso e não altere nenhum registro. Se uma alteração parecer necessária, apresente-a apenas como proposta e indique qual ferramenta mutável seria usada depois da confirmação.
PROMPT,
            ],
        ];
    }
}
