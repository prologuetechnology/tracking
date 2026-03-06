# Changelog

## Unreleased

### Added

- Local onboarding and architecture baseline docs for the transfer-kit
  alignment.

### Changed

- Transfer-kit adaptation pass completed for the Laravel + Inertia Vue stack.
- Repo conventions now explicitly document server-first hydration, thin page
  controllers, action/service layering, and Vue Query domain composables.
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
- Company API token store/validate flows now use request/action/resource
  layering with corrected Pipeline company matching and stable token payloads.
- Impersonation keeps its `/api/...` endpoints but now runs on browser-session
  auth middleware so redirect-based impersonation and restore flows are
  coherent in both the app and the test suite.
