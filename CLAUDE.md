# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository structure

This is a monorepo with two independent projects wired through an API-first contract:

- `backend/` — Laravel 13 API (PHP 8.5)
- `frontend/` — Flutter app (Dart SDK ^3.12.2)

Both projects have moved past scaffold. `backend/` has an established CRUD architecture (route → controller → service, see "API architecture" below) with `Team`/`Person` reference implementations plus custom append-only flows for Daily and webhooks. `frontend/` has an established MVVM architecture (Screen → ViewModel → Repository → Service → Model) and calls the Laravel API end-to-end — new modules on either side should follow the existing reference implementation rather than reinvent the pattern. See `backend/CLAUDE.md` and `frontend/CLAUDE.md` for the full details of each.

## Backend (`backend/`)

Laravel 13 + PHP 8.5, using Pest v4 for testing, Pint for formatting, and Laravel Boost (MCP server + skills) for AI-assisted development.

### Docker dev environment

The backend runs in Docker. Everything Docker-related lives under `backend/` (compose file, Dockerfiles, nginx config, persistent data) — the repo root is monorepo-only and has no Docker assets.

- `backend/docker-compose.yml` — services: `app` (PHP-FPM), `nginx`, `postgres`, `queue` (same image as `app`, runs `queue:work`), `redis`, `minio` (S3-compatible object storage), plus a one-shot `minio-init` that creates the default bucket.
- `backend/.docker/` — `php/` (shared Dockerfile + entrypoint + php.ini for `app`/`queue`), `nginx/conf.d/` (server block), `volumes/` (bind-mounted Postgres/Redis/MinIO data, gitignored).
- All ports, passwords, and names are configurable via `backend/.env` (same file Laravel itself reads — see the "Docker Compose" section within it) with `backend/.env.example` as the template. `backend/.env`'s `DB_HOST`/`REDIS_HOST`/`AWS_ENDPOINT` default to `127.0.0.1` (published ports) for host-side `artisan`; the `app`/`queue` containers get these overridden to the in-network service names (`postgres`/`redis`/`minio`) via `docker-compose.yml`.
- Defaults: Postgres (not SQLite) for `DB_CONNECTION`, Redis for cache/session/queue, S3 driver (via MinIO) for `FILESYSTEM_DISK` — requires the `league/flysystem-aws-s3-v3` composer package (already added).

```bash
docker compose up -d --build             # from backend/: build + start all services
docker compose exec app php artisan migrate
docker compose logs -f queue             # tail the queue worker
docker compose down                      # stop (data persists under .docker/volumes/)
```

### Commands

Run all commands from `backend/` (either on the host, if PHP/Composer are installed locally, or via `docker compose exec app ...`).

```bash
composer dev          # runs server + queue listener + pail (logs) + vite concurrently
php artisan serve     # server only
php artisan test --compact                       # run all tests
php artisan test --compact --filter=testName      # run a single test
vendor/bin/pint --dirty --format agent            # format only changed PHP files (run after any PHP edit)
php artisan route:list
php artisan make:test --pest SomeFeatureTest      # add --unit for Unit tests
```

### API architecture

The backend is API-only (`routes/api.php`, versioned under `api/v1`) and follows a strict
`route → controller → service` split, with almost all CRUD behavior reusable via two traits:

- `App\Http\Controllers\Concerns\CrudControllerTrait` gives a controller the full `apiResource` set
  (`index`/`store`/`show`/`update`/`destroy`) for free. A standard CRUD controller is just a constructor
  setting `$this->model`, `$this->resource`, and optionally `$this->{store,update,index}Request` —
  see `App\Http\Controllers\Api\V1\TeamController` for the minimal reference example.
- `App\Models\Concerns\Filterable` gives a model `filter()`/`search()`/`order()` query scopes, driven by
  the model's own `filterableFields()`/`searchableFields()`/`sortableFields()` allow-lists. Query string
  convention: `?filters[column]=value&order[column]=asc&search=term` (`order` supports multiple columns).
- When an operation needs logic beyond the default, set `$this->{store,update,delete,index}Service` in the
  controller's constructor to a class implementing the matching contract in `App\Contracts\Services`
  (otherwise the trait falls back to `App\Services\Generic{Store,Update,Delete,Index}Service`).

Full details, code shape, and the step-by-step recipe for adding a new CRUD module live in `backend/CLAUDE.md`
("CRUD architecture" section) — read that before touching `app/Http/Controllers`, `app/Services`, or
`app/Models` in the backend.

Auth is `laravel/sanctum` bearer tokens (not cookie/session) — `POST /auth/register` and `POST
/auth/login` are public, everything else (including `teams`/`people`) requires
`Authorization: Bearer <token>` via the `auth:sanctum` guard. Registering auto-authenticates (returns a
token immediately, same as login). See `backend/CLAUDE.md`'s "Authentication" section for the full
contract, including the generic-credentials-error behavior and the `Sanctum`-guard testing gotcha.

### Conventions (from Laravel Boost guidelines, `backend/CLAUDE.md`)

These are enforced project guidelines — follow them exactly:

- Use `php artisan make:*` to scaffold new files (migrations, controllers, models, tests, etc.) rather than hand-writing boilerplate.
- After editing any PHP file, run `vendor/bin/pint --dirty --format agent` before finishing.
- Use PHP 8 constructor property promotion; explicit return types and param type hints everywhere; TitleCase Enum keys.
- Default to Feature tests (Pest) over Unit tests; use model factories, not manual instantiation.
- Default to Eloquent API Resources + API versioning for new API routes.
- Use named routes + `route()` for links, not hardcoded URLs.
- Don't create new top-level directories or add dependencies without approval; don't create documentation files unless explicitly asked.
- Two Boost skills are auto-loaded from `backend/.claude/skills/`: `laravel-best-practices` and `pest-testing` — invoke them when touching backend PHP or tests respectively.
- Laravel Boost MCP tools (`search-docs`, `database-query`, `database-schema`, `get-absolute-url`, `browser-logs`) are available and preferred over manual equivalents (raw SQL via tinker, manual URL construction, etc.) — see `backend/CLAUDE.md` for full tool guidance.

## Frontend (`frontend/`)

Flutter app targeting Android, iOS, web, macOS, Linux, and Windows (mobile-first, but macOS/Windows
desktop must work well too — all platform folders are present and scaffolded).

### Commands

Run all commands from `frontend/`, via `/Users/ronan/fvm/bin/fvm flutter ...` in this environment.

```bash
/Users/ronan/fvm/bin/fvm flutter run -d macos    # or -d chrome / a connected mobile device/emulator
/Users/ronan/fvm/bin/fvm flutter test            # unit + widget tests
/Users/ronan/fvm/bin/fvm flutter analyze         # lint (tightened rules — see frontend/analysis_options.yaml)
/Users/ronan/fvm/bin/fvm flutter pub get         # install dependencies after editing pubspec.yaml
```

### Frontend architecture

MVVM: `Screen → ViewModel → Repository → Service → Model`, mirroring the backend's
`route → controller → service` discipline on the Laravel side.

- **Screens never import Services** — only ViewModels (`extends BaseViewModel`,
  `lib/core/viewmodels/base_view_model.dart`), created per-route via `ChangeNotifierProvider` and
  consumed via `Consumer`/`Selector` for granular rebuilds (never one `Consumer` around a whole
  screen).
- **Repositories are only used inside Services and ViewModels**, registered as `get_it` singletons in
  `lib/bootstrap.dart` — never instantiated in a Screen.
- `dio` (via `lib/core/network/dio_client.dart`) + a debug-only logging interceptor + typed
  `ApiException` subclasses (mirroring Laravel's validation-error shape) — see
  `lib/core/network/api_exception.dart`.
- Central design-system theme (`lib/core/theme/`) and a reusable component library
  (`lib/core/widgets/`: buttons, inputs, `AppDataTable`) — widgets always read tokens via
  `Theme.of(context)`, never hardcoded colors/spacing.
- Responsive shell (`lib/core/responsive/`): bottom nav on mobile widths, `NavigationRail` on desktop
  widths, wired as a go_router `ShellRoute`.
- Reference implementation: `lib/features/teams/` — copy this shape for every new feature. Full
  recipe and rationale in `frontend/CLAUDE.md`.
- Auth: `lib/core/auth/auth_session.dart` (`AuthSession`) is cross-cutting session state (like
  `DioClient`, not a feature) — persists the bearer token via secure storage, drives `go_router`'s
  login/register guard as its `refreshListenable`, and is what `AuthInterceptor` attaches to every
  request and clears on a 401. `lib/features/auth/` follows the same Model → Service → Repository →
  ViewModel → Screen shape as `teams`. Full details in `frontend/CLAUDE.md`'s "Authentication" section.
- Imports: the Flutter package is `for_tech_lead`. Tests import app code as `package:for_tech_lead/...`;
  stale `package:frontend/...` imports break the full suite at compile time.

### Integrations and webhooks

Laravel receives external system webhooks; Flutter manages configuration and displays mapped metrics.
Register external systems through `integration-systems`, return the plaintext webhook token only on
creation, store only token hashes, persist raw webhook events, enforce idempotency by provider event id,
map external actor codes through `person-external-identities`, and generate read-only
`person-delivery-metrics`. The public webhook route is intentionally outside Sanctum and must validate
the integration token on every request.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

<!-- rtk-instructions v2 -->
# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash
# ❌ Wrong
git add . && git commit -m "msg" && git push

# ✅ Correct
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)
```bash
rtk cargo build         # Cargo build output
rtk cargo check         # Cargo check output
rtk cargo clippy        # Clippy warnings grouped by file (80%)
rtk tsc                 # TypeScript errors grouped by file/code (83%)
rtk lint                # ESLint/Biome violations grouped (84%)
rtk prettier --check    # Files needing format only (70%)
rtk next build          # Next.js build with route metrics (87%)
```

### Test (90-99% savings)
```bash
rtk cargo test          # Cargo test failures only (90%)
rtk vitest run          # Vitest failures only (99.5%)
rtk playwright test     # Playwright failures only (94%)
rtk test <cmd>          # Generic test wrapper - failures only
```

### Git (59-80% savings)
```bash
rtk git status          # Compact status
rtk git log             # Compact log (works with all git flags)
rtk git diff            # Compact diff (80%)
rtk git show            # Compact show (80%)
rtk git add             # Ultra-compact confirmations (59%)
rtk git commit          # Ultra-compact confirmations (59%)
rtk git push            # Ultra-compact confirmations
rtk git pull            # Ultra-compact confirmations
rtk git branch          # Compact branch list
rtk git fetch           # Compact fetch
rtk git stash           # Compact stash
rtk git worktree        # Compact worktree
```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)
```bash
rtk gh pr view <num>    # Compact PR view (87%)
rtk gh pr checks        # Compact PR checks (79%)
rtk gh run list         # Compact workflow runs (82%)
rtk gh issue list       # Compact issue list (80%)
rtk gh api              # Compact API responses (26%)
```

### JavaScript/TypeScript Tooling (70-90% savings)
```bash
rtk pnpm list           # Compact dependency tree (70%)
rtk pnpm outdated       # Compact outdated packages (80%)
rtk pnpm install        # Compact install output (90%)
rtk npm run <script>    # Compact npm script output
rtk npx <cmd>           # Compact npx command output
rtk prisma              # Prisma without ASCII art (88%)
```

### Files & Search (60-75% savings)
```bash
rtk ls <path>           # Tree format, compact (65%)
rtk read <file>         # Code reading with filtering (60%)
rtk grep <pattern>      # Search grouped by file (75%)
rtk find <pattern>      # Find grouped by directory (70%)
```

### Analysis & Debug (70-90% savings)
```bash
rtk err <cmd>           # Filter errors only from any command
rtk log <file>          # Deduplicated logs with counts
rtk json <file>         # JSON structure without values
rtk deps                # Dependency overview
rtk env                 # Environment variables compact
rtk summary <cmd>       # Smart summary of command output
rtk diff                # Ultra-compact diffs
```

### Infrastructure (85% savings)
```bash
rtk docker ps           # Compact container list
rtk docker images       # Compact image list
rtk docker logs <c>     # Deduplicated logs
rtk kubectl get         # Compact resource list
rtk kubectl logs        # Deduplicated pod logs
```

### Network (65-70% savings)
```bash
rtk curl <url>          # Compact HTTP responses (70%)
rtk wget <url>          # Compact download output (65%)
```

### Meta Commands
```bash
rtk gain                # View token savings statistics
rtk gain --history      # View command history with savings
rtk discover            # Analyze Claude Code sessions for missed RTK usage
rtk proxy <cmd>         # Run command without filtering (for debugging)
rtk init                # Add RTK instructions to CLAUDE.md
rtk init --global       # Add RTK to ~/.claude/CLAUDE.md
```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->