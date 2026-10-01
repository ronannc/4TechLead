---
name: fortechlead-pdi-registration
description: "Registre no 4TechLead um PDI previamente validado em entrevista, preservando PDIs anteriores e criando ações estruturadas em ordem de dependência."
---

# Registro de PDI no 4TechLead

Use esta skill somente depois de existir um rascunho validado e concordância explícita da pessoa. Consultas e mutações de dados usam somente os MCPs autorizados do 4TechLead.

O input obrigatório é o contrato `status: validado` produzido pela skill de entrevista, incluindo `person_id`, conteúdo do PDI, ações e `agreement.agreed: true`. Se esse contrato não existir, pare no planejamento.

## Pré-condições

- confirme a pessoa com `mcp__4techlead__people_search`;
- consulte `mcp__4techlead__people_get_context` e `mcp__4techlead__pdi_get_open_actions`;
- mantenha separados PDI histórico e nova fase quando solicitado;
- não substitua respostas da entrevista por inferências de cargo, métricas ou 1:1.

## Fluxo

1. Leia o PDI atual e identifique ações no texto, ações estruturadas e possíveis duplicatas.
2. Quando ações estiverem no resumo, extraia-as sem alterar seu sentido, crie ações estruturadas e remova somente a seção de ações do resumo.
3. Para uma nova fase, use `mcp__4techlead__pdi_create_draft`; não reescreva o PDI histórico para representar a fase nova.
4. Ordene as ações por dependência: requisitos/modelagem, SQL, complexidade/desempenho e depois API/testes.
5. Faça primeiro `mode=proposal`; só faça `mode=commit` após confirmação explícita usando o `approval_id` correspondente.
6. Use `mcp__4techlead__pdi_update`, `mcp__4techlead__pdi_update_action` e `mcp__4techlead__pdi_create_action` conforme a operação.
7. Se não houver exclusão física suportada para uma ação, não apague dados por meios alternativos: use o status de cancelamento/arquivamento suportado e informe que ela saiu da lista ativa, preservando o histórico.
8. Se a API não for transacional, confirme o resultado após cada grupo de commits. Em caso de falha parcial, reconsulte o contexto, não repita mutações já efetivadas e apresente o que ficou pendente.
9. Após o commit, consulte novamente o contexto e confirme PDI antigo preservado, resumo limpo, ações presentes, novo PDI ativo e ordem correta.

## Qualidade

Não registre “estudar backend”. Registre problema, contexto e resultado, por exemplo: “modelar o banco de um app de gerenciamento de moto a partir de requisitos de motos, abastecimentos, manutenções, revisões e acessórios”.

Informe no final o que foi lido, alterado e preservado. Diferencie proposta de registro efetivado.

Inclua a concordância no resumo ou no artefato suportado pelo MCP, pois o PDI precisa conservar o acordo firmado e os pontos que ficaram para revisão.
