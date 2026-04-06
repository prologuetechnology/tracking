# Agent Handoff

Last updated: 2026-03-06

## Current Focus

- Transfer-kit alignment for the local Laravel + Inertia Vue codebase.
- Replace route-closure page assembly with thin page controllers.
- Move domain workflows from controllers into actions/services.
- Standardize Vue Query hydration and authorization helper usage.
- Keep route names, permission names, and product scope stable while refactoring.

## Active Workstreams

- Documentation baseline:
  - `README.md`
  - `docs/context-index.md`
  - `docs/architecture-conventions.md`
  - `docs/project-scaffold-playbook.md`
  - `docs/vue-frontend-structure-contract.md`
  - `docs/roadmap.md`
- Backend alignment:
  - page controllers for admin and tracking surfaces
  - action/resource extraction for companies, themes, allowed domains, RBAC,
    and tracking
- Verification:
  - add feature tests for page access and hydrated props
  - document lint/build/test commands in package/composer scripts

## Working Assumptions

- The repo remains Vue-first.
- Billing and tiering are out of scope unless local requirements appear.
- Admin permissions remain colon-delimited and unchanged in this pass.
- External Pipeline integration is sensitive and should be refactored last.
