---
name: laravel-quality-testing
description: Use when adding or improving tests, architecture checks, linting, static analysis, predeploy scripts, CI, route screenshots, and frontend quality gates in a Laravel Inertia React or Vue project using Pest, Pint, PHPStan or Larastan, ESLint, Prettier, Vite, and Playwright.
---

# Laravel Quality Testing

Use this skill to make quality checks executable rather than tribal knowledge.

## Companion Rules

Use this in tandem with Laravel Boost, Laravel best-practice rules, and the project's Pest/testing rules:

- Read Boost application metadata before assuming Laravel, Pest, PHPUnit, PHPStan, Tailwind, or frontend package versions.
- Use Boost `search-docs` before changing testing APIs, Inertia assertions, framework test helpers, or package-specific fakes.
- Use Boost `database-schema` when tests depend on schema, factories, migrations, or tenant ownership columns.
- Use Boost `browser-logs` and app logs when debugging frontend or browser-test failures.
- Keep verification programmatic and run the smallest useful test slice.

## First Checks

Inspect:

- `tests/Pest.php`
- `tests/TestCase.php`
- `tests/Feature/**`
- `tests/Unit/**`
- `database/factories/**`
- `phpunit.xml`
- `phpstan.neon`
- `pint.json`
- `eslint.config.js`
- `prettier.config.*`
- `vite.config.js`
- `playwright.config.*`
- `package.json`
- scripts under `scripts/`
- CI workflows if present

Use version-specific docs for Pest, Laravel testing, Inertia testing, Playwright, and static analysis.

## Pest Bootstrap

Keep `tests/Pest.php` small:

```php
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
```

Avoid global helper sprawl. Put one-off helpers at the bottom of the test file that needs them.

Use Pest `it(...)` with behavior names.

## Feature Tests

Prefer full boundary coverage:

- HTTP requests through named routes
- `actingAs()` for browser/session auth
- Sanctum token auth where APIs use tokens
- `postJson()`, `getJson()`, `patchJson()`, `deleteJson()`
- `assertInertia()` for page contracts
- `assertJsonPath()` for important response fields
- `assertJsonValidationErrors()` for validation

Cover:

- happy path
- unauthenticated path
- unauthorized path
- validation failure
- tenant/workspace isolation
- important side effects
- normalized error shape for external APIs

Prefer explicit assertions:

- `assertOk()`
- `assertCreated()`
- `assertNoContent()`
- `assertUnauthorized()`
- `assertForbidden()`
- `assertNotFound()`
- `assertUnprocessable()`

Use `assertStatus()` only for less common statuses.

## Inertia Contract Tests

For query-backed pages, test the initial props that hydrate TanStack Query:

```php
$this->actingAs($user)
    ->get(route('authed.domain.index'))
    ->assertOk()
    ->assertInertia(fn (Assert $page) => $page
        ->has('domainPage.initial_items')
        ->has('domainPage.filters'));
```

This catches first-render regressions before the browser does.

## Critical Path Tests

Mark smoke and business-critical flows:

```php
it('renders critical authenticated pages', function () {
    // ...
})->group('critical');
```

Use critical groups in predeploy checks for fast confidence before slower suites.

## Architecture Tests

Encode important architectural invariants as tests.

Examples:

- tenant-owned models implement a shared interface and trait
- route-bound tenant resources include alignment middleware
- controllers avoid direct user-only queries for tenant-owned models
- new tenant-owned create calls include ownership columns
- external API routes include normalized error middleware
- admin routes require permissions

Keep source scans narrow and allowlisted. Do not turn architecture tests into broad lint replacements.

## Factories

Factories should create valid records with relationships by default.

Good factory states:

- `personal()`
- `organization()`
- `owner()`
- `admin()`
- `inactive()`
- `expired()`
- `revoked()`
- `public()`
- `private()`

Use enum-backed defaults where the model uses enums.

Cache expensive defaults such as hashed passwords:

```php
protected static ?string $password;

'password' => static::$password ??= Hash::make('password'),
```

## Test Doubles

Use framework fakes at boundaries:

- `Mail::fake()`
- `Notification::fake()`
- `Bus::fake()`
- `Event::fake()`
- `Http::fake()`

Use `app()->instance()` or Mockery only for service boundaries, provider adapters, and resolvers.

Use time helpers for time-sensitive flows:

- `travelTo()`
- `travel()`
- `Carbon::setTestNow()`

## Test Environment

Prefer fast defaults:

- in-memory SQLite when compatible
- array cache/session
- array mailer
- sync queue
- reduced password hashing rounds
- disabled analytics/telemetry

Make external services fakeable and disabled in tests.

## Static Analysis And Formatting

Use:

- Pint with Laravel preset
- PHPStan/Larastan with Pest extensions
- a checked-in baseline only when needed

Typical `phpstan.neon` shape:

```neon
includes:
    - vendor/pestphp/pest/extension.neon
    - vendor/pestphp/pest/phpstan-pest-extension.neon
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon

parameters:
    paths:
        - app
        - tests
    level: 5
```

Run Pint after PHP edits.

## Frontend Quality

ESLint should enforce:

- selected client recommended rules
- React hooks or Vue composition rules, depending on the adapter
- React refresh or Vue single-file component rules, depending on the adapter
- unused import cleanup
- Prettier formatting
- project-specific architectural bans, such as direct `useEffect`

Prettier should include Tailwind class sorting when Tailwind is used.

Keep ignores explicit:

- vendor
- storage
- public build output
- node_modules
- generated route files

## Vite Build Checks

Keep `vite.config.js` intentional:

- Laravel plugin
- React or Vue plugin
- Tailwind plugin
- `@` alias to `resources/js`
- manual chunks only for heavy real boundaries

Run frontend build in release checks, not only lint.

## Playwright Route Screenshots

For visual/mobile audits:

- use Playwright config with target devices
- use storage state setup for authenticated routes
- discover or centrally list routes
- skip unsafe methods, vendor routes, and raw API endpoints
- write screenshots to a temp/output folder
- hide dev-only fixed widgets that overlap screenshots
- wait for DOM content and a brief settle period

Use screenshot audits for layout regressions, not as a substitute for functional tests.

## Predeploy Script

Create a strict shell script:

```bash
#!/usr/bin/env bash
set -euo pipefail

php artisan test --group=critical
npm run lint
npm run build
```

Add `--full` mode for:

- full Pest suite
- PHPStan
- Pint check
- frontend lint
- frontend build

Respect local PHP runtimes such as Herd when present, but keep the script runnable with plain `php`.

## CI Shape

Split backend and frontend jobs.

Backend:

- install PHP
- install Composer dependencies
- prepare `.env`
- generate app key
- run Pest
- run PHPStan
- run Pint check

Frontend:

- install Node from project version file
- `npm ci`
- lint
- build

## Verification Rule

Every code change needs the smallest meaningful programmatic check:

- backend behavior: focused Pest file or filter
- PHP formatting: Pint for dirty PHP files
- static typing: PHPStan when types/contracts change
- frontend behavior: ESLint/build and browser check when UI changes
- visual layout: Playwright/browser screenshot for responsive surfaces

Document tests not run in the final handoff.
