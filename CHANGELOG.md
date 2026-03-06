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
