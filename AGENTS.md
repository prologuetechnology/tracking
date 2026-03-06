# AGENTS Instructions — Pattern Handoff

This repository is using a reusable onboarding kit so every new agent can pick up the project conventions quickly.

## Primary operating rule

- Treat this repository as a pattern template.
- Before coding, identify stack differences, then map source patterns to target implementations.
- Preserve existing business rules and permissions; avoid redesigning core architecture unless requested.

## Read-first order for any new agent

- `AGENTS.md`
- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/source-project-reference.md`
- `docs/transfer-kit/files-to-transfer.md`
- `docs/transfer-kit/pattern-adaptation-pass.md`
- `docs/transfer-kit/agent-handoff-source.md`
- `docs/context-index.md`

## Code and architecture conventions to preserve

- one concern per file
- domain-scoped composables (queries/mutations)
- route/action/controller/actions layer
- backend boot and frontend boot are source-of-truth and must be inspected
- initial data hydration for pages relying on query/mutation data
- shadcn-first UI choices
- explicit auth + billing + privacy boundaries
- named exports over deep barrel export chains
- local adjacent barrel exports only
- config-driven metadata catalogs where practical
- avoid logging raw payload content; log IDs/metadata instead
- admin features are permission-gated and visible only to allowed roles

## For implementation work

- keep changes scoped and reversible
- do not copy route names or policy names directly without adaptation
- do not copy boot/provider assumptions directly without adaptation
- explicitly document "adopt/adapt/skip" decisions before edits
- keep changelog and roadmap updated as work lands

## Quick failure mode reminders

- If stack differs (Vue/React, Sanctum/Cashier variants, route naming, auth model), perform mapping before coding.
- If build boot differs (theme providers, analytics init, Inertia entry, middleware aliases), map that before coding.
- If any uncertainty exists around auth, billing, or admin scope, stop and confirm before pushing code.
