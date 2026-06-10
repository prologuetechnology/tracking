# Context Index

This file is the first local index for architecture, onboarding, and release
context in this repository.

## Onboarding Path

Read these in order:

1. `AGENTS.md`
2. `README.md`
3. `docs/local-setup.md`
4. `docs/dev-runbook.md`
5. `docs/context-index.md`
6. `docs/architecture-conventions.md`
7. `docs/project-scaffold-playbook.md`
8. `docs/vue-frontend-structure-contract.md`
9. `docs/agent-handoff.md`

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
- Request/log context:
  - `app/Http/Middleware/AttachRequestContext.php`
- Auth/session config:
  - `config/auth.php`
  - `config/sanctum.php`
  - `config/services.php`
  - `config/socialite.php`
- Storage config:
  - `config/filesystems.php`
- Permissions config:
  - `config/permission.php`
- Build/test tooling:
  - `composer.json`
  - `package.json`
  - `.env.example`
  - `.env.dusk.local`

## Local Project Docs

- Fresh-machine setup:
  - `docs/local-setup.md`
- Daily development workflow:
  - `docs/dev-runbook.md`
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
- Release expectations:
  - `docs/release-process.md`
- Browser smoke test runtime:
  - `tests/DuskTestCase.php`
  - `tests/Browser/AdminSmokeTest.php`
- Image admin/API contract coverage:
  - `tests/Feature/ImageAdminAlignmentTest.php`
- Admin API hardening coverage:
  - `tests/Feature/AdminApiHardeningTest.php`
- Pattern-alignment architecture coverage:
  - `tests/Feature/PatternAlignmentArchitectureTest.php`

## Transfer-Kit Reference

- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/source-project-reference.md`
- `docs/transfer-kit/pattern-adaptation-pass.md`
- `docs/transfer-kit/architecture-conventions.md`

Use the transfer-kit docs as reusable reference material. Use the local docs
above as the project-specific contract for this repository.
