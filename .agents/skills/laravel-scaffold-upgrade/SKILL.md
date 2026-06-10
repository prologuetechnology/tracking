---
name: laravel-scaffold-upgrade
description: Use when upgrading a newly scaffolded Laravel app into a production-ready Laravel + Inertia application using the architecture, frontend, SaaS, and quality patterns from this skill set. Supports React or Vue clients. Applies to fresh apps, starter-kit apps, and early projects that need structure before feature work begins.
---

# Laravel Scaffold Upgrade

Use this skill to bring a fresh Laravel project up to a disciplined app baseline.

This is an orchestration skill. Pair it with:

- `laravel-application-architecture`
- `laravel-inertia-frontend`
- `laravel-quality-testing`
- `laravel-saas-systems` only for optional SaaS subsystems the product actually needs

## Ground Rules

Before changing code:

1. Use Laravel Boost if it is installed: read application metadata, inspect database schema when needed, search version-specific docs, use browser logs for frontend failures, and resolve absolute URLs before sharing local links.
2. Follow Laravel best-practice rules in tandem with this skill.
3. Read project package versions from Composer, npm, and Laravel application metadata.
4. Search version-specific docs for Laravel, Inertia, the selected frontend client, Tailwind, Pest, and any installed packages.
5. Inspect existing conventions and starter-kit structure.
6. Choose the smallest useful baseline. Do not scaffold billing, social auth, organizations, webhooks, or API tokens unless the target product needs them.

## Upgrade Order

Use this sequence:

1. Establish quality gates.
2. Establish backend layering.
3. Establish Inertia frontend structure for React or Vue.
4. Establish shared app contracts.
5. Add optional SaaS systems.
6. Add architecture tests.
7. Run focused verification.

## 1. Quality Gates First

Add or confirm:

- Pest configured for Feature and Unit tests.
- `phpunit.xml` optimized for local tests.
- Pint with Laravel preset.
- PHPStan/Larastan if accepted by the project.
- ESLint flat config for the selected frontend client.
- Prettier with Tailwind plugin.
- Vite build script.
- predeploy script with fast and full modes.

Minimum scripts:

```json
{
  "scripts": {
    "build": "vite build",
    "dev": "vite",
    "lint": "eslint . --ext .js,.jsx,.vue,.cjs,.mjs",
    "lint:fix": "eslint . --ext .js,.jsx,.vue,.cjs,.mjs --fix",
    "format": "prettier --write \"**/*.{js,jsx,vue,cjs,mjs,json,md}\""
  }
}
```

Do not add dependencies without approval.

## 2. Backend Baseline

Create or align these folders:

```text
app/
  Actions/
  Services/
  Http/
    Controllers/
    Requests/
    Resources/
    Middleware/
  Models/
    Concerns/
  Policies/
  Enums/
```

Rules:

- Controllers stay thin.
- Actions own use cases.
- Services own reusable domain capabilities.
- Form Requests own validation.
- Resources own JSON/Inertia shaping.
- Policies own resource authorization.
- Config owns catalogs and limits.

Start with one real feature or starter-kit surface and refactor it into this shape rather than creating empty folders everywhere.

## 3. Route Surfaces

Organize routes by audience:

```php
Route::as('guest.')->group(function (): void {
    // public pages
});

Route::middleware(['auth'])->as('authed.')->group(function (): void {
    // Inertia app pages
});

Route::as('api.auth.')->middleware(['auth:sanctum'])->group(function (): void {
    // authenticated JSON endpoints
});
```

Only add admin, external API, or public ingress groups when the product needs them.

Use named routes everywhere so the frontend can use Ziggy instead of hardcoded paths.

## 4. Inertia Shared Props

Establish a small `HandleInertiaRequests` shared prop contract:

- `auth.user`
- `auth.permissions` or `auth.roles` if RBAC exists
- `flash.error`
- `flash.success`
- `app.name`
- `app.env`
- `ziggy`

Add these only when needed:

- `billing.tier`
- `workspace.current`
- `workspace.available`
- `features`
- product catalogs
- notification counters

Keep shared props lazy with closures when they require queries.

## 5. Frontend Structure

Create the structure:

```text
resources/js/
  app.js
  bootstrap.js
  Pages/
  components/
    ui/
    layout/
    feature/
  composables/
    queries/
    mutations/
    hooks/      # React-specific if React is selected
    forms/
    helpers/
  lib/
```

Set `@` alias to `resources/js` in both Vite and `jsconfig.json`.

Mount:

- Inertia app
- TanStack Query provider for React or Vue
- theme provider if dark mode exists
- toast provider if the UI uses command feedback

Create one authenticated layout and one public layout rather than one overloaded shell.

## 6. Query And Mutation Contract

For the first query-backed page:

1. Laravel web route prepares `initial_*` props through resources.
2. Page reads the props with `usePage()`.
3. Domain query composable receives `initialData` through its config/options.
4. Mutations invalidate exported query key factories.

Do not fetch directly inside page components.

## 7. UI Baseline

If shadcn-style primitives are installed:

- keep primitives in `components/ui`
- do not edit generated primitives casually
- build product-specific UI in `components/feature/<domain>`

If primitives are not installed, create only the minimum components needed for current work.

Tailwind CSS v4 baseline should use CSS-first directives and design tokens in `resources/css/app.css`.

## 8. Optional SaaS Systems

Add these only when requested or clearly needed:

- workspace resolver and membership tables
- plan resolver and entitlements
- feature flag registry
- social identities and verified aliases
- API tokens
- notification preferences
- webhook ingress
- sharing links
- audit logs

Each optional system needs:

- config or catalog
- migrations/models
- actions/services
- middleware or policies when applicable
- resources/API contracts
- feature tests

## 9. Architecture Tests

After the baseline exists, add tests for invariants that would be expensive to catch manually:

- route-bound tenant resources include tenant alignment middleware
- tenant-owned models implement required contract/trait
- shared Inertia props include required keys
- query-backed pages provide initial data
- external API routes use normalized errors and ability middleware

Keep allowlists explicit and small.

## 10. Verification

Run the smallest checks that prove the baseline:

```bash
php artisan test --compact
npm run lint
npm run build
```

If PHP files changed, run Pint.

If frontend layout changed, verify in a browser or Playwright screenshot.

## Avoid

- Copying feature-specific code from another app.
- Installing optional SaaS packages before the product needs them.
- Creating empty abstractions with no current caller.
- Shipping client-only loading screens for data Laravel can seed.
- Hardcoding URLs instead of named routes.
- Adding large global barrels or utility dumps.
- Treating frontend authorization as enforcement.
