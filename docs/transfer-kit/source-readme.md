# Cereal Eyes Bravo

Developer toolbox built with **Laravel 12 + Inertia.js + React**. Includes snippet sharing, safe PHP unserializer, Base64 and JSON↔XML converters, and a date conversion tool (timestamp ↔ datetime). Everything uses UUID primary/foreign keys (no integers), Sanctum for auth, and Spatie Permissions.

## Stack
- Laravel 12, MySQL, Sanctum (stateful SPA + token), Spatie Permission (UUID models), Pest tests, Pint strict, Larastan.
- Inertia.js (React), Tailwind CSS v4 + ShadCN components, TanStack React Query + Zod, Ziggy route helper, Vite + React plugin.

## Features
- **Snippet sharing**: create/list/delete, copy content or share UUID URL, optional expiration, public/private flag, policies for `snippets.view-own | snippets.view-all | snippets.manage`.
- **PHP unserializer**: safe `unserialize` with `allowed_classes=false`, friendly error messages.
- **Base64 encode/decode**: toggle modes, inline validation.
- **JSON↔XML**: validate JSON/XML and convert both directions.
- **Date conversion**: convert Unix timestamps and datetimes both ways in UTC or local timezone.
- **Subscription foundation**: Free/Standard/Pro plan config, effective tier resolver, billing page (`/billing`), and per-user override grants for lifetime/giveaway access.

## Architecture & Conventions
- **UUIDs everywhere**: migrations, factories, seeders, pivot FKs. Trait `App\Models\Concerns\HasUuidPrimary` applied to User, Permission/Role, Snippet, PersonalAccessToken. Migrations use `uuid()`/`uuidMorphs()`.
- **Actions first**: controllers delegate to `app/Actions/**` (e.g., `StoreSnippet`, `JsonToXml`, `PhpUnserialize`).
- **Domain-first tools backend**:
  - actions: `app/Actions/Tools/{DataTransform,Inspect,History}`
  - requests: `app/Http/Requests/Tools/{DataTransform,Inspect,History}`
  - controllers: `app/Http/Controllers/Api/Tools/{DataTransformController,InspectController,HistoryController}`
- **HTTP layout**: Inertia pages via `routes/web.php`; JSON API under `routes/api.php` (`Api\SnippetController`, domain tool controllers above).
- **Route naming contract**:
  - canonical: `api.auth.tools.history.*`, `api.auth.tools.inspect.*`, `api.auth.tools.data-transform.*`
  - legacy aliases are still present during migration (`api.auth.tool-history.*`, `api.auth.tools.*`)
- **Permissions**: custom UUID Role/Permission models configured in `config/permission.php`; seed roles (`admin`, `user`) and permissions (`snippets.view-own`, `snippets.view-all`, `snippets.manage`).
- **Frontend structure**:
  - `resources/js/components/ui`, `components/layout`, `components/feature`
  - composables are domain/model scoped with local barrels only:
    - queries: `resources/js/composables/queries/<domain-or-model>/index.js`
    - mutations: `resources/js/composables/mutations/<domain-or-model>/index.js`
  - avoid root composables barrels; import by scoped path (`@/composables/queries/snippets`, `@/composables/mutations/tools/history`)
  - pages under `resources/js/pages` / `resources/js/Pages`
- **Tool taxonomy source of truth**: `config/tools.php` is shared via Inertia (`HandleInertiaRequests`) and drives grouped UI navigation.
- **Styling**: Tailwind v4 (`@import "tailwindcss"; @source "../js/**/*.{js,jsx}"`) plus lightweight ShadCN-like components (Button, Input, Card, etc.).

## Project Layout (high level)
- `app/Models` — UUID models (User, Snippet, Permission, Role, PersonalAccessToken)
- `app/Services/Subscriptions` — effective tier resolution and future Cashier/Spark mapping
- `app/Actions` — Snippets + tool converters
- `app/Http/Controllers/Api` — JSON endpoints; `SnippetPageController` serves Inertia pages
- `resources/js` — Vue entry `app.js`, layouts/components, feature pages in `pages/**`
- `database/migrations` — UUID migrations incl. Sanctum & Spatie tables; `2026_02_03_023500_create_snippets_table`
- `database/seeders/DatabaseSeeder.php` — admin/demo users, roles/permissions, sample snippet
- `tests` — Pest config + feature tests for snippets

## Local Setup (macOS / Laravel Herd)
1. **Prereqs**: Composer, Node 20+, npm, MySQL, and **Laravel Herd**. We use Herd for local development and expect contributors to use it (it's the documented path).
   - Point a Herd site to this repo (e.g., `cereal-eyes-bravo.test`).
2. **Install**  
   ```bash
   herd composer install
   npm install
   cp .env.example .env
   herd php artisan key:generate
   herd php artisan migrate --seed
   npm run dev   # or npm run build for prod assets
   ```
3. **Env**: update `.env` for MySQL (DB_DATABASE, DB_USERNAME, DB_PASSWORD), `SANCTUM_STATEFUL_DOMAINS=cereal-eyes-bravo.test,localhost:3000`, `APP_URL=https://cereal-eyes-bravo.test`.
   - Subscription env: set `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, and Stripe price IDs for standard/pro plans.
4. **Auth seeds**:  
   - Admin: `admin@example.com` / `password` (role: admin, all permissions)  
   - Demo: `demo@example.com` / `password` (role: user, `snippets.view-own`)
5. **Run tests/lint**: `herd php artisan test`, `herd composer pint`, `npm run lint`.

## Docs

- Start here: `docs/context-index.md`
- User docs (training): `docs/user/`
- Dev runbook (commands + gotchas): `docs/dev-runbook.md`
- Release process: `docs/release-process.md`

## Deployment (Laravel Forge on DigitalOcean)
1. **Server**: create DO droplet, provision via Forge with PHP 8.2+, MySQL. Enable queue worker if background jobs added later.
2. **Clone & Build** (Forge deploy script example):  
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan config:cache && php artisan route:cache
   php artisan migrate --force --seed
   npm ci
   npm run build
   php artisan storage:link
   ```
3. **Env on Forge**: set `APP_URL`, DB creds, `SESSION_DRIVER=database`, `SANCTUM_STATEFUL_DOMAINS` (prod domain), `QUEUE_CONNECTION=database`.
4. **Web/SPA**: keep Nginx serving `/public`; enable HTTPS. Sanctum requires correct cookie domain + HTTPS in production.
5. **Cron/Queue**: add `* * * * * php /home/forge/site.com/artisan schedule:run >> /dev/null 2>&1`; configure a queue worker if you add jobs later.

## Commands
- Dev server: `npm run dev`
- Lint JS/Vue (repo-wide): `npm run lint`
- Lint JS/Vue and auto-fix: `npm run lint:fix`
- Format JS: `npm run format`
- Pint: `composer pint`
- Tests: `php artisan test`
- Static analysis: `vendor/bin/phpstan analyse`
- Grant giveaway/lifetime access:
  - `php artisan subscriptions:override user@example.com pro --lifetime --source=giveaway`
  - `php artisan subscriptions:override user@example.com standard --days=30 --source=support`
  - `php artisan subscriptions:override user@example.com --remove`

## Notes
- Tailwind v4 uses config-less `@theme`/`@source`; styles live in `resources/css/app.css`.
- Ziggy routes generated to `resources/js/ziggy.js` (`php artisan ziggy:generate`).
- In testing env, the Inertia root skips Vite asset loading to keep tests fast.
- VS Code on-save linting/formatting is configured in `.vscode/settings.json` (ESLint + Prettier).
