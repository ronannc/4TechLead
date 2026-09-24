# Plano de execução: MCP do 4TechLead

## 1. Objetivo

Permitir que o usuário converse com um agente externo, como ChatGPT/Codex, e que o agente consulte e altere dados do 4TechLead por meio de ferramentas MCP seguras.

Exemplos do resultado esperado:

- consultar o histórico de 1:1 de uma pessoa;
- preparar uma pauta para o próximo 1:1;
- consultar um PDI e suas ações abertas;
- criar um documento-base de 1:1 como rascunho;
- criar um PDI como rascunho;
- atualizar uma ação de PDI após confirmação do usuário;
- consultar um KPI e as evidências que formaram o indicador;
- explicar a origem de uma métrica de delivery sem acessar o banco diretamente.

O MCP será uma interface externa para as capacidades funcionais do 4TechLead. Ele não será uma interface de banco, shell, arquivos, código ou administração irrestrita.

## 2. Decisões arquiteturais

### 2.1 API, MCP, conector e plugin

- A API Laravel continua sendo o contrato principal do sistema.
- O Agent Gateway será a camada de autorização, contexto, auditoria e execução das ferramentas.
- O MCP Server será um adaptador que expõe as ferramentas do Agent Gateway para clientes externos.
- Um conector é apenas a forma de registrar/autenticar esse servidor em um cliente; não substitui o MCP Server.
- Um plugin não é necessário para o primeiro release. Poderá ser criado depois para empacotar configuração de cliente ou comandos auxiliares.

### 2.2 Transporte

O primeiro transporte será MCP remoto via HTTPS, com OAuth 2.1 ou token de acesso de curta duração. O transporte stdio ficará como opção posterior para desenvolvimento local e clientes que executem um processo local.

Requisitos:

- HTTPS obrigatório em ambientes compartilhados;
- endpoint público somente para o MCP, sem expor rotas internas;
- autenticação vinculada a um usuário e tenant;
- revogação, expiração e rotação de credenciais;
- nenhum segredo de provider ou token de integração enviado ao modelo.

### 2.3 Leitura e escrita

As ferramentas devem ser classificadas como `read_only` ou mutáveis.

- Leituras podem ser executadas diretamente quando o escopo permitir.
- Escritas sempre retornam uma proposta estruturada primeiro.
- A persistência somente ocorre depois de confirmação explícita do usuário.
- A confirmação deve incluir pessoa, objeto, campos alterados e origem dos dados.
- Toda execução aprovada deve ser idempotente quando aplicável.

## 3. Arquitetura de referência

```text
ChatGPT/Codex
    |
    | MCP remoto via HTTPS
    | OAuth/token com escopos
    v
MCP Server do 4TechLead
    |
    | validação MCP e normalização de argumentos
    v
Agent Gateway Laravel
    |
    +-- Auth/tenant resolver
    +-- Permission checker
    +-- Context builder
    +-- Agent Tool Registry
    +-- Approval verifier
    +-- Audit logger
    |
    v
Serviços de domínio existentes
    |
    +-- 1:1
    +-- PDI
    +-- pessoas
    +-- KPIs/métricas
    +-- casos de entrega/evidências
```

O MCP não acessará Eloquent ou o banco diretamente. Cada ferramenta chamará um serviço de domínio que já respeite as políticas existentes.

## 4. Escopo funcional do primeiro release

### 4.1 Ferramentas somente leitura

| Ferramenta | Finalidade | Escopo inicial |
| --- | --- | --- |
| `people.list` | Listar pessoas que o usuário pode consultar | `people:read` |
| `people.get_context` | Retornar contexto resumido e evidências autorizadas | `people:read` |
| `one_on_one.list` | Listar sessões e documentos da pessoa | `one-on-one:read` |
| `one_on_one.get_history` | Ler histórico resumido e sessões selecionadas | `one-on-one:read` |
| `pdi.list` | Listar PDIs da pessoa | `pdi:read` |
| `pdi.get_open_actions` | Retornar ações abertas, prazos e status | `pdi:read` |
| `kpi.get_summary` | Consultar KPIs por pessoa/período | `kpi:read` |
| `kpi.get_evidence` | Consultar dados normalizados que formaram o KPI | `kpi:evidence:read` |

Todas as respostas devem informar período, pessoa, tenant lógico, filtros aplicados e referências de evidência quando disponíveis.

### 4.2 Ferramentas mutáveis

| Ferramenta | Resultado inicial | Aprovação |
| --- | --- | --- |
| `one_on_one.create_draft` | Cria documento-base de 1:1 | obrigatória |
| `one_on_one.create_session` | Cria execução de 1:1 | obrigatória |
| `one_on_one.create_note` | Cria ponto para futuro 1:1 | obrigatória |
| `pdi.create_draft` | Cria PDI em rascunho | obrigatória |
| `pdi.update_action` | Atualiza uma ação de PDI | obrigatória |
| `agent.memory.refresh` | Recalcula memória operacional | explícita ou política controlada |

No primeiro release, evitar ferramentas genéricas como `database.query`, `model.update`, `execute_command` ou `run_sql`.

## 5. Modelo de autorização

### 5.1 Escopos MCP

```text
people:read
one-on-one:read
one-on-one:write
pdi:read
pdi:write
kpi:read
kpi:evidence:read
agent:memory:refresh
```

### 5.2 Regras obrigatórias

- Toda chamada resolve o usuário autenticado e o tenant no servidor.
- `person_id`, `team_id` e demais identificadores são validados contra a política do usuário.
- O agente nunca recebe autorização maior que a do usuário.
- Membros não podem consultar dados de outras pessoas fora das regras atuais.
- A autorização não pode depender apenas de instruções do prompt.
- O token deve identificar a instalação/conexão, usuário, tenant, escopos, criação, expiração e revogação.
- Ferramentas mutáveis devem exigir um `approval_id` emitido pelo Agent Gateway.

### 5.3 Fluxo de aprovação

```text
Agente chama ferramenta mutável em modo proposal
        |
        v
Agent Gateway valida e retorna preview + approval_id
        |
        v
Usuário confirma no chat
        |
        v
Agente chama ferramenta de commit com approval_id
        |
        v
Gateway revalida autorização, expiração e payload
        |
        v
Serviço de domínio persiste + registra auditoria
```

O `approval_id` deve ser de uso único, possuir expiração curta e estar vinculado ao hash do payload apresentado ao usuário.

## 6. Fases de execução

### Fase 0 — Contrato e baseline

**Entregas**

- confirmar os casos de uso prioritários;
- inventariar rotas, policies, resources e services existentes;
- definir nomes, schemas JSON e classificação de cada ferramenta;
- documentar quais evidências formam cada KPI;
- definir a matriz usuário/tenant/pessoa/ação;
- registrar decisões em OpenAPI e no documento MCP.

**Critério de saída**

Cada ferramenta do MVP possui entrada, saída, escopo, política, erros e exemplo de chamada.

### Fase 1 — Fundação do Agent Gateway

**Entregas**

- `AgentToolRegistry` com metadata das ferramentas;
- `AgentContextBuilder` para contexto mínimo e autorizado;
- `AgentPermissionService`;
- `AgentApprovalService`;
- tabelas para conexões, sessões, execuções, tool calls e aprovações;
- auditoria com usuário, tenant, ferramenta, argumentos redigidos, resultado, duração e erro;
- correlação por `request_id` e `run_id`.

**Critério de saída**

Uma ferramenta fake pode ser chamada, rejeitada por escopo, aprovada, expirada, executada uma única vez e auditada.

### Fase 2 — Ferramentas de leitura

**Entregas**

- implementar as oito ferramentas read-only do MVP;
- reutilizar os serviços e resources atuais;
- respostas compactas e próprias para agente;
- paginação e limites máximos;
- referências de evidência para históricos e KPIs;
- redaction de dados sensíveis desnecessários.

**Critério de saída**

O gateway permite consultar uma pessoa, histórico de 1:1, PDI, ações abertas e KPI sem ultrapassar tenant ou política.

### Fase 3 — MCP remoto

**Entregas**

- implementar endpoint MCP remoto sobre HTTPS;
- expor apenas ferramentas registradas;
- discovery de ferramentas e schemas;
- autenticação OAuth/token;
- propagação de `request_id`, usuário, tenant e scopes;
- health check sem revelar dados;
- rate limiting, timeout e tratamento de indisponibilidade;
- configuração de ambiente local, staging e produção.

**Critério de saída**

Um cliente MCP consegue descobrir as ferramentas, autenticar e concluir uma consulta read-only real no staging.

### Fase 4 — Primeiro teste conversacional externo

**Roteiro**

1. “Liste as pessoas que posso consultar.”
2. “Leia o histórico recente de 1:1 da pessoa X.”
3. “Quais ações de PDI estão abertas?”
4. “Prepare uma pauta para o próximo 1:1 usando somente essas evidências.”
5. “Mostre o KPI de delivery e os dados que o formaram.”

**Critério de saída**

O agente responde com dados do 4TechLead, informa limites e evidências, e não inventa registros quando não há dados.

### Fase 5 — Escritas com confirmação

**Entregas**

- modo proposal para cada ferramenta mutável;
- preview legível no chat;
- `approval_id` de uso único;
- commit separado da proposta;
- idempotency key;
- auditoria antes/depois com campos permitidos;
- testes de repetição, concorrência, expiração e alteração do payload.

**Critério de saída**

O agente consegue criar um documento-base de 1:1 e um PDI como rascunho, mas nunca persiste sem confirmação explícita.

### Fase 6 — Atualizações e memória operacional

**Entregas**

- atualizar ações de PDI;
- registrar ponto para próximo 1:1;
- atualizar memória após novo 1:1, PDI, feedback ou ponto;
- marcar memória como stale quando houver mudança relevante;
- manter evidências e data de construção;
- permitir refresh explícito da memória.

**Critério de saída**

Uma conversa nova pode usar um resumo atualizado sem reenviar todo o histórico bruto da pessoa.

### Fase 7 — Produção e operação

**Entregas**

- domínio MCP estável e sem tokens expostos;
- observabilidade de latência, erros, chamadas por ferramenta e rejeições;
- revogação de conexões;
- documentação de configuração do cliente;
- runbook de incidentes;
- testes de carga e limites;
- revisão de segurança e privacidade.

**Critério de saída**

É possível responder quem acessou qual dado, por qual ferramenta, com qual autorização e qual alteração foi feita.

## 7. Contrato mínimo de uma ferramenta

Exemplo conceitual para `pdi.update_action`:

```json
{
  "name": "pdi.update_action",
  "description": "Propõe ou aplica uma alteração em uma ação de PDI autorizada.",
  "annotations": {
    "readOnlyHint": false
  },
  "inputSchema": {
    "type": "object",
    "required": ["action_id", "changes", "mode"],
    "properties": {
      "action_id": { "type": "string" },
      "changes": {
        "type": "object",
        "properties": {
          "status": { "type": "string" },
          "due_date": { "type": ["string", "null"] },
          "notes": { "type": ["string", "null"] }
        },
        "additionalProperties": false
      },
      "mode": { "enum": ["proposal", "commit"] },
      "approval_id": { "type": ["string", "null"] },
      "idempotency_key": { "type": ["string", "null"] }
    },
    "additionalProperties": false
  }
}
```

O servidor deve rejeitar qualquer campo não previsto no schema, mesmo que o modelo o envie.

## 8. Testes obrigatórios

### Backend

- autenticação válida e inválida;
- tenant correto e tenant divergente;
- usuário sem acesso à pessoa;
- escopo ausente;
- ferramenta inexistente;
- argumentos inválidos;
- limite de paginação;
- redaction de dados sensíveis;
- ferramenta mutável sem aprovação;
- aprovação expirada, reutilizada ou com payload alterado;
- idempotência e concorrência;
- auditoria em sucesso e erro;
- associação correta de evidências do KPI.

### MCP

- discovery de ferramentas;
- schema inválido;
- timeout;
- servidor indisponível;
- token expirado/revogado;
- ferramenta read-only marcada corretamente;
- aprovação de escrita;
- resposta compatível com cliente externo.

### Teste ponta a ponta

```text
cliente MCP -> autenticação -> discovery -> people.get_context
cliente MCP -> one_on_one.get_history
cliente MCP -> kpi.get_summary -> kpi.get_evidence
cliente MCP -> pdi.create_draft(proposal)
usuário confirma
cliente MCP -> pdi.create_draft(commit)
backend -> auditoria -> recurso persistido
```

## 9. Segurança e privacidade

- Não expor URLs públicas com tokens, secrets ou IDs de integração.
- Não enviar payload bruto de webhooks ao modelo; usar dados normalizados e allowlists.
- Reduzir contexto ao mínimo necessário para a tarefa.
- Criptografar credenciais armazenadas.
- Separar ator da alteração de responsável/assignee.
- Não permitir que o modelo escolha livremente tenant, usuário executor ou política.
- Registrar autorização e decisão humana para cada escrita.
- Definir retenção para prompts, respostas, tool calls e dados derivados.
- Permitir revogar uma conexão MCP sem apagar dados do 4TechLead.

## 10. Ordem recomendada de implementação no repositório

1. Criar contratos e schemas em `docs/` e atualizar o contrato OpenAPI.
2. Criar migrations/models de conexão, execução, tool call e aprovação.
3. Criar `AgentToolRegistry`, autorização, contexto e auditoria.
4. Implementar ferramentas read-only sobre os serviços atuais.
5. Adicionar endpoint MCP remoto e testes de protocolo.
6. Configurar staging e realizar o primeiro teste externo somente leitura.
7. Implementar proposal/commit para documentos de 1:1 e PDI.
8. Implementar aprovação, idempotência e auditoria das escritas.
9. Adicionar atualização de ações e memória operacional.
10. Publicar documentação de conexão e runbook de produção.

## 11. Critérios de aceite do objetivo final

O objetivo será considerado atingido quando, a partir de uma conversa com o agente:

- ele conseguir consultar dados autorizados do 4TechLead;
- ele conseguir consultar históricos de 1:1 e PDIs;
- ele conseguir explicar KPIs usando evidências persistidas;
- ele conseguir propor documento de 1:1 ou PDI;
- nenhuma escrita ocorrer sem confirmação explícita;
- ele conseguir persistir a escrita confirmada;
- todas as chamadas forem auditáveis;
- um usuário sem permissão não conseguir contornar as políticas via prompt ou MCP;
- o sistema continuar funcionando quando o MCP estiver indisponível;
- não houver acesso direto do agente ao banco, shell, código ou arquivos do projeto.

## 12. Próximo incremento recomendado

O primeiro incremento de código deve ser a Fase 1 mais duas ferramentas read-only:

- `people.get_context`;
- `one_on_one.get_history`.

Esse incremento valida a fundação de autorização, contexto, auditoria e resposta MCP com o menor risco. Depois devem entrar `pdi.get_open_actions`, `kpi.get_summary` e `kpi.get_evidence` antes de qualquer escrita.

## Referências

- `docs/ai-agents-implementation-plan.md` — plano geral de agentes, providers, memória e MCP.
- `backend/routes/api.php` — rotas funcionais existentes de pessoas, 1:1, PDI, métricas e KPIs.
- OpenAI Docs: [Remote MCP tools](https://platform.openai.com/docs/api-reference/responses-streaming/response/refusal?lang=python).
- Skill de integração MCP: transporte HTTPS, OAuth/token, escopos, configuração e testes.
