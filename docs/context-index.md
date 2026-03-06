# Context Index

This file is the first local index for architecture, onboarding, and release
context in this repository.

## Read First

- `AGENTS.md`
- `README.md`
- `docs/architecture-conventions.md`
- `docs/project-scaffold-playbook.md`
- `docs/vue-frontend-structure-contract.md`
- `docs/agent-handoff.md`

## Source Of Truth Files

- Backend boot:
  - `bootstrap/app.php`
- Frontend boot:
  - `resources/js/app.js`
- Web routes:
  - `routes/web.php`
- API routes:
  - `routes/api.php`
- Shared Inertia props:
  - `app/Http/Middleware/HandleInertiaRequests.php`
- Auth/session config:
  - `config/auth.php`
  - `config/sanctum.php`
  - `config/services.php`
- Permissions config:
  - `config/permission.php`
- Build/test tooling:
  - `composer.json`
  - `package.json`

## Local Project Docs

- Architecture rules:
  - `docs/architecture-conventions.md`
- Backend and frontend scaffold map:
  - `docs/project-scaffold-playbook.md`
- Vue frontend contract:
  - `docs/vue-frontend-structure-contract.md`
- Current priorities and handoff notes:
  - `docs/agent-handoff.md`
- Planned work:
  - `docs/roadmap.md`
- Daily development workflow:
  - `docs/dev-runbook.md`
- Release expectations:
  - `docs/release-process.md`

## Transfer-Kit Reference

- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/source-project-reference.md`
- `docs/transfer-kit/pattern-adaptation-pass.md`
- `docs/transfer-kit/architecture-conventions.md`

Use the transfer-kit docs as reusable reference material. Use the local docs
above as the project-specific contract for this repository.
