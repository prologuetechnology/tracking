# Architecture Conventions

Last updated: 2026-03-06

## Backend

- Framework: Laravel 11.
- Auth: session auth for web, Sanctum for API.
- OAuth: Microsoft Azure via Socialite, constrained by allowed email domains.
- RBAC: Spatie Permission with colon-delimited permissions such as
  `company:show`.
- Route files define surfaces and middleware only. Page data assembly belongs in
  page controllers, API workflows belong in actions/services.
- Controllers stay thin.
- Validation belongs in `app/Http/Requests/**`.
- Reusable workflows belong in `app/Actions/**`.
- External integration logic belongs in `app/Services/**`.
- JSON payload shaping belongs in `app/Http/Resources/**`.

## Route Surfaces

- Public web:
  - `/`
  - `/login`
  - `/trackShipment`
  - `/oauth/*`
- Authenticated admin web:
  - `/admin/*`
- Authenticated JSON API:
  - `/api/*`

## First Render Contract

- Query-backed Inertia pages must receive initial props from the server when the
  initial dataset is already available.
- Vue Query composables must consume that dataset via `config.initialData`.
- Do not ship loading-only first paint for admin index and edit pages when the
  route/controller already has the data.
- Tracking and branded tracking pages must hydrate with the same normalized
  shipment/company/document/coordinate shapes that the follow-up API queries
  return.

## Frontend

- Frontend runtime: Vue 3 + Inertia + TanStack Vue Query.
- Pages live under `resources/js/pages/**`.
- Layouts live under `resources/js/components/layout/**`.
- Domain UI lives under `resources/js/components/feature/**`.
- Base shadcn-vue primitives live under `resources/js/components/ui/**`.
- Query composables live under `resources/js/composables/queries/**`.
- Mutation composables live under `resources/js/composables/mutations/**`.
- Auth helper composables live under `resources/js/composables/hooks/auth/**`.
- Use Ziggy `route(...)` instead of hardcoded internal URLs.
- Use local adjacent barrel files only.

## UI Rules

- shadcn-vue primitives are the first choice for UI building blocks.
- Do not edit `resources/js/components/ui/**` during feature work unless adding
  a missing installed primitive is required.
- Keep one concern per file.
- Prefer named exports in barrels and explicit imports in domain code.

## Privacy And Logging

- Do not log raw tracking payloads, document URLs, OAuth tokens, or request
  headers with secrets.
- Log IDs, route names, company IDs, user IDs, and status metadata instead.
- External service failures should be summarized without dumping the full remote
  payload unless explicitly redacted first.

## Testing And Validation

- Backend verification: `php artisan test`
- Browser verification: `php artisan dusk`
- Frontend production verification: `npm run build`
- Static frontend verification: `npm run lint`
- Formatting verification: `npm run format:check`
