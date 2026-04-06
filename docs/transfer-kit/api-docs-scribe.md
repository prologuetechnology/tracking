# API Docs (Scribe)

Last updated: 2026-02-13

## What We Use
- Generator: `knuckleswtf/scribe`
- Docs type: `laravel` (served from app routes, not a separate static site)
- Primary config: `config/scribe.php`

## Local Usage
1. Generate docs:
   - `composer docs:api`
   - or `php artisan scribe:generate`
2. Open docs in browser:
   - `http://<app-url>/docs`
3. Open generated artifacts:
   - Postman collection: `/docs.postman`
   - OpenAPI spec: `/docs.openapi`

## Auth Model in Docs
- Docs are configured for Bearer token auth.
- Token source for users: `Settings -> API Access`.
- Optional local auth value for "Try It Out" calls:
  - `.env`: `SCRIBE_AUTH_KEY=your_personal_access_token`

## Notes
- Docs are intentionally scoped to the public integration surface (Pro API access), not internal/admin APIs.
- Response calls are disabled in Scribe config to keep generation stable when local DB/services are unavailable.
- Scribe will still show request/response structure from validation rules, resources, and route metadata.
- To improve generated request examples over time, add explicit Scribe annotations to high-value endpoints first.
