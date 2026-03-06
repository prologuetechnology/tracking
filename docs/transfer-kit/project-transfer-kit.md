# Project Transfer Kit

This kit standardizes how we move this project's architecture patterns and operating instructions into another existing project.

It is intentionally practical and short enough to apply in one dev pass.

Use it as a documentation bundle, not as a blind copy/paste template.

## Goal

Enable another built project to inherit:

- backend structure
- frontend conventions
- communication patterns
- security/observability habits
- onboarding workflow
- release/testing expectations

without requiring a full walkthrough from scratch.

## What to copy first

Start with the self-contained transfer kit files:

- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/source-project-reference.md`
- `docs/transfer-kit/files-to-transfer.md`
- `docs/transfer-kit/pattern-adaptation-pass.md`
- `docs/transfer-kit/agent-onboarding-prompt.md`
- `docs/transfer-kit/AGENTS.md`
- `docs/transfer-kit/project-scaffold-playbook.md`
- `docs/transfer-kit/architecture-conventions.md`
- `docs/transfer-kit/react-frontend-structure-contract.md`
- `docs/transfer-kit/agent-handoff-source.md`

Then add the source-reference snapshots if you want fuller context:

- `docs/transfer-kit/source-context-index.md`
- `docs/transfer-kit/release-process.md`
- `docs/transfer-kit/source-roadmap.md`
- `docs/transfer-kit/source-readme.md`
- `docs/transfer-kit/source-changelog.md`

If you want the kit to carry release and operations process too, include:

- `docs/transfer-kit/dev-runbook.md`
- `docs/transfer-kit/forge-deploy-runbook.md`
- `docs/transfer-kit/incident-guardrails-runbook.md`
- `docs/transfer-kit/production-go-live-checklist.md`
- `docs/transfer-kit/api-docs-scribe.md`
- `docs/transfer-kit/multi-chat-collaboration-playbook.md`
- `docs/transfer-kit/workstream-locks.md`
- `docs/transfer-kit/stability-and-release-implementation-list.md`

Use `docs/transfer-kit/files-to-transfer.md` as the exact source-path to target-path manifest.

## Source project truth set

These files describe how this project is actually built and should be reviewed before adapting patterns elsewhere:

- `composer.json`
- `package.json`
- `bootstrap/app.php`
- `routes/web.php`
- `routes/api.php`
- `config/tools.php`
- `docs/context-index.md`
- `docs/project-scaffold-playbook.md`
- `docs/architecture-conventions.md`
- `docs/react-frontend-structure-contract.md`
- `docs/transfer-kit/source-project-reference.md`

## Transfer philosophy

The correct workflow is:

1. understand the source project
2. inspect the target project
3. map differences explicitly
4. only then move patterns over

Do not move route names, permission names, billing assumptions, or domain vocabulary over without adaptation.

## One-time adaptation pass

Run this pass before any implementation in the target project:

1) Map stack equivalents

Record differences in:

- framework/version
- auth/session model
- frontend runtime
- UI library
- queue/cache/logging
- test stack

2) Map app surfaces

In the target project, identify where each surface lives:

- public web surface
- authenticated web surface
- JSON API surface
- admin surface
- external/public API surface

3) Replace domain-specific references

In each copied doc, replace product-specific terms with neutral equivalents:

- snippets/tool references
- billing/subscription names
- admin group names
- feature labels and route names
- team/member language

4) Confirm enforcement boundaries

Verify each target project has explicit ownership separation for:

- business rules
- authorization
- rate limits
- quota visibility
- data residency/logging policy

5) Validate initial data patterns

For each Inertia page with remote data:

- either pass `initialData` from web route props
- or intentionally choose loading-first where unavoidable

6) Validate boot and build assumptions

Confirm the target project has an equivalent answer for:

- backend app boot
- frontend app boot
- route helper strategy
- auth/session bootstrapping
- theme/provider initialization
- analytics/error collection initialization
- testing/lint/build commands

## Exact transfer manifest

Use this file as the transfer checklist:

- `docs/transfer-kit/files-to-transfer.md`

## New project starter structure for copied docs

Recommended structure in the target repo:

- `AGENTS.md`
- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/pattern-adaptation-pass.md`
- `docs/transfer-kit/agent-onboarding-prompt.md`
- `docs/transfer-kit/source-project-reference.md`
- `docs/transfer-kit/files-to-transfer.md`
- `docs/transfer-kit/agent-handoff-source.md`

This keeps historical project notes separate from reusable patterns.

## How to brief the next agent

Give the new agent this sequence:

1) read:
   - `AGENTS.md`
   - `docs/transfer-kit/project-transfer-kit.md`
   - `docs/transfer-kit/source-project-reference.md`
   - `docs/transfer-kit/pattern-adaptation-pass.md`
   - current project `docs/context-index.md`
2) complete the adaptation pass and mark all pass/fail decisions
3) list 2-3 assumptions from the mapping before making edits
4) only then start implementation

## Anti-regression guardrails for transfer

- Do not apply copied route names directly; map them.
- Do not apply copied permissions by name directly; map them to existing authorization model.
- Do not apply copied CSS classes by name without checking the target design system.
- Do not assume queue, cache, mail, and filesystem drivers are identical.
- Keep tenant-level secrets and env assumptions explicit.

## Tracking this transfer

Create one PR in the target repo with:

- `AGENTS.md` added or adapted
- `docs/transfer-kit/` added
- short `context-index` entry for reusable kit
- `docs/transfer-kit/pattern-adaptation-pass.md` completed as baseline

From this point forward, treat the kit as the single source for architectural onboarding.
