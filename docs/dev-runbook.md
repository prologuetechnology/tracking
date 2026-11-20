# Dev Runbook

This runbook is the day-to-day operational companion to
`docs/local-setup.md`. Use the local setup guide for fresh-machine bootstrap.
Use this file for regular development, verification, and debugging.

## Daily Commands

- Install or refresh backend dependencies:
  - `herd composer install`
- Install or refresh frontend dependencies:
  - `npm install`
- Run migrations and seeders:
  - `herd php artisan migrate --seed`
- Start frontend assets:
  - `npm run dev`
- Run the PHP test suite:
  - `herd php artisan test`
- Run Dusk browser coverage:
  - `herd php artisan dusk --without-tty`
- Build production assets:
  - `npm run build`
- Lint frontend code:
  - `npm run lint`
- Check PHP formatting:
  - `herd php vendor/bin/pint --test`

## Local Workflow Notes

- Herd is the source of truth for local web serving. Use the Herd site for the
  app and run `npm run dev` separately for Vite.
- This app requires PHP 8.4. Confirm `herd php -r 'echo PHP_VERSION, PHP_EOL;'`
  reports 8.4 before running Composer, tests, or Dusk.
- `composer dev` still exists, but it uses `php artisan serve`. Prefer Herd for
  normal local work unless you intentionally want the combined artisan stack.
- Standard local runtime uses MySQL. Automated tests and Dusk use sqlite-backed
  environments.

## Auth And Access Notes

- Normal web login is Azure OAuth only.
- Allowed-domain rules still apply after Azure authentication.
- Local and testing environments expose `/testing/oauth-login` as a fallback
  super-admin login helper for smoke checks. Do not rely on that route outside
  local or testing workflows.

## Tracking And Image Notes

- Live tracking in the normal local app depends on valid Pipeline credentials.
- `.env.dusk.local` swaps Dusk onto fake local Pipeline endpoints and sqlite.
- Image uploads default to the `spaces` disk in app config. Fresh local setups
  should usually override `IMAGE_UPLOAD_DISK=public` in `.env` until Spaces
  credentials are available.

## Pre-Deploy Local Check

- `herd php artisan test`
- `npm run lint`
- `npm run build`
- `herd php artisan dusk --without-tty`

## Cache And Route Debug

- Clear stale caches:
  - `herd php artisan optimize:clear`
- Inspect routes:
  - `herd php artisan route:list`

## Common Troubleshooting

- If the app URL, cookies, or OAuth redirect feel wrong, confirm `.env`,
  Herd's local domain, and Azure redirect URIs all point at the same host.
- If image uploads fail locally, confirm `IMAGE_UPLOAD_DISK` matches the disk
  you actually configured.
- If tracking lookups fail in the normal local app, confirm the Pipeline env
  values before debugging frontend behavior.
- If Dusk fails, compare `.env.dusk.local` with `tests/DuskTestCase.php`.

## Tracking proof exports

From **Company → Design drafts**, save a revision and open its preview. Load a
shipment and choose **Export desktop and mobile PNGs**. One new lookup freezes
the shipment for both views. Reopen the revision to download either original
PNG. Each file name identifies its revision, proof and viewport.

Staff send the downloaded files through the existing customer approval channel.
**Record customer approval** identifies the person who approved that pair.
**Publish approved revision** applies that revision to the company's existing
links. Public visitors continue to see current shipment data. The first
publication saves the prior Classic branding as a revision. **Publication
history** on the company's drafts page can restore it or an earlier publication.

### Local and worker setup

- Run `php artisan migrate`, `npm run build` and `npm ci --prefix tools/proof-renderer`.
  The proof browser uses the compiled Vue assets even while the editor uses Vite HMR.
- Install Google Chrome on the worker host. The default is the installed Chrome
  stable channel. Set `TRACKING_PROOF_CHROME` to an absolute executable path for
  another Chromium installation and `TRACKING_PROOF_NODE` when Node is outside
  the worker's PATH. Use an unprivileged account with the Chromium sandbox available.
- A local `sync` queue renders during the export request. For deployment use an
  asynchronous worker with a retry interval of at least 150 seconds (for the
  database driver, `DB_QUEUE_RETRY_AFTER=150`). Run `php artisan queue:work --timeout=110`
  and restart workers on release. Use the same application build on web and worker hosts.
- Mount and back up `storage/app/private/design-proofs` across workers and web
  processes. Also back up the database and retain its Laravel encryption key.
  Archives contain shipment information. Keep the directory outside the public
  document root. Both images are verified by SHA-256 on download.
- `TRACKING_PROOF_TIMEZONE` defaults to `America/Chicago` for both views.
  Proofs use a 1440 × 900 desktop viewport and a 390 × 844 mobile viewport
  at 1× pixel density. PNG height expands to include the whole tracking page.
- Use **Retry this snapshot** on a failed export or one pending for ten minutes.
  It reuses the original shipment data. A new export fetches a fresh shipment.
  If exports fail, check the browser executable, compiled assets, private volume,
  and access to the assigned branding files.

## Related Docs

- Fresh-machine setup:
  - `docs/local-setup.md`
- Onboarding overview:
  - `README.md`
- Release expectations:
  - `docs/release-process.md`
- Repo source-of-truth index:
  - `docs/context-index.md`
