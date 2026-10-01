---
name: fortechlead-pdi
description: "Planeje, revise e atualize PDIs do 4TechLead usando o MCP quando o usuário pedir desenvolvimento individual, objetivos, ações ou progresso de uma pessoa. Não use para preparar ou registrar uma reunião 1:1 sem um pedido explícito de PDI."
---

# 4TechLead PDI

Esta é a skill principal e roteia os fluxos de desenvolvimento individual do 4TechLead. O escopo inicial cobre PDI; novas skills de 1:1 devem seguir as mesmas regras de acesso ao MCP e confirmação antes de mutações.

- **Entrevista e planejamento:** conduza a conversa com a pessoa e produza um rascunho validado. Consulte `skills/fortechlead-pdi-interview/SKILL.md`.
- **Registro:** depois da concordância explícita, persista o PDI e as ações pelo MCP. Consulte `skills/fortechlead-pdi-registration/SKILL.md`.

Não pule a entrevista para criar um PDI com suposições. Não registre um PDI antes de a pessoa concordar com o conteúdo.

## Roteamento

- Criação ou revisão do conteúdo com a pessoa: leia `skills/fortechlead-pdi-interview/SKILL.md` antes de agir.
- Registro, edição, migração ou exclusão de PDI/ação: leia `skills/fortechlead-pdi-registration/SKILL.md` antes de agir.
- Pedido de 1:1: use a skill de 1:1 do projeto quando existir; não trate histórico de 1:1 como PDI sem pedido explícito.

As skills especializadas são complementares. A entrevista entrega um rascunho validado; o registro consome esse rascunho e executa as mutações.

## Regras comuns

- O MCP do 4TechLead é a fonte autorizada para dados e mutações do domínio.
- Identifique a pessoa com `mcp__4techlead__people_search` quando não houver `person_id` confiável.
- Não invente fatos, não exponha payloads brutos ou segredos e separe evidência do MCP de inferência.
- Toda escrita começa com `mode=proposal`; `mode=commit` exige confirmação explícita e o `approval_id` correspondente.
- Se uma proposta mudar, descarte a aprovação anterior e gere uma nova proposta.
