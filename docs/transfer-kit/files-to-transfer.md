# Files To Transfer

This is the exact documentation bundle to copy into another existing project when you want that project to inherit the architecture, workflow, and implementation patterns from this codebase.

## Required transfer set

These are the exact source-path to target-path copies to make.

- source `docs/transfer-kit/AGENTS.md` -> target `AGENTS.md`
- source `docs/transfer-kit/project-transfer-kit.md` -> target `docs/transfer-kit/project-transfer-kit.md`
- source `docs/transfer-kit/source-project-reference.md` -> target `docs/transfer-kit/source-project-reference.md`
- source `docs/transfer-kit/files-to-transfer.md` -> target `docs/transfer-kit/files-to-transfer.md`
- source `docs/transfer-kit/pattern-adaptation-pass.md` -> target `docs/transfer-kit/pattern-adaptation-pass.md`
- source `docs/transfer-kit/agent-onboarding-prompt.md` -> target `docs/transfer-kit/agent-onboarding-prompt.md`
- source `docs/transfer-kit/agent-handoff-source.md` -> target `docs/transfer-kit/agent-handoff-source.md`
- source `docs/transfer-kit/project-scaffold-playbook.md` -> target `docs/transfer-kit/project-scaffold-playbook.md`
- source `docs/transfer-kit/architecture-conventions.md` -> target `docs/transfer-kit/architecture-conventions.md`
- source `docs/transfer-kit/react-frontend-structure-contract.md` -> target `docs/transfer-kit/react-frontend-structure-contract.md`
- source `docs/transfer-kit/source-context-index.md` -> target `docs/transfer-kit/source-context-index.md`
- source `docs/transfer-kit/release-process.md` -> target `docs/transfer-kit/release-process.md`
- source `docs/transfer-kit/source-roadmap.md` -> target `docs/transfer-kit/source-roadmap.md`
- source `docs/transfer-kit/source-readme.md` -> target `docs/transfer-kit/source-readme.md`
- source `docs/transfer-kit/source-changelog.md` -> target `docs/transfer-kit/source-changelog.md`

## Recommended operational add-ons

Copy these when the target project also needs release and operations discipline from day one.

- source `docs/transfer-kit/dev-runbook.md` -> target `docs/transfer-kit/dev-runbook.md`
- source `docs/transfer-kit/forge-deploy-runbook.md` -> target `docs/transfer-kit/forge-deploy-runbook.md`
- source `docs/transfer-kit/incident-guardrails-runbook.md` -> target `docs/transfer-kit/incident-guardrails-runbook.md`
- source `docs/transfer-kit/production-go-live-checklist.md` -> target `docs/transfer-kit/production-go-live-checklist.md`
- source `docs/transfer-kit/api-docs-scribe.md` -> target `docs/transfer-kit/api-docs-scribe.md`
- source `docs/transfer-kit/multi-chat-collaboration-playbook.md` -> target `docs/transfer-kit/multi-chat-collaboration-playbook.md`
- source `docs/transfer-kit/workstream-locks.md` -> target `docs/transfer-kit/workstream-locks.md`
- source `docs/transfer-kit/stability-and-release-implementation-list.md` -> target `docs/transfer-kit/stability-and-release-implementation-list.md`

## Domain-specific optional docs

Only copy these if the target project has similar needs or you want example product documentation patterns.

- source `docs/admin-rbac-usage-snippets.md` -> target `docs/transfer-kit/admin-rbac-usage-snippets.md`
- source `docs/user/getting-started.md` -> target `docs/transfer-kit/user-getting-started.md`
- source `docs/user/snippets.md` -> target `docs/transfer-kit/user-snippets.md`
- source `docs/user/sharing.md` -> target `docs/transfer-kit/user-sharing.md`
- source `docs/user/tools.md` -> target `docs/transfer-kit/user-tools.md`
- source `docs/user/api-access.md` -> target `docs/transfer-kit/user-api-access.md`
- source `docs/user/privacy.md` -> target `docs/transfer-kit/user-privacy.md`

## Root-level target placement

In the target project, use this structure:

- `AGENTS.md`
- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/pattern-adaptation-pass.md`
- `docs/transfer-kit/agent-onboarding-prompt.md`
- `docs/transfer-kit/source-project-reference.md`
- `docs/transfer-kit/files-to-transfer.md`
- `docs/transfer-kit/agent-handoff-source.md`

## First-read order in the target repo

1. `AGENTS.md`
2. `docs/transfer-kit/project-transfer-kit.md`
3. `docs/transfer-kit/source-project-reference.md`
4. `docs/transfer-kit/pattern-adaptation-pass.md`
5. target project's own `docs/context-index.md`

## Transfer note

Do not copy these files blindly and start coding.

First:

- map the stack
- map the app surfaces
- map authorization and billing
- adapt route names and vocabulary
- document Adopt / Adapt / Skip decisions
