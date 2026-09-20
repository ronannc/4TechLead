# Plano de execução — KPIs de entregas por pessoa

## Objetivo

Transformar os webhooks do ClickUp e GitHub em entregas canônicas, correlacionadas pelo ID da tarefa, com participantes por papel, linha do tempo auditável e KPIs de fluxo, qualidade e colaboração calculados por período.

## Princípios

- Uma entrega é identificada por `tenant + task_ref` e pode possuir vários PRs.
- Eventos recebidos são evidências imutáveis; projeções e KPIs podem ser recalculados.
- Ator, responsável de implementação, autor de código, reviewer e QA são papéis distintos.
- `Impedimento` pausa o trabalho; `Reprovado pelo QA` cria retrabalho; `Rejeitado` cancela; `Publicado` conclui.
- GitHub é a fonte principal para PR, review, CI, merge e deployment.
- ClickUp é a fonte principal para fluxo operacional, responsáveis, QA e publicação.
- `HOMOLOG` e `MERGIADO` são confirmações operacionais, não novas entregas.
- Métricas do time contam tarefas distintas; participação individual não duplica o throughput do time.

## Sequência de execução

### 0. Baseline e proteção do trabalho existente — concluído

- [x] Mapear ingestão, normalização, métricas, API e UI com Graphify.
- [x] Identificar alterações locais existentes no enriquecimento do ClickUp.
- [x] Registrar os significados oficiais dos status do Kanban.
- [x] Confirmar o ID da tarefa na branch como chave de correlação.

Critério de conclusão: riscos atuais e arquivos afetados conhecidos antes de editar.

### 1. Modelo canônico da entrega — concluído

- [x] Criar `DeliveryCase`, único por tenant e referência da tarefa.
- [x] Criar participantes da entrega com papel, intervalo temporal, fonte e confiança.
- [x] Criar marcos da timeline ligados ao webhook de origem.
- [x] Criar vínculos da entrega com entidades externas, incluindo tarefa, PR, SHA, workflow e deployment.
- [x] Adicionar factories, relações, casts e isolamento por tenant.
- [x] Cobrir migrations e modelos com testes.

Critério de conclusão: um caso consegue representar uma tarefa com vários PRs, DEV e QA distintos e eventos fora de ordem.

### 2. Normalização ClickUp — concluído

- [x] Mapear os dez status para estágios semânticos.
- [x] Normalizar valores de `before` e `after`, inclusive quando forem objetos.
- [x] Capturar alterações de assignee e tags `HOMOLOG`/`MERGIADO`.
- [x] Enriquecer `custom_id`, assignees, pontos, tags, lista e sprint quando disponíveis no snapshot da tarefa.
- [x] Retirar chamadas externas de dentro da transação de ingestão.
- [x] Preservar snapshots históricos de responsáveis.

Critério de conclusão: status, responsáveis e tags geram marcos idempotentes sem atribuir a entrega ao ator da mudança.

### 3. Normalização GitHub — concluído

- [x] Vincular PRs à entrega usando referência extraída de branch, título ou corpo.
- [x] Separar autor de PR e reviewer.
- [x] Consolidar CI por workflow/run attempt, usando checks apenas como detalhes.
- [x] Vincular merge e deployments por PR, SHA e ambiente.
- [x] Tratar múltiplos PRs por tarefa.

Critério de conclusão: uma execução de pipeline gera um único resultado de CI e todos os participantes mantêm o papel correto.

### 4. Projetor e reconciliação — em execução

- [x] Projetar cada novo webhook na entrega correspondente.
- [ ] Reprocessar a entrega quando eventos chegarem fora de ordem.
- [x] Criar deduplicação semântica além da idempotência de transporte.
- [x] Calcular confiança e registrar conflitos de associação.
- [x] Criar comando de backfill dos eventos armazenados.

Critério de conclusão: apagar e reconstruir as projeções produz o mesmo resultado.

### 5. KPIs confiáveis — em execução

- [x] Entregas e pontos publicados por período.
- [ ] Tempo de análise, desenvolvimento ativo e cycle time completo.
- [x] Espera de QA.
- [x] QA first-pass e taxa de retrabalho.
- [x] Tempo impedido.
- [ ] Tempo até homolog, review, merge e publicação.
- [ ] CI first-pass e tempo de recuperação.
- [ ] Colaboração em reviews separada da qualidade do autor.
- [ ] Excluir `Rejeitado` de falha individual e medir desperdício de processo separadamente.

Critério de conclusão: métricas são agregadas no backend por intervalo/sprint e não sobre uma página arbitrária de eventos.

### 6. Contrato de API — em execução

- [x] Criar endpoint de resumo do time e da pessoa.
- [x] Incluir intervalo, tamanho da amostra, confiança e cobertura dos dados.
- [x] Manter controller fino e consultas em service.
- [ ] Atualizar OpenAPI e testes do contrato.

Critério de conclusão: Flutter não precisa inferir métricas a partir dos 20 últimos registros.

### 7. Flutter — em execução

- [x] Substituir cards atuais pelo resumo de Entrega, Fluxo e Qualidade calculado no backend.
- [ ] Criar visão do time com filtros de período/sprint.
- [ ] Criar timeline Entrega 360°.
- [ ] Criar painel de qualidade dos dados e associações conflitantes.
- [ ] Validar desktop, mobile, acessibilidade, espaçamento e ausência de overflow.

Critério de conclusão: cada KPI permite abrir as tarefas e evidências que formaram o número.

### 8. Validação e entrega — pendente

- [x] Executar Pint e testes backend focados e completos.
- [ ] Atualizar Graphify do backend.
- [x] Executar format, analyze e testes Flutter serialmente via FVM.
- [ ] Atualizar Graphify do frontend.
- [ ] Revisar segurança de tokens/webhooks e performance das agregações.
- [ ] Registrar riscos residuais e decisões ainda configuráveis.

Critério de conclusão: testes, contratos, segurança, performance e grafos validados nos dois repositórios independentes.

## Frentes paralelas

- Backend: modelo canônico, projetor, métricas e APIs.
- Frontend: contrato consumidor, arquitetura de telas e componentes reutilizáveis.
- QA/contrato: cenários de transição, idempotência, eventos fora de ordem e alinhamento OpenAPI/Flutter.

As frentes convergem ao final de cada fase. Nenhuma tela de KPI será finalizada antes de o respectivo cálculo existir no backend.

## Virada da coleta de teste — concluída em 2026-09-13

- [x] Aplicar as migrations do modelo canônico de entrega.
- [x] Remover eventos de webhook, métricas derivadas e projeções legadas do ambiente de teste.
- [x] Preservar times, pessoas, integrações, tokens e mapeamentos de identidade externa.
- [x] Adicionar o comando explícito `delivery-kpis:reset-collection` para futuras viradas controladas.

Os KPIs novos passam a considerar apenas os eventos que chegarem após esta virada. O comando de reprojeção existe para recuperação operacional, mas não deve ser executado para importar o histórico descartado.
