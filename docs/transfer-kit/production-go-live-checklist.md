# Production Go-Live Checklist

Last updated: 2026-02-18

## Scope
- Target: production launch in the next 24-48 hours.
- Priority: stability, privacy, auth correctness, billing correctness.

Reference:
- Forge deployment runbook: `docs/forge-deploy-runbook.md`

## 1) Pre-Cutover (T-48h to T-4h)

### Environment and security
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` matches final production domain
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_DOMAIN` set for production domain (if needed)
- [ ] `SANCTUM_STATEFUL_DOMAINS` includes production web domain(s)
- [ ] no dev/test credentials in production `.env`
- [ ] production DB vars are set (no `127.0.0.1` fallback values)

### Auth providers
- [ ] GitHub OAuth callback URL matches production URL
- [ ] Google OAuth callback URL matches production URL
- [ ] GitLab OAuth callback URL matches production URL
- [ ] provider client IDs/secrets are production values

### Billing and subscriptions
- [ ] Stripe keys are live mode keys (`STRIPE_KEY`, `STRIPE_SECRET`)
- [ ] `STRIPE_WEBHOOK_SECRET` is production webhook secret
- [ ] Spark plan env vars are price IDs (`price_...`), not product IDs (`prod_...`)
- [ ] `config/spark.php` path and middleware are correct for production

### Mail and domain
- [ ] production sending domain configured (Resend domain verified)
- [ ] SPF/DKIM/DMARC records validated
- [ ] production `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` correct
- [ ] dev/staging fallback behavior confirmed (no production outage if Resend class/config missing)
- [ ] if `MAIL_MAILER=resend`, verify package is installed on server release (`composer show resend/resend-php`)

### Data and operations
- [ ] backups configured and recent backup exists
- [ ] queue worker running
- [ ] scheduler running (`schedule:run` cron present)
- [ ] log files writable (`app`, `auth_billing`, `security_audit`, `error_monitoring`)
- [ ] `sessions` table migration is present (`0001_01_01_000000_create_users_table`)

### Build and verification
- [ ] `php artisan test` passes on release branch
- [ ] `npm run lint` passes
- [ ] `npm run build` passes
- [ ] no unresolved P0 items in `docs/v1-launch-checklist.md`

## 2) Deploy Window (T-0)

### Deploy sequence
1. [ ] put app in maintenance mode (if required by your strategy)
2. [ ] deploy code
3. [ ] `php artisan optimize:clear`
4. [ ] `php artisan migrate --force`
5. [ ] `php artisan config:cache`
6. [ ] `php artisan route:cache`
7. [ ] `php artisan view:cache`
8. [ ] restart queue worker
9. [ ] bring app out of maintenance mode

### Immediate checks
- [ ] homepage responds 200
- [ ] login page responds 200
- [ ] authenticated dashboard renders
- [ ] no 5xx spike in logs during first 5 minutes
- [ ] active session can be created (confirms `sessions` table + driver wiring)

## 3) 30-Minute Smoke Test Script

## A) Auth and account
- [ ] register with email/password
- [ ] verify MFA/email code flow works
- [ ] login with GitHub
- [ ] link/unlink social provider from account settings
- [ ] set/change password from account settings
- [ ] add/verify email alias and resend token works

## B) Snippets
- [ ] create public snippet
- [ ] create private snippet (tier-gated behavior correct)
- [ ] create burner snippet (tier-gated behavior correct)
- [ ] open snippet details page and copy actions work
- [ ] shared snippet appears in proper list(s)
- [ ] "shared with me" loads expected records

## C) Tools
- [ ] run at least one tool in each active domain group
- [ ] output copy action works
- [ ] per-tool history sheet opens and details render
- [ ] global history page filters/pagination/actions work

## D) Billing
- [ ] open billing portal from app (`authed.billing`)
- [ ] subscription status displays correctly
- [ ] create test/live transaction path and verify webhook updates app state
- [ ] invoice history visible in billing portal
- [ ] if user has an active entitlement grant, billing page shows entitlement-managed state and billing portal button is disabled
- [ ] if user has no active entitlement grant, billing portal button is enabled and opens Stripe portal

## E) Mail
- [ ] alias verification email arrives
- [ ] password security code email arrives
- [ ] account deletion challenge email arrives
- [ ] sender identity and domain alignment look correct
- [ ] verify SPF, DKIM, and DMARC are all passing on production sending domain

## F) Privacy and deletion
- [ ] account deletion flow hard-deletes data (new account used for test)
- [ ] privacy settings toggles persist and affect behavior

## G) Client quality
- [ ] check browser console for critical errors on core routes
- [ ] mobile viewport sanity pass (iOS/Android)
- [ ] dark mode follows system preference

## 4) Post-Deploy (T+1h to T+24h)
- [ ] monitor `storage/logs/error-monitoring.log` for new critical exceptions
- [ ] monitor auth/billing logs for callback/webhook anomalies
- [ ] verify no abnormal queue failures
- [ ] confirm billing/support emails continue to send
- [ ] tune and record incident guardrail thresholds for:
  - 5xx rate
  - auth failure spikes
  - billing webhook failures
  - queue backlog/failures

## 4.5) Production Sign-off Record

Complete this block before closing launch-critical stability work.

### Billing webhook sync sign-off
- [ ] Stripe webhook endpoint receives production events
- [ ] subscription state sync verified in app after webhook delivery
- [ ] retry/replay behavior verified for one webhook event
- Owner:
- Evidence (log screenshot / event ID):
- Date:

### DNS/auth email delivery sign-off
- [ ] SPF pass
- [ ] DKIM pass
- [ ] DMARC aligned
- [ ] alias verification email delivered
- [ ] password challenge email delivered
- [ ] account deletion challenge email delivered
- [ ] click-through alias verification link auto-verifies alias (no manual token entry)
- [ ] admin dashboard shows auth email delivery status as operational
- Owner:
- Evidence (mail headers / provider dashboard):
- Date:

### Incident guardrail tuning sign-off
- [ ] threshold values documented and accepted by owner
- [ ] on-call/notification path confirmed
- [ ] quick triage runbook link shared with team
- [ ] guardrail values validated in admin dashboard (5xx/auth/queue thresholds)
- Owner:
- Evidence (links/screenshots):
- Date:

## 5) Rollback Criteria
- [ ] repeated auth failures
- [ ] repeated billing 5xx or bad subscription state transitions
- [ ] critical snippet data access issues
- [ ] email delivery outage on auth/security mails

If rollback needed:
- [ ] deploy previous known-good release
- [ ] run cache clear + restart workers
- [ ] post status update and incident notes

## Notes
- Umami can remain non-blocking for launch; validate core app without it first.
- If ad blockers suppress analytics, this should not affect functional flows.
- Keep logos/favicon/login artwork as a final polish item, not a launch blocker.
