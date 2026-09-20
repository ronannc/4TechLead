# Plano de implementacao: agentes de IA no 4TechLead

## Objetivo

Permitir que usuarios do 4TechLead conversem com agentes de IA para consultar contexto de liderados e criar artefatos de lideranca dentro do proprio produto, como documentos de 1:1, execucoes de 1:1, PDIs, pontos de acompanhamento, feedbacks e resumos.

O 4TechLead nao deve usar agentes para programar, alterar codigo, executar comandos de desenvolvimento ou manipular arquivos do projeto. Os agentes devem atuar somente no dominio funcional do 4TechLead.

## Decisoes de produto

- No desktop buildado para macOS, Windows ou Linux, o 4TechLead pode usar agentes via CLI local quando o usuario tiver ferramentas instaladas e autenticadas na maquina.
- No mobile, o 4TechLead deve usar providers via API.
- Providers via API devem suportar, no minimo, Gemini, Claude e ChatGPT/OpenAI.
- Providers via CLI devem ser opcionais e disponiveis apenas em builds desktop.
- Toda criacao ou alteracao feita por IA deve passar por revisao e confirmacao do usuario antes de ser persistida.
- A API deve manter memoria operacional da pessoa para evitar resumir todo o historico de 1:1 a cada nova conversa.
- A memoria deve ser derivada dos dados oficiais do 4TechLead e atualizada conforme novos 1:1, PDIs, pontos e feedbacks forem registrados.
- O MCP do 4TechLead deve existir para agentes externos acessarem o sistema com seguranca, mas o chat interno do app deve conversar com o Agent Gateway do backend.

## Escopo inicial

O MVP deve permitir:

- configurar provider de IA;
- abrir um chat com agente dentro do 4TechLead;
- selecionar pessoa/contexto;
- pedir sugestoes para proximo 1:1;
- criar documento-base de 1:1 a partir de uma sugestao;
- criar ponto para proximo 1:1;
- sugerir PDI;
- criar PDI como rascunho;
- consultar historico resumido da pessoa;
- registrar auditoria das mensagens, execucoes e acoes aprovadas.

Fora do MVP:

- agentes alterarem codigo;
- agentes executarem comandos livres;
- automacao sem confirmacao para escrita;
- sugestoes avancadas com ClickUp/GitHub, exceto preparacao da arquitetura para isso;
- MCP publico sem controle de escopo, token e auditoria.

## Arquitetura proposta

```text
4TechLead Flutter
  Chat de IA
  Configuracao de provider
  Aprovacao de acoes
        |
Laravel API
  Agent Gateway
  Agent Memory
  Agent Tool Registry
  Agent Conversations
  Agent Runs
  Agent Tool Calls
        |
Tools internas do 4TechLead
  people.get_context
  one_on_one.suggest
  one_on_one.document.create
  one_on_one.session.create
  one_on_one.note.create
  pdi.suggest
  pdi.create_draft
        |
Providers de IA
  API: Gemini, Claude, ChatGPT/OpenAI
  CLI desktop: Gemini CLI, Claude CLI, Codex/OpenAI CLI quando disponivel
        |
MCP 4TechLead
  acesso externo seguro as mesmas tools internas
```

## Modos de execucao

### Modo API

Usado obrigatoriamente no mobile e opcionalmente no desktop.

Fluxo:

```text
Usuario envia mensagem no chat
Backend identifica provider configurado
Backend monta contexto controlado
Backend chama API do provider
Provider responde texto e/ou acoes estruturadas
Backend registra mensagem e tool calls propostas
Usuario aprova uma acao
Backend executa a tool interna
Sistema persiste o artefato no 4TechLead
```

### Modo CLI local

Usado somente em builds desktop para macOS, Windows e Linux.

Fluxo:

```text
Usuario envia mensagem no chat
Flutter identifica que provider configurado e CLI local
App chama Local Agent Bridge
Local Agent Bridge envia prompt e contexto controlado para a CLI
CLI retorna resposta
App envia resposta para o backend registrar
Usuario aprova a acao sugerida
Backend executa a tool interna e persiste o artefato
```

O CLI local deve ser usado apenas para gerar sugestoes e respostas. A persistencia continua acontecendo pela API do 4TechLead.

## Local Agent Bridge

O Local Agent Bridge e uma camada do app desktop responsavel por interagir com CLIs instaladas na maquina do usuario.

Responsabilidades:

- detectar comandos disponiveis;
- validar versao quando possivel;
- testar autenticacao basica;
- executar prompts com timeout;
- capturar stdout, stderr, codigo de saida e erros;
- normalizar a resposta para o formato esperado pelo 4TechLead;
- bloquear comandos nao permitidos;
- impedir execucao de shell arbitrario.

Configuracoes por provider CLI:

- nome do provider;
- caminho do binario;
- argumentos fixos permitidos;
- timeout;
- formato de resposta esperado;
- status: disponivel, nao encontrado, erro de autenticacao ou indisponivel.

## Providers

### Gemini API

Uso esperado:

- sugestoes de documentos;
- resumos;
- propostas de PDI;
- respostas conversacionais.

### Claude API

Uso esperado:

- raciocinio de lideranca;
- preparacao de conversas;
- escrita de feedbacks;
- estruturacao de planos.

### ChatGPT/OpenAI API

Uso esperado:

- chat geral;
- geracao estruturada;
- chamada de tools;
- criacao de documentos e planos.

### Providers CLI

Disponiveis somente no desktop:

- Gemini CLI;
- Claude CLI;
- Codex/OpenAI CLI, se fizer sentido para geracao de texto e estiver instalado.

Mesmo quando o provider for CLI, o agente nao deve receber permissao para programar, editar arquivos ou executar comandos de projeto.

## Memoria operacional da API

A memoria deve evitar que o sistema precise enviar todo o historico bruto da pessoa para o provider a cada solicitacao.

Tipos de memoria:

- memoria da pessoa;
- memoria de 1:1;
- memoria de PDI;
- memoria de pontos abertos;
- memoria de feedbacks;
- memoria futura de ClickUp/GitHub.

Tabela sugerida:

```text
agent_memories
  id
  tenant_id
  person_id nullable
  subject_type
  subject_id nullable
  memory_type
  summary
  facts json
  evidence json
  last_built_at
  stale_at nullable
  created_at
  updated_at
```

Exemplos de `memory_type`:

- `person_profile_summary`;
- `one_on_one_history_summary`;
- `open_points_summary`;
- `pdi_summary`;
- `leadership_context`;
- `delivery_context`.

Regras:

- memoria nunca substitui os dados oficiais;
- memoria deve ter evidencia apontando para registros reais;
- ao criar novo 1:1, PDI, ponto ou feedback, a memoria relacionada deve ser marcada como desatualizada;
- se a memoria estiver desatualizada, o backend deve reconstruir ou complementar antes de chamar o provider;
- dados sensiveis devem respeitar permissao do usuario solicitante.

## Agent Gateway

O Agent Gateway e o modulo backend que orquestra conversas, providers, contexto, tools e auditoria.

Responsabilidades:

- receber mensagens do usuario;
- carregar configuracao do provider;
- montar contexto seguro;
- carregar memoria operacional;
- chamar provider API ou receber resposta normalizada do CLI desktop;
- interpretar acoes estruturadas;
- registrar conversas, mensagens, runs e tool calls;
- exigir confirmacao do usuario para escrita;
- executar tools internas aprovadas;
- retornar resultado para o chat.

Tabelas sugeridas:

```text
ai_provider_configs
  provider, mode, model, encrypted_credentials, cli_command, settings

ai_agent_conversations
  tenant_id, user_id, person_id nullable, title, status

ai_agent_messages
  conversation_id, role, content, structured_payload, provider_metadata

ai_agent_runs
  conversation_id, provider, mode, model, status, started_at, finished_at

ai_agent_tool_calls
  run_id, tool_name, arguments, status, requires_approval, approved_by, result
```

## Tools internas

As tools internas devem ser classes/servicos de dominio do backend, reaproveitadas pelo chat interno, MCP e futuras automacoes.

Tools iniciais:

```text
people.search
people.get_context
people.get_memory

one_on_one.documents.list
one_on_one.documents.create
one_on_one.sessions.list
one_on_one.sessions.get
one_on_one.sessions.create_from_document
one_on_one.notes.list
one_on_one.notes.create
one_on_one.suggestions.generate

pdi.list
pdi.get
pdi.suggest
pdi.create_draft

agent.memory.refresh
```

Cada tool deve declarar:

- nome;
- descricao;
- schema de entrada;
- schema de saida;
- permissoes necessarias;
- se exige aprovacao;
- servico backend responsavel;
- politica de auditoria.

## MCP do 4TechLead

O MCP deve expor as mesmas tools internas para agentes externos.

Objetivos:

- permitir que Claude, Codex ou outros clientes consultem o 4TechLead;
- permitir criacao de documentos, pontos e PDIs com token e escopo adequados;
- manter as mesmas permissoes e auditorias do app.

Transportes:

- stdio para uso local;
- HTTP para uso externo.

Escopos de token:

- `people:read`;
- `one-on-one:read`;
- `one-on-one:write`;
- `pdi:read`;
- `pdi:write`;
- `agent:suggest`;
- `agent:memory:refresh`.

O MCP nao deve acessar banco diretamente. Ele deve chamar a API/servicos oficiais.

## Fluxo do usuario: configuracao

```text
Usuario abre Configuracoes > Agentes de IA
Sistema mostra providers disponiveis:
  - Gemini API
  - Claude API
  - ChatGPT/OpenAI API
  - Gemini CLI, desktop apenas
  - Claude CLI, desktop apenas
  - Codex/OpenAI CLI, desktop apenas
Usuario escolhe provider padrao
Usuario informa credenciais ou caminho do CLI
Sistema testa conexao
Usuario escolhe modo de criacao:
  - sugerir apenas
  - criar rascunho com confirmacao
Sistema salva configuracao
```

## Fluxo do usuario: preparar proximo 1:1

```text
Usuario abre menu 1:1
Usuario entra em Sugestoes ou Agente IA
Usuario seleciona uma pessoa
Usuario escreve:
  "Me ajude a preparar o proximo 1:1"
Sistema carrega memoria da pessoa
Sistema busca pontos abertos e ultimos dados relevantes
Agente responde com sugestoes de pauta
Usuario pede ajustes no chat
Agente refina a proposta
Usuario clica em "Criar documento de 1:1"
Sistema mostra preview do documento
Usuario confirma
Sistema cria documento-base de 1:1
```

## Fluxo do usuario: criar PDI

```text
Usuario abre Agente IA
Usuario seleciona pessoa
Usuario escolhe objetivo "Criar PDI"
Usuario informa contexto adicional
Sistema carrega memoria, historico, pontos e PDIs anteriores
Agente sugere objetivos, acoes e checkpoints
Usuario conversa ate ajustar o plano
Usuario clica em "Criar PDI como rascunho"
Sistema mostra preview
Usuario confirma
Sistema cria PDI em status rascunho
```

## Fluxo do usuario: salvar ponto para futuro 1:1

```text
Usuario conversa com agente
Agente identifica um ponto relevante
Agente oferece acao "Salvar ponto"
Usuario confirma
Sistema cria ponto aberto para a pessoa
Ponto aparece no fluxo de execucao de 1:1
Memoria da pessoa e marcada como desatualizada
```

## Fluxo do usuario: executar 1:1 com apoio de IA

```text
Usuario abre 1:1 > Executar
Usuario seleciona pessoa
Usuario seleciona documento-base
Sistema mostra pontos abertos
Usuario pode abrir chat do agente contextual
Usuario pede ajuda para conduzir ou adaptar perguntas
Agente sugere perguntas complementares
Usuario registra respostas e decisoes
Usuario salva 1:1 executado
Sistema atualiza memoria operacional da pessoa
```

## Fluxo conversacional esperado

Exemplo:

```text
Usuario:
Quero preparar o 1:1 da Camila.

Agente:
Encontrei pontos abertos sobre autonomia e comunicacao de bloqueios.
Tambem identifiquei que o ultimo PDI tinha uma acao pendente sobre ownership.
Sugiro uma conversa focada em clareza de expectativas, autonomia e apoio.

Usuario:
Quero perguntas mais diretas.

Agente:
Preparei um roteiro com 6 perguntas objetivas.
Posso criar um documento de 1:1 com esse roteiro.

Usuario:
Criar documento.

Sistema:
Mostra preview.

Usuario:
Confirmar.

Sistema:
Documento criado.
```

## Regras de seguranca e permissao

- Membros podem consultar apenas seus proprios dados permitidos.
- TLs podem criar documentos, PDIs, pontos e execucoes para seus liderados conforme regras atuais.
- A IA deve receber somente o contexto permitido para o usuario autenticado.
- Escritas exigem confirmacao explicita.
- Credenciais de providers devem ser criptografadas.
- Tool calls devem ser auditadas.
- O prompt enviado ao provider deve evitar expor dados desnecessarios.
- Respostas devem conter evidencias quando usarem historico.

## Formato das respostas de IA

O backend deve preferir respostas estruturadas.

Formato conceitual:

```json
{
  "message": "Texto para o usuario",
  "suggestions": [
    {
      "title": "Autonomia e bloqueios",
      "reason": "Tema recorrente nos ultimos 1:1s",
      "evidence": []
    }
  ],
  "proposed_actions": [
    {
      "type": "create_one_on_one_document",
      "label": "Criar documento de 1:1",
      "requires_approval": true,
      "payload": {}
    }
  ]
}
```

## Fases de implementacao

### Fase 1: Fundacao backend

- Criar modelos de configuracao de provider.
- Criar conversas, mensagens, runs e tool calls.
- Criar Agent Gateway.
- Criar registry de tools internas.
- Criar memoria operacional inicial.
- Criar politicas de permissao e auditoria.

### Fase 2: Tools de dominio

- Implementar contexto de pessoa.
- Implementar sugestao de 1:1.
- Implementar criacao de documento de 1:1.
- Implementar criacao de ponto.
- Implementar sugestao de PDI.
- Implementar criacao de PDI como rascunho.

### Fase 3: Providers API

- Implementar Gemini API.
- Implementar Claude API.
- Implementar ChatGPT/OpenAI API.
- Normalizar entrada e saida entre providers.
- Criar fallback quando provider falhar.

### Fase 4: Flutter mobile e desktop

- Criar tela de configuracao de agentes.
- Criar chat de agente.
- Criar seletor de pessoa/contexto.
- Criar preview de acoes propostas.
- Criar confirmacao antes de persistir.
- Integrar fluxo com 1:1 e PDI.

### Fase 5: CLI desktop

- Detectar plataforma desktop.
- Criar Local Agent Bridge.
- Permitir configurar binario local.
- Executar prompts com timeout e captura de resposta.
- Normalizar resposta para o Agent Gateway.
- Bloquear execucao de comandos arbitrarios.

### Fase 6: MCP 4TechLead

- Expor tools internas via MCP.
- Criar autenticacao por token e escopos.
- Suportar stdio local.
- Suportar HTTP externo.
- Registrar auditoria das chamadas MCP.

### Fase 7: Evolucao com dados operacionais

- Incorporar ClickUp.
- Incorporar GitHub.
- Enriquecer memoria de delivery.
- Gerar sugestoes considerando tarefas, PRs, bloqueios, revisoes e incidentes.

## Criterios de aceite do MVP

- Usuario consegue configurar provider API.
- Usuario desktop consegue configurar provider CLI local.
- Usuario mobile usa somente provider API.
- Usuario consegue conversar com agente dentro do 4TechLead.
- Usuario consegue pedir sugestao para proximo 1:1 de uma pessoa.
- Sistema usa memoria operacional e historico permitido da pessoa.
- Usuario consegue criar documento de 1:1 a partir da resposta da IA.
- Usuario consegue criar ponto para proximo 1:1 a partir da resposta da IA.
- Usuario consegue pedir sugestao de PDI.
- Usuario consegue criar PDI como rascunho.
- Todas as escritas exigem confirmacao.
- Todas as tool calls ficam auditadas.
- Membro nao acessa historico de outras pessoas.
- TL acessa dados conforme permissoes atuais.

## Principio orientador

O agente deve funcionar como copiloto de lideranca. Ele ajuda o usuario a pensar, estruturar e criar artefatos melhores dentro do 4TechLead, mas quem decide e confirma as mudancas e sempre o usuario.
