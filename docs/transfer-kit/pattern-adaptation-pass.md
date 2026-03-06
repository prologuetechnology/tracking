# Pattern Adaptation Pass (Copy-to-Existing-Project)

Use this checklist to adapt the Cereal Eyes patterns to another already built repo.

Before filling this out, read:

- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/source-project-reference.md`
- the target repo's own `README.md`
- the target repo's own `docs/context-index.md` if it exists

## 1) Baseline mapping

### 1.1 Runtime stack

| Area | Target project current state | Source pattern | Adopt / Adapt / Skip | Notes |
| --- | --- | --- | --- | --- |
| Backend framework | Laravel 11 | Laravel | Adopt | Same framework family, older minor version than source. |
| PHP version | PHP 8.2 | Laravel-compatible | Adopt | Compatible with the transfer pattern. |
| Frontend framework | Vue 3 + Inertia | React + Inertia | Adapt | Preserve Inertia architecture and hydration rules, translate components/hooks to Vue SFCs and composables. |
| Component kit | shadcn-vue structure in `resources/js/components/ui` | shadcn UI | Adopt | Same architectural intent with Vue primitives. |
| Data fetching | TanStack Vue Query | TanStack Query | Adapt | Keep query/mutation split and initial-data hydration. |
| Queue/Cache | Default Laravel config, no local queue discipline docs yet | Redis/DB queue | Skip | No project-specific queue/cache pattern is defined locally yet. |
| Logging | Laravel logging + custom database channel | Structured channels + JSON | Adapt | Keep privacy-first logging rules locally; skip source-specific channel architecture until grounded here. |
| Billing | No billing or tiering present in local product | Stripe/Spark | Skip | Do not force billing patterns into this repo. |
| CI/test runner | Pest, PHPUnit, Dusk, ESLint, Prettier, Vite build | Pest + JS lint/test | Adopt | Local docs should explicitly name the real commands. |

### 1.2 App surface map

- Public routes and middleware:
  - `/`, `/login`, `/trackShipment`, `/oauth/*`
  - `web` middleware stack plus Inertia shared props
- Authenticated web routes and middleware:
  - `/admin/*`
  - `auth` middleware, plus permission checks and `EnsureSuperAdmin` for the most sensitive admin surfaces
- JSON API and guards:
  - `/api/*`
  - `auth:sanctum`
- Admin routes and authorization model:
  - `auth` + Spatie permission checks + `EnsureSuperAdmin`
- Public docs/API routes:
  - none beyond Laravel health endpoint; no public product docs surface exists locally

### 1.3 Boot and provider map

- Backend bootstrap file:
  - `bootstrap/app.php`
- Frontend bootstrap file:
  - `resources/js/app.js`
- Inertia app entry and page resolution:
  - `createInertiaApp(...)` with `resolvePageComponent('./pages/${name}.vue', ...)`
- Theme/provider initialization:
  - Vue Query plugin and Ziggy only
- Analytics/error tracking boot:
  - none present locally

### 1.4 Authorization model

- RBAC library:
  - Spatie Laravel Permission
- Role naming strategy:
  - seeded names such as `Super Admin`, `Company Admin`, `Standard`
- Permission naming strategy:
  - colon-delimited domain permissions such as `company:show`
- Tier/billing policy separation:
  - not applicable in this project today
- Cross-cutting admin permissions:
  - role- and permission-based checks, with `EnsureSuperAdmin` protecting the highest-risk admin routes

## 2) Contract mapping

### 2.1 Backend contract

- shared props in Inertia:
  - `app/Http/Middleware/HandleInertiaRequests.php`
- Form request + action boundaries:
  - partially present; needs completion and enforcement in local refactor
- resource serialization pattern:
  - missing as a first-class pattern; add local `Http/Resources`
- service/action naming:
  - `app/Actions/*` and `app/Services/Pipeline/*`
- route naming standard:
  - route names already exist and should be preserved in the first pass
- config source-of-truth files:
  - `config/auth.php`, `config/sanctum.php`, `config/permission.php`, `config/services.php`, `config/logging.php`
- middleware alias strategy:
  - aliases live in `bootstrap/app.php`

### 2.2 Frontend contract

- route helpers:
  - Ziggy `route(...)`
- query key strategy:
  - domain-scoped keys such as `['companies']`, `['themes']`, `['users']`
- mutation/error handling:
  - local composables exist; error handling is inconsistent and should be normalized incrementally
- initial data propagation:
  - already present on several admin and tracking pages; adopt as a required rule
- list/detail UI shell:
  - authenticated layout exists; admin surface can keep current shells while page-controller layering improves
- component/ui folder strategy:
  - already matches the intended split between `layout`, `feature`, and `ui`
- local barrel export strategy:
  - already used in several composable folders and should remain adjacent-only

## 3) Privacy and observability

- Sensitive payload logging policy:
  - local policy should be explicit: never log raw OAuth tokens, tracking payloads, or document payload bodies
- request correlation IDs:
  - not implemented locally
- security event coverage:
  - limited today
- redaction standard:
  - not formalized yet
- retention policy:
  - not formalized yet
- user-visible trust features:
  - none beyond branded tracking and admin management surfaces

## 4) Build and release mapping

- PHP/composer workflow:
  - `composer install`, `composer dev`, `php artisan test`
- JS package manager + build workflow:
  - `npm install`, `npm run build`
- lint/format commands:
  - ESLint and Prettier are configured; scripts must be normalized in `package.json`
- test commands:
  - `php artisan test`
- deploy/runbook equivalents:
  - local `docs/dev-runbook.md` and `docs/release-process.md`
- changelog/release tagging process:
  - add `CHANGELOG.md`; no existing release tagging process documented locally

## 5) Pattern-by-pattern migration decisions

For each doc/pattern, answer Adopt / Adapt / Skip.

- Inertia + layout contract: Adopt
- public vs authenticated surface split: Adopt
- Tooling/action/service layering: Adapt
- Query/mutation split: Adopt
- Initial data hydration: Adopt
- Tool shell standard: Skip
- Settings shell and sections: Skip
- Admin split layout: Adapt
- Audit/logs/surface patterns: Skip
- Public markdown/doc SEO docs approach: Skip
- Post-deploy runbook style: Adapt
- Roadmap and changelog style: Adopt

## 6) Documentation handoff update

- `docs/context-index` points at correct local files
- `docs/agent-handoff` updated with active priorities
- `CHANGELOG.md` reflects start state and migration decisions
- A new `docs/transfer-kit` section exists for this adaptation

## 7) Implementation sequence

1. Stabilize auth/route map first.
2. Stabilize boot and provider initialization next.
3. Then move contract and data-layer patterns.
4. Then apply frontend structural patterns.
5. Then apply observability and privacy rules.
6. Finish with release/docs/runbook updates.

## 8) Sign-off

- [x] No route names copied blindly without adaptation.
- [x] Boot and provider assumptions reviewed against target stack.
- [x] Permissions and admin scope reviewed against target model.
- [x] Initial render strategy approved for key pages.
- [x] No cross-team sensitive data logging.
- [x] Transfer kit committed and visible in project context index.
