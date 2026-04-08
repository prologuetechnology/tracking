# Agent Handoff

Last updated: 2026-04-06

## Current Focus

- Keep the transfer-kit-aligned Laravel + Inertia Vue app stable while
  preserving existing route names, permissions, and product scope.
- The framework/runtime baseline is now Laravel 13 on PHP 8.4.
- Treat the Herd-first onboarding docs and `.env.example` as source-of-truth
  for fresh-machine setup.
- Keep admin hydration, tracking integrations, and auth boundaries documented as
  the code evolves.

## Active Workstreams

- Documentation source of truth:
  - `README.md`
  - `docs/local-setup.md`
  - `docs/dev-runbook.md`
  - `docs/context-index.md`
  - `.env.example`
- Backend alignment:
  - page controllers for admin and tracking surfaces
  - action/resource extraction for companies, themes, allowed domains, RBAC,
    and tracking
- Verification:
  - preserve feature and browser coverage for page access, hydration, and
    tracking-critical workflows
  - keep Herd-first test/build commands documented and current

## Working Assumptions

- The repo remains Vue-first.
- Herd must run this project with PHP 8.4 before Composer, tests, or Dusk are
  executed.
- Herd-managed MySQL is the standard local runtime database.
- SQLite remains the default for automated tests and Dusk browser runs.
- Normal local web login is Azure OAuth plus allowed-domain checks.
- External Pipeline integration is sensitive and should be refactored only with
  deliberate verification.
- Current Dusk browser coverage has known auth/tracking assertion failures in
  `AuthAndNavigationTest` and `TrackingFlowsTest`; backend tests, lint, build,
  and Pint are green on the Laravel 13 upgrade branch.
