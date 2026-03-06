# Incident Guardrails Runbook

Last updated: 2026-02-18

## Purpose

This runbook defines the production alert thresholds and first-response actions for launch-critical reliability areas:

- API / app 5xx spikes
- auth failure spikes
- billing webhook staleness
- queue failure/backlog growth
- auth/security email delivery degradation

## Guardrail Thresholds

Set these in production `.env` (defaults shown):

- `INCIDENT_5XX_PER_5M_WARN=20`
- `INCIDENT_AUTH_FAILURES_PER_15M_WARN=25`
- `INCIDENT_QUEUE_FAILED_JOBS_WARN=10`
- `INCIDENT_QUEUE_BACKLOG_WARN=100`
- `MAIL_FAILURES_1H_DEGRADED=2`
- `MAIL_FAILURES_1H_OUTAGE=5`
- `BILLING_WEBHOOK_STALE_AFTER_MINUTES=1440`

## Operator Surfaces

- Admin dashboard:
  - API access visibility
  - Billing webhook sync
  - Auth email delivery
  - Incident guardrails
- Logs:
  - `storage/logs/auth-billing.log`
  - `storage/logs/error-monitoring.log`
  - `storage/logs/security-audit.log`

## First Response Playbook

### 1) 5xx spike or auth failures

1. Confirm active release/version and recent deploy time.
2. Inspect `error-monitoring` and `auth-billing` logs for first failing route.
3. Validate DB + cache connectivity from production app shell.
4. If blast radius is high and unresolved in 10-15 minutes, roll back to previous known-good release.

### 2) Billing webhook degraded/outage

1. Confirm Stripe endpoint health and signing secret.
2. Check latest event timestamps and replay one event from Stripe dashboard.
3. Validate app sync state after replay.
4. Keep manual review on subscription changes until status returns operational.

### 3) Queue failed jobs/backlog threshold reached

1. Run `php artisan queue:failed` and identify dominant failure class.
2. Restart workers (`php artisan queue:restart`).
3. Fix root cause and replay safe jobs (`php artisan queue:retry all` carefully).
4. If backlog keeps rising, enable temporary maintenance/degraded mode.

### 4) Auth/security email delivery degraded/outage

1. Check `mail_delivery_failed` events in `auth-billing`.
2. Validate `MAIL_MAILER`, sender identity, and provider key.
3. Validate SPF/DKIM/DMARC for sending domain.
4. Run live checks:
   - alias verification email
   - password challenge email
   - account deletion challenge email
5. Post incident note if outage exceeds 15 minutes.

## Release Sign-off Expectation

Before closing a production release:

- Billing webhook sign-off recorded
- DNS/auth email sign-off recorded
- Incident guardrail values confirmed in admin dashboard
- Go-live checklist updated with owner + evidence

