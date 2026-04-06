# Forge Deploy Runbook (Production)

Last updated: 2026-02-10

## Scope
- Target platform: Laravel Forge on DigitalOcean.
- App: `cereal-eyes-bravo`.
- Goal: repeatable, low-risk deploy with fast rollback.

## 1) Production Environment Checklist

Set these in Forge site environment before first production deploy:

### Core app
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://<your-production-domain>`
- `APP_KEY=<generated>`
- `LOG_CHANNEL=stack`
- `LOG_STACK=app,error_monitoring`

### Sessions / Sanctum
- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_DOMAIN=<your-production-domain or parent domain>`
- `SANCTUM_STATEFUL_DOMAINS=<your-production-domain>`

Important:
- This app expects a real `sessions` table when `SESSION_DRIVER=database`.
- `sessions` is created in `0001_01_01_000000_create_users_table.php`; make sure migrations run successfully on every deploy.

### Database / cache / queue
- `DB_CONNECTION=mysql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_STORE=redis` (recommended)
- `QUEUE_CONNECTION=database` (or redis if you decide to move)

### Mail / Resend
- `MAIL_MAILER=resend` (prod)
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `RESEND_API_KEY=<prod key>`
- `MAIL_RESEND_REQUIRED_CLASS=Resend`

Note:
- App has fallback to `mail.default=log` if resend class is missing. That prevents a fatal but should be treated as misconfiguration in production.

### Billing / Spark / Stripe
- `STRIPE_KEY=<live publishable>`
- `STRIPE_SECRET=<live secret>`
- `STRIPE_WEBHOOK_SECRET=<live webhook secret>`
- `SPARK_STANDARD_MONTHLY_PLAN=<price_...>`
- `SPARK_STANDARD_YEARLY_PLAN=<price_...>`
- `SPARK_PRO_MONTHLY_PLAN=<price_...>`
- `SPARK_PRO_YEARLY_PLAN=<price_...>`

### OAuth
- `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URL`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URL`
- `GITLAB_CLIENT_ID`, `GITLAB_CLIENT_SECRET`, `GITLAB_REDIRECT_URL`

## 2) Forge Deploy Script (Recommended)

Use this as your Forge site deploy script:

```bash
cd "$FORGE_SITE_PATH"

echo "Deploying release: $(date)"

# PHP deps
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Frontend deps + build (with safer memory ceiling)
export NODE_OPTIONS=--max-old-space-size=1024
npm ci --no-audit --no-fund
npm run build

# Laravel optimize/migrate
php artisan down --render="errors::503" || true
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure public storage link exists
php artisan storage:link || true

# Bring app up and restart workers
php artisan up
php artisan queue:restart

echo "Deploy completed: $(date)"
```

Notes:
- If you use Forge’s atomic release directories, keep this script in the release context Forge provides.
- If builds still OOM, increase memory (`1536` or `2048`) or shift frontend build to CI and deploy artifacts.

## 3) Forge Daemons + Scheduler

### Queue worker (Daemon)
Command:
```bash
php /home/forge/<site>/artisan queue:work --sleep=1 --tries=3 --timeout=120 --max-time=3600
```

### Scheduler (Forge Scheduler)
Command:
```bash
php /home/forge/<site>/artisan schedule:run
```
Frequency: every minute.

## 4) Webhooks and Integrations

### Stripe webhook
- Endpoint: `https://<your-production-domain>/spark/webhook`
- Use live secret in `STRIPE_WEBHOOK_SECRET`.
- Verify events are arriving and no signature mismatch errors in logs.

### OAuth callbacks
Configure provider callbacks exactly:
- GitHub: `https://<domain>/auth/github/callback`
- Google: `https://<domain>/auth/google/callback`
- GitLab: `https://<domain>/auth/gitlab/callback`

### Resend
- Verify sending domain DNS (SPF/DKIM).
- Send a test alias verification email and password security code after deploy.
- Confirm alias verification email deep-link auto-verifies the alias when opened by an authenticated user.

## 5) Post-Deploy Smoke (10-15 min)

- Guest:
  - load login page
  - start social login redirect
- Auth:
  - login (email/password and one social provider)
  - dashboard renders
- Snippets:
  - create public snippet
  - open snippet details
  - copy link/content works
- Tools:
  - run one tool and verify output + copy
  - open tool history page and verify pagination/filter request works
- Billing:
  - open billing route from app (`authed.billing`)
  - ensure Spark portal loads
- Mail:
  - resend alias token works (no 422 for verified alias resend path)
  - alias verification deep-link verifies automatically and redirects to Email Aliases settings
  - admin dashboard shows recent auth email delivery counters

## 6) Quick Troubleshooting

### Billing route loops or fails
- Check `config/spark.php` path + middleware.
- Confirm Stripe live env values and Spark plan price IDs.
- Check `storage/logs/auth-billing.log`.

### Mail errors (`Class "Resend" not found`)
- Confirm dependency exists on server:
  ```bash
  composer show resend/resend-php
  ```
- Confirm `MAIL_MAILER=resend`.
- If fallback triggers, you’ll see `resend_transport_class_missing_fallback` in `auth-billing` log.

### Mail delivery health is degraded/outage on admin dashboard
- Check `auth-billing` logs for:
  - `mail_delivery_failed`
  - `mail_delivery_succeeded`
- Validate:
  - `MAIL_MAILER` is set to `resend`
  - `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` are set
  - Resend DNS auth (SPF/DKIM/DMARC) remains valid

### `sessions` table missing
- Symptom:
  - `Base table or view not found: ... sessions`
- Fix:
  1. Confirm `SESSION_DRIVER=database` in Forge env.
  2. Run `php artisan migrate --force`.
  3. Confirm migration status includes `0001_01_01_000000_create_users_table`.

### Database connects to `127.0.0.1:3306` on production
- Symptom:
  - `SQLSTATE[HY000] [2002] Connection refused ... Host: 127.0.0.1`
- Root cause:
  - Production DB env vars were missing/incorrect when artisan commands ran, so Laravel fell back to local defaults.
- Fix:
  1. Re-check Forge site env values (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
  2. Re-deploy so release has correct `.env`.
  3. Run:
     - `php artisan optimize:clear`
     - `php artisan config:cache`
     - `php artisan migrate --force`

### Frontend build errors / OOM
- Increase `NODE_OPTIONS` memory.
- Clear node modules and reinstall in release if needed.
- Consider CI-built assets for deterministic deploys.

### Inertia page resolution errors
- Ensure filename case matches import usage.
- Rebuild assets and clear caches:
  - `php artisan optimize:clear`
  - `npm run build`

## 7) Rollback Plan

If critical regression appears:
1. Roll back to previous Forge release.
2. Run:
   - `php artisan optimize:clear`
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
   - `php artisan queue:restart`
3. Re-run smoke checks (auth, snippets, tools, billing, mail).

## 8) Optional: CI-Assisted Deploy (Recommended Next)

For faster and safer deploys:
- run lint/tests/build in CI,
- upload built assets/artifacts,
- deploy with minimal build work on Forge.
