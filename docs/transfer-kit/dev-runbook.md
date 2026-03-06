# Dev Runbook

Last updated: 2026-02-08

## Daily Commands
- Install dependencies:
  - `herd composer install`
  - `npm install`
- Local app:
  - `herd php artisan migrate:fresh --seed`
  - `npm run dev`
- Quality checks:
  - `herd php artisan test`
  - `npm run build`
  - `npm run lint`

## Pre-deploy sanity check (local)
- Fast "critical path" suite:
  - `herd php artisan test --group=critical`
- Full suite:
  - `herd php artisan test`
- Build assets:
  - `npm run build`
- One-liner:
  - `scripts/predeploy-check.sh --full`

## Temporary Herd Fallback
- If Herd wrapper is failing (`.../Herd/bin/php: File name too long` or repeated `herd.phar` warnings), run Laravel commands directly as a temporary workaround:
  - `php artisan test`
  - `php artisan migrate:fresh --seed`
  - `php artisan optimize:clear`
- Keep Herd as the preferred convention once wrapper issues are resolved.

## Targeted Lint During Feature Work
- Run lint on changed files first to keep signal high:
  - `npx eslint <file1> <file2> ...`

## Route / Cache Debug
- If routes/config look stale:
  - `herd php artisan optimize:clear`
  - `herd php artisan route:list | rg tools`

## Logging Debug
- Structured log channels:
  - `storage/logs/app.log`
  - `storage/logs/auth-billing.log`
  - `storage/logs/security-audit.log`
  - `storage/logs/error-monitoring.log`
- Quick tail:
  - `tail -f storage/logs/app.log`
  - `tail -f storage/logs/error-monitoring.log`
- Retention / rotation defaults (daily channels):
  - `app`: `LOG_DAILY_DAYS=14`
  - `auth_billing`: `LOG_AUTH_BILLING_DAYS=30`
  - `security_audit`: `LOG_SECURITY_AUDIT_DAYS=90`
  - `error_monitoring`: `LOG_ERROR_MONITORING_DAYS=30`

## Analytics (Umami)
- Frontend env vars:
  - `VITE_UMAMI_ENABLED`
  - `VITE_UMAMI_WEBSITE_ID`
  - `VITE_UMAMI_SCRIPT_URL` (defaults to `https://cloud.umami.is/script.js`)
  - `VITE_UMAMI_HOST_URL` (optional, used for proxy/custom host)
  - `VITE_UMAMI_AUTO_TRACK` (`false` for manual Inertia page tracking)
- Event schema:
  - `docs/analytics-event-taxonomy.md`
- Privacy rule:
  - Never include raw tool/snippet input/output payload bodies in events.
- Deploy note:
  - Vite env vars are build-time values; changing Umami env requires a fresh frontend build/deploy.

## Tool API Debug Checklist
1. Confirm tool slug exists and is active in `config/tools.php`.
2. Confirm tier requirement (`minimum_tier`) and current user plan.
3. Confirm request validation payload shape.
4. Confirm controller uses `denyToolAccess` and `consumeToolRunQuota`.
5. Confirm history type enum includes the tool history value.

## Auth / 419 / Sanctum Notes
- For SPA requests:
  - Ensure `X-Requested-With` and credentials usage in axios bootstrap.
  - Verify CSRF cookie and session cookie domains.
- If random 419 appears:
  - check session/cookie config
  - clear caches
  - re-authenticate

## Spark / Stripe Notes
- Product IDs (`prod_...`) are not price IDs.
- Spark plan env vars must use Stripe price IDs (`price_...`).
- Billing errors to watch:
  - `sparkConfiguration()` missing on user model trait integration.
  - plan ID mismatch between env and Stripe mode/account.

## Deploy Notes (Forge)
- Node OOM during build:
  - increase node heap for install/build step if needed.
- If Inertia page resolve errors on deploy:
  - ensure case-sensitive page path and build artifact freshness.
  - run deploy with fresh `npm ci` + `npm run build`.
- Full production deployment procedure:
  - `docs/forge-deploy-runbook.md`

## Persistent Context Docs
- Task execution status:
  - `docs/roadmap.md`
- Active handoff and next actions:
  - `docs/agent-handoff.md`
- Conventions source:
  - `docs/architecture-conventions.md`

## Update Rule
- After each completed chunk, update:
  1. `docs/agent-handoff.md`
  2. `docs/roadmap.md`
  3. this runbook if new operational gotchas appear
