# Tracking

Tracking is a Laravel 11 + Inertia Vue 3 application for branded shipment
tracking and internal administration.

The product scope in this repository is:

- branded shipment lookup for customer-facing tracking pages
- company administration for branding, features, and API token setup
- theme management
- allowed-domain gated Azure OAuth access
- user, role, and permission administration

This repository uses the transfer-kit docs under `docs/transfer-kit/` as a
pattern reference. Local project conventions live in the repo-level docs listed
in `docs/context-index.md`.

## Stack

- PHP 8.2
- Laravel 11
- Inertia Laravel
- Vue 3
- TanStack Vue Query
- Ziggy
- Sanctum
- Socialite with Microsoft Azure provider
- Spatie Laravel Permission
- Tailwind CSS + shadcn-vue component structure

## Local Setup

1. Install PHP dependencies with `composer install`.
2. Install frontend dependencies with `npm install`.
3. Copy `.env.example` to `.env`.
4. Configure database, Azure OAuth, DigitalOcean Spaces, and Pipeline service
   credentials in `.env`.
5. Generate an app key with `php artisan key:generate`.
6. Run migrations with `php artisan migrate`.
7. Seed baseline data with `php artisan db:seed`.

## Daily Commands

- `composer dev`
- `php artisan test`
- `npm run build`
- `npm run lint`
- `npm run format:check`

## Route Surfaces

- Web:
  - `/login`
  - `/admin/*`
  - `/trackShipment`
  - `/oauth/*`
- API:
  - `/api/shipmentTracking`
  - `/api/shipmentDocuments`
  - `/api/shipmentCoordinates`
  - `/api/companies/*`
  - `/api/themes/*`
  - `/api/admin/*`

## Auth And Authorization

- Authentication is session-based for the web UI and Sanctum-based for API
  routes.
- Azure OAuth is restricted by entries in `allowed_domains`.
- RBAC uses Spatie roles and colon-delimited permissions.
- Admin-only pages are permission-gated or protected by `EnsureSuperAdmin`.

## Architecture Notes

- Keep controllers thin and move workflows into actions or services.
- Keep validation in `FormRequest` classes.
- Keep initial Inertia props as the first-render source of truth for query-backed
  pages.
- Keep Vue Query network access inside domain-scoped query and mutation
  composables.
- Do not hardcode URLs in frontend code; use Ziggy `route(...)`.
- Do not log raw sensitive payload bodies, tokens, or full external API payloads.

## Docs

Start here:

- `AGENTS.md`
- `docs/context-index.md`
- `docs/architecture-conventions.md`
- `docs/project-scaffold-playbook.md`
- `docs/vue-frontend-structure-contract.md`
- `docs/agent-handoff.md`

Transfer-kit reference docs remain under `docs/transfer-kit/`.
