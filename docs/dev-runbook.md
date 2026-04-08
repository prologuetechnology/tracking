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

## Related Docs

- Fresh-machine setup:
  - `docs/local-setup.md`
- Onboarding overview:
  - `README.md`
- Release expectations:
  - `docs/release-process.md`
- Repo source-of-truth index:
  - `docs/context-index.md`
