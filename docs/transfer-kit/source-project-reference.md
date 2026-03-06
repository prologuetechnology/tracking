# Source Project Reference

This document explains how the source project is actually built so another agent can understand not just the rules, but the implementation shape those rules came from.

Use this together with:

- `docs/project-scaffold-playbook.md`
- `docs/architecture-conventions.md`
- `docs/react-frontend-structure-contract.md`

## Stack snapshot

Backend:

- PHP `^8.2`
- Laravel `^12`
- Inertia Laravel `^2`
- Sanctum `^4`
- Socialite `^5`
- Spark Stripe `^6`
- Scout `^10`
- Spatie Permission `^6`
- Ziggy `^2`
- Scribe for API docs

Frontend:

- React `^19`
- Inertia React `^2`
- TanStack Query `^5`
- Tailwind CSS `^4`
- shadcn-style component architecture
- Radix/Base UI primitives
- Sonner for toasts
- Day.js for dates
- jsPDF for export generation
- Vite `^7`

Quality/tooling:

- Pest
- PHPUnit
- Larastan
- Pint
- ESLint
- Prettier

## App boot flow

Backend boot:

- `bootstrap/app.php`
  - registers middleware aliases
  - prepends request context middleware
  - enables stateful API behavior for Sanctum
  - normalizes `api/v1/*` exception responses
  - reports structured exception payloads to the log channel

Frontend boot:

- `resources/js/app.js`
  - boots Inertia React
  - dynamically resolves page components
  - wraps the app in TanStack Query
  - wraps the app in `next-themes` theme provider
  - initializes analytics
  - tracks page views and client errors

## Route surfaces

Public web routes:

- `routes/web.php`
- public marketing pages, auth pages, public snippet/burner entry points, public tools, public status

Authenticated web routes:

- `routes/web.php`
- dashboard, settings, admin pages, authenticated tool pages, snippet pages

Authenticated app API:

- `routes/api.php`
- Sanctum-protected JSON endpoints for settings, snippets, tools, admin, widgets, access history

Public API:

- `routes/api.php`
- public status endpoint
- privacy receipt verification endpoints

External versioned API:

- `routes/api.php`
- `/api/v1/*`
- normalized error responses
- token ability enforcement
- intended for user-facing API access, not internal UI calls

## Backend layering pattern

The project uses a stable, server-first layering model:

- routes decide surface and middleware
- controllers stay thin
- Form Requests own validation/authorization
- actions own domain workflows
- services own reusable business/system concerns
- resources shape JSON payloads
- config files act as source-of-truth catalogs

Key directories:

- `app/Actions/*`
- `app/Services/*`
- `app/Http/Controllers/*`
- `app/Http/Requests/*`
- `app/Http/Resources/*`
- `app/Models/*`
- `app/Enums/*`
- `app/Support/*`
- `config/*`

## Frontend layering pattern

The frontend is intentionally split by responsibility:

- Inertia pages under `resources/js/Pages/*`
- layouts under `resources/js/components/layout/*`
- domain-aware UI under `resources/js/components/feature/*`
- base primitives under `resources/js/components/ui/*`
- query hooks under `resources/js/composables/queries/*`
- mutation hooks under `resources/js/composables/mutations/*`
- supporting logic under `resources/js/lib/*`

Core frontend rules:

- one concern per file
- adjacent `index.js` barrels only
- no giant global barrel exports
- use named exports by default
- keep network calls inside query/mutation hooks
- use route helpers, not hardcoded URLs
- keep initial server props as first-render source of truth

## Shared data and first render

The project is intentionally server-first on first render.

Pattern:

- web routes provide initial Inertia props
- React Query hooks receive `initialData`
- first render should not flash blank loading states where server data is available

This is a hard convention in this codebase.

## Authorization model

Three different policy layers exist and are intentionally separate:

- authentication: session auth and Sanctum token auth
- subscription/tier gating: Free, Standard, Pro
- RBAC: Spatie roles + colon-delimited permissions

Do not collapse these together.

Examples:

- billing/tier gates control product access
- permissions control admin and elevated operations
- token abilities control external API scopes

## Source-of-truth config pattern

Important config-driven domains:

- `config/tools.php`
  - tool catalog
  - domain grouping
  - minimum tier
  - route names
  - logging defaults
- `config/plan_limits.php`
  - plan limits and quotas
- `config/api_access.php`
  - API abilities, defaults, and API-access rules
- `config/seo.php`
  - SEO defaults
- `config/logging.php`
  - structured logging channels

This app prefers configuration-driven metadata over ad hoc duplication in UI and controllers.

## UI shell patterns

Repeated UI surfaces are built as shells:

- public layout
- authenticated app layout
- settings layout
- tool page shell
- history sheet/detail shell
- list/item shell
- admin split-view pattern

This keeps new features visually and structurally consistent.

## Tool implementation pattern

Tools follow a repeatable contract:

- tool metadata comes from `config/tools.php`
- page route renders an Inertia tool page
- page receives history logging visibility via server props
- page uses shared tool shell components
- API route runs the action
- telemetry/history/receipts are applied centrally through middleware/services

Important related files:

- `config/tools.php`
- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/Api/Tools/*`
- `app/Actions/Tools/*`
- `resources/js/Pages/tools/*`
- `resources/js/components/feature/tools/*`

## Snippet implementation pattern

Snippets are a good example of a larger feature with multiple surfaces:

- authenticated list/show/create/edit flows
- sharing and burner link flows
- public/shared entry points
- encryption at rest for content
- tier gates, invite flows, and history/access concerns

Important related files:

- `app/Http/Controllers/SnippetPageController.php`
- `app/Http/Controllers/Api/SnippetController.php`
- `app/Http/Controllers/Api/SnippetShareController.php`
- `app/Actions/Snippets/*`
- `app/Actions/SnippetShares/*`
- `resources/js/Pages/snippets/*`

## Admin implementation pattern

Admin is treated as a separate surface, not as a few scattered pages.

Characteristics:

- dedicated routes and permissions
- live filter/query patterns
- list/detail split views
- audit/logs/entitlements/roles/users separated by domain

Important related files:

- `routes/web.php`
- `routes/api.php`
- `app/Http/Controllers/AdminPageController.php`
- `app/Http/Controllers/Api/Admin/*`
- `resources/js/Pages/admin/*`

## Logging, privacy, and observability

This project is explicitly privacy-aware.

Rules:

- avoid logging raw sensitive content
- log metadata and operational state instead
- attach request correlation context early
- keep dedicated channels for system/error/security contexts
- expose some user-visible trust surfaces such as access history and privacy receipts

Important related files:

- `bootstrap/app.php`
- `config/logging.php`
- `app/Logging/*`
- `app/Http/Middleware/AttachRequestContext.php`
- `app/Http/Middleware/AttachPrivacyReceipt.php`
- `app/Http/Controllers/Api/PrivacyReceiptController.php`

## Build, release, and testing

Build and tooling source files:

- `composer.json`
- `package.json`

High-signal docs:

- `README.md`
- `docs/dev-runbook.md`
- `docs/release-process.md`
- `docs/production-go-live-checklist.md`
- `docs/forge-deploy-runbook.md`
- `docs/context-index.md`
- `docs/agent-handoff.md`

## How another agent should use this reference

An agent should not treat this file as a substitute for code inspection.

It should use this file to:

- understand the intended architecture quickly
- identify the key source-of-truth files
- preserve the separation between app surfaces
- avoid collapsing auth, tiering, permissions, and API scopes together
- follow the same server-first and config-first approach in the next project
