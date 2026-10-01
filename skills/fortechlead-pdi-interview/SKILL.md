---
name: fortechlead-pdi-interview
description: "Conduza a entrevista de PDI do 4TechLead e transforme as respostas da pessoa em um plano validado, antes de qualquer registro no sistema."
---

# Entrevista e planejamento de PDI

Use esta skill para planejar o PDI com a pessoa. Ela não registra nem altera dados no 4TechLead; a persistência é responsabilidade de `fortechlead-pdi-registration`.

O MCP do 4TechLead permanece acessível para consultas autorizadas, como identificação da pessoa e contexto atual. Não use ferramentas mutáveis nesta etapa.

## Condução

Faça a entrevista em blocos curtos e espere as respostas antes de avançar. Quando a resposta for ampla, peça um exemplo recente, uma situação observável ou uma entrega concreta.

1. **Autoavaliação:** em quais atividades sente confiança, em que tarefas entrega bem e onde percebe que o time confia nela?
2. **Pontos a desenvolver:** quando trava ou depende muito de outras pessoas, o que causa insegurança técnica e que tarefa evita ou procrastina?
3. **Interesses e aspirações:** o que quer aprender ou melhorar no ano, em qual direção quer crescer (backend, frontend, mobile, IA ou produto) e onde gostaria de estar em 1–2 anos?
4. **Objetivos técnicos:** quais responsabilidades novas conseguiria assumir?
5. **Objetivos de produto/negócio:** o que quer entender melhor e como isso pode melhorar suas decisões técnicas?
6. **Objetivos comportamentais/colaboração:** o que pode melhorar na forma de trabalhar com o time e como pode ajudar outras pessoas?
7. **Ações práticas:** traduza cada objetivo em uma ação pequena, objetiva, de fácil conquista e ligada a uma tarefa real.
8. **Encerramento:** leia o resumo e pergunte explicitamente se os objetivos fazem sentido e se a pessoa concorda com o acordo.

## Ações

Uma ação deve ter verbo, escopo, entregável e evidência de conclusão. Prefira “documentar um fluxo de autenticação com um exemplo” a “estudar backend”.

Capture título, contexto, entregável, evidência, dependência e prazo somente quando acordado. Ordene por dependência: requisitos/modelagem antes de consultas; consultas antes de API; prática antes de uma responsabilidade maior.

## Rascunho

```text
Objetivo do PDI

Diagnóstico atual
- Pontos fortes
- Pontos a desenvolver
- Interesses e aspirações

Objetivos do ano
- Objetivos técnicos
- Objetivos de produto/negócio
- Objetivos comportamentais/colaboração

Ações práticas
1. ...
2. ...

Acordo
- O que foi combinado
- Concordância da pessoa
- Pontos para revisar
```

Não invente respostas ausentes. Se a pessoa não concordar com o resumo, ajuste-o antes do registro.

## Contrato de saída

Só entregue o planejamento à skill de registro quando houver concordância explícita. O handoff deve conter:

```text
status: validado
person_id: ...
person_name: ...
objective: ...
diagnosis:
  strengths: [...]
  development_points: [...]
  interests_and_aspirations: [...]
annual_objectives:
  technical: [...]
  product_business: [...]
  behavioral_collaboration: [...]
actions:
  - title: ...
    description: ...
    evidence: ...
    dependency: ...
agreement:
  agreed: true
  notes: ...
```

O campo `agreement` deve registrar o que foi confirmado e os pontos que ficaram para revisão. Sem `agreed: true`, a próxima skill pode apenas revisar o rascunho, nunca persistir o PDI.
