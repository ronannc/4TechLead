# Repository Guidelines

## Project Structure & Module Organization

This repository is a two-project workspace containing two independent repositories:

- `backend/` contains the Laravel 13 API. Core code lives in `app/`, API routes in `routes/api.php`, migrations/factories/seeders in `database/`, and Pest tests in `tests/Feature` and `tests/Unit`.
- `frontend/` contains the Flutter app. Application code lives in `lib/`, organized by `core/` and `features/`; tests mirror that structure under `test/`. The Dart package name is `for_tech_lead`, so tests must import `package:for_tech_lead/...`.
- `frontend/assets/sounds/` stores bundled sound assets. Update `pubspec.yaml` when adding asset groups.

## Build, Test, and Development Commands

Run backend commands from `backend/`:

- `make up` starts the Docker services.
- `make test` runs `php artisan test --compact` in the app container.
- `make pint` formats PHP with Laravel Pint.
- `composer dev` runs the Laravel server, queue listener, logs, and Vite together.
- `npm run build` builds backend Vite assets.

Run frontend commands from `frontend/`:

- `/Users/ronan/fvm/bin/fvm flutter pub get` installs Dart dependencies.
- `/Users/ronan/fvm/bin/fvm flutter run -d macos` starts the app on macOS; use `-d chrome` or a device as needed.
- `/Users/ronan/fvm/bin/fvm flutter test` runs unit and widget tests.
- `/Users/ronan/fvm/bin/fvm flutter analyze` runs Flutter lints and strict analyzer checks.

## Coding Style & Naming Conventions

Backend PHP follows Laravel conventions, PSR-4 autoloading, explicit parameter and return types, PHP 8 constructor promotion, and TitleCase enum cases. Keep controllers thin: authorization and validation in controllers/Form Requests, business logic in services.

Frontend Dart uses `flutter_lints` plus strict casts, inference, and raw types. Use single quotes, trailing commas, relative imports inside `lib/`, `package:for_tech_lead/...` imports in tests, and declared return types. Follow the feature pattern: Screen -> ViewModel -> Repository -> Service -> Model.

## Testing Guidelines

Backend tests use Pest. Prefer feature tests for API behavior and factories for data setup; run focused tests with `php artisan test --compact --filter=testName` when iterating.

Frontend tests use `flutter_test` and `mocktail`. Keep tests near the matching feature path, such as `test/features/people/viewmodels/..._test.dart`.

## Integrations & Webhook Rules

Laravel owns external webhook ingestion. Register systems through `integration-systems`, return plaintext tokens only on creation, store token hashes, persist raw webhook events, enforce idempotency by provider event id, map external actor codes through `person-external-identities`, and generate `person-delivery-metrics`. Flutter should only configure systems, map people, and display metrics.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit-style prefixes such as `feat:`, `feat(scope):`, and `docs:`. Keep subjects imperative and scoped when useful, for example `feat(people): add birthday filters`.

Pull requests should describe the change, list test commands run, link related issues, and include screenshots or recordings for visible UI changes. Note migrations, new environment variables, or dependency changes explicitly.

## Agent-Specific Instructions

Read `backend/AGENTS.md` before changing Laravel code. Use `rtk` before shell commands in this workspace, for example `rtk git status`.

Project agents are configured in `.codex/agents/`. Their capabilities and maximum
review-correction cycle are governed by `.codex/agent-capabilities.toml`. Run
`php scripts/validate-codex-agents.php` after changing an agent configuration.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

@RTK.md
