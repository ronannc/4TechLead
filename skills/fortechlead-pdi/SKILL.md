---
name: fortechlead-pdi
description: "Planeje, revise e atualize PDIs do 4TechLead usando o MCP quando o usuário pedir desenvolvimento individual, objetivos, ações ou progresso de uma pessoa. Não use para preparar ou registrar uma reunião 1:1 sem um pedido explícito de PDI."
---

# 4TechLead PDI

Use esta skill para conduzir o ciclo de desenvolvimento individual no 4TechLead. O MCP é a fonte de dados e a camada de autorização; esta skill define a sequência de consulta, a forma da proposta e a confirmação necessária antes de alterações.

## Fluxo de consulta

1. Identifique a pessoa com `people.search` quando ainda não houver um `person_id` confiável.
2. Consulte `people.get_context` para conhecer o contexto autorizado e os PDIs existentes.
3. Consulte `pdi.get_open_actions` para evitar duplicar ações abertas.
4. Consulte `kpi.get_summary` e `kpi.get_evidence` somente quando o objetivo envolver delivery ou quando evidências de entrega forem necessárias.
5. Use `one_on_one.get_history` apenas quando o usuário pedir explicitamente conexão com conversas 1:1. O contexto de 1:1 não deve ser presumido como contexto de PDI.

## Como propor um PDI

- Separe fatos observados, lacunas, hipóteses e sugestões.
- Formule objetivos específicos, mensuráveis e com horizonte claro.
- Para cada ação, informe resultado esperado, evidência, responsável e prazo quando esses dados forem conhecidos.
- Reaproveite ou atualize uma ação aberta quando ela já atender ao objetivo; não crie duplicatas.
- Mostre o rascunho completo antes de qualquer gravação.

## Alterações

- Use `pdi.create_draft` para criar um novo PDI.
- Use `pdi.update` para alterar campos do PDI.
- Use `pdi.update_action` para alterar uma ação existente.
- A primeira chamada mutável deve ser `mode=proposal`.
- Só use `mode=commit` depois de o usuário confirmar a proposta apresentada e somente com o `approval_id` correspondente.
- Nunca trate uma intenção vaga, como “pode ajustar”, como confirmação de um payload que ainda não foi mostrado.
- Se a proposta mudar depois da aprovação, gere uma nova proposta.

## Formato da resposta

Apresente, nesta ordem:

1. pessoa e período analisados;
2. evidências consultadas;
3. leitura atual do desenvolvimento;
4. objetivos sugeridos;
5. ações sugeridas ou alterações propostas;
6. dúvidas e riscos;
7. confirmação solicitada, quando houver escrita.

Não invente fatos, não exponha payloads brutos ou segredos e deixe claro o que veio do MCP e o que foi inferência da análise.
