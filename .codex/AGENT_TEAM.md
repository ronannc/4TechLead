# ForTechLead Agent Team

This project has local Codex agents in `.codex/agents/` for coordinated backend and frontend delivery.

## Team

- `fortechlead-team-orchestrator`: coordinates the full loop.
- `fortechlead-backend-dev`: implements Laravel API changes and fixes backend reviewer findings.
- `fortechlead-frontend-dev`: implements Flutter changes and fixes frontend reviewer findings.
- `fortechlead-api-contract-reviewer`: keeps Laravel responses/requests and Flutter consumers aligned.
- `fortechlead-backend-qa`: validates Laravel behavior and adds Pest coverage.
- `fortechlead-frontend-qa`: validates Flutter behavior and adds unit/widget coverage.
- `fortechlead-backend-security`: reviews Laravel auth, authorization, webhooks, tokens, and data exposure.
- `fortechlead-frontend-security`: reviews Flutter token/session handling, route guards, diagnostics, and sensitive UI state.
- `fortechlead-backend-performance`: reviews Laravel query/request cost, indexes, pagination, and webhook throughput.
- `fortechlead-frontend-performance`: reviews Flutter rebuilds, layout stability, duplicated calls, and responsive rendering.

## Delivery Loop

1. Orchestrator scopes the task as backend, frontend, or both.
2. Dev agent implements the change.
3. API contract reviewer validates backend/frontend alignment when the contract changes.
4. QA agent tests behavior and adds missing coverage.
5. Dev agent fixes QA failures.
6. Security agent validates the patched result when the security gate applies.
7. Dev agent fixes security findings.
8. Performance agent validates request/screen cost when the performance gate applies.
9. Dev agent fixes performance findings.
10. Orchestrator reports commands, evidence, skipped gates, and residual risks.

## Gate Rules

- QA is mandatory for every code change.
- API contract review is mandatory when routes, Resources, Form Requests, response JSON, enums, pagination, filters/order/search, auth behavior, webhook payloads, or Flutter API models/services change.
- Security review is mandatory for auth, authorization, token/session handling, webhook ingestion, external integrations, secrets, logs/diagnostics, file/object access, permission policies, or personal/sensitive data exposure.
- Performance review is mandatory for list endpoints, search/filter/order changes, pagination, possible N+1 queries, queue/webhook throughput, repeated API calls, broad Flutter rebuilds, large lists/tables, responsive layout risk, or high-frequency paths.
- Security and performance may be skipped for docs-only changes, tests-only refactors, copy-only changes, or isolated styling with no data flow. The orchestrator must state skipped gates and why.

Use `fortechlead-team-orchestrator` for cross-cutting tasks. Use a specialist directly when the role and layer are already clear.
