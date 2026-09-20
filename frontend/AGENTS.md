# Frontend Repository Guidelines

## Scope

This directory contains the Flutter client of the ForTechLead monorepo. Backend
code, migrations, webhook ingestion, and Laravel tests belong under `backend/`.

## Architecture

Follow the feature flow:

`Screen -> ViewModel -> Repository -> Service -> Model`

- Screens must not import Services directly.
- ViewModels receive Repositories through constructor injection.
- Repositories map API JSON into immutable typed Models.
- Services own Dio calls and exception mapping.
- Keep feature code under `lib/features/<feature>/` and shared code under `lib/core/`.
- Tests mirror the `lib/` structure under `test/` and import app code through
  `package:for_tech_lead/...`.

## Commands

Run from `frontend/` and use FVM:

- `/Users/ronan/fvm/bin/fvm flutter test --no-pub`
- `/Users/ronan/fvm/bin/fvm flutter analyze --no-pub`
- `/Users/ronan/fvm/bin/fvm dart format lib test`

## API boundary

Laravel owns webhook ingestion and business rules. Flutter consumes the typed
API contract and must not reproduce backend projection or KPI calculation logic.

## Monorepo and Graphify

The Git repository root is the parent directory. Use the root Graphify output for
cross-layer questions and update it from the monorepo root after code changes:

`graphify update .`

Do not create or commit a second `frontend/graphify-out/` directory.
