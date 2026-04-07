# Changelog

## Unreleased

### Added

- Local onboarding and architecture baseline docs for the transfer-kit
  alignment.
- A dedicated Herd-first fresh-machine setup guide in `docs/local-setup.md`.
- Additional production-confidence coverage for active admin middleware,
  super-admin page hydration, branded tracking success rendering, and the
  remaining normalized resource payloads.
- SVG upload regression coverage for the shared image library under Laravel's
  explicit SVG image validation rules.

### Changed

- Transfer-kit adaptation pass completed for the Laravel + Inertia Vue stack.
- Upgraded the application runtime from Laravel 11 to Laravel 13 on PHP 8.4,
  including stable compatible package lines for Inertia Laravel, Sanctum,
  Socialite, Dusk, Pest, PHPUnit, Spatie Permission, Ziggy, and Tinker.
- Repo conventions now explicitly document server-first hydration, thin page
  controllers, action/service layering, and Vue Query domain composables.
- `.env.example` is now a project-specific local env contract covering Azure
  OAuth, login allowlists, Pipeline credentials, and local image-storage
  choices.
- `README.md` is now a detailed developer onboarding guide for Herd-based local
  setup and project-specific operational realities.
- `docs/dev-runbook.md` now focuses on daily Herd-first commands and operational
  gotchas, while `docs/local-setup.md` owns fresh-machine bootstrap.
- PHPUnit now targets sqlite in testing by default so `php artisan test` does
  not depend on a local MySQL database.
- OAuth-aligned auth coverage replaced the stale Laravel password/profile
  scaffold tests.
- Users, roles, and permissions now use resource-backed page/API payloads with
  stable initial hydration props and thin action-driven controllers.
- Tracking search, coordinates, documents, and branded tracking hydration now
  use normalized resource-backed payloads instead of leaking raw Pipeline
  envelopes into Vue.
- Dusk is now wired for local/testing with a dedicated provider path and a
  shared sqlite browser-test environment.
- Image admin now uses page-controller hydration, read-only image type catalog
  data, resource-backed image payloads, and request-scoped auth on the active
  index/store/destroy surface.
- Company asset dialogs now use the shared image library for logo, banner, and
  footer assignment. Uploads from company screens create shared library images
  and auto-assign them, while company edit screens only clear slot references
  instead of deleting global image records.
- Theme create and edit flows now invalidate the correct Vue Query caches,
  redirect back to the themes index after save, and use the correct theme show
  query contract in the edit surface. Browser coverage now verifies both theme
  creation and update from the admin UI.
- Company API token store/validate flows now use request/action/resource
  layering with corrected Pipeline company matching and stable token payloads.
- Impersonation keeps its `/api/...` endpoints but now runs on browser-session
  auth middleware so redirect-based impersonation and restore flows are
  coherent in both the app and the test suite.
- Dusk now boots its local app server with explicit dusk-local app URL, asset
  URL, and sqlite environment values so browser runs do not inherit the base
  `tracking.test` asset host.
- Company brand lookups now use a database-agnostic exact-match check instead
  of a MySQL-only `BINARY` clause, keeping branded tracking resolution
  consistent in both sqlite-backed tests and production.
- Dusk now starts its local test server with the active PHP binary so browser
  runs stay aligned with the PHP 8.4 framework runtime.
