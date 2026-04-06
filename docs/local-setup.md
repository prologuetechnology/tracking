# Local Setup

This guide is the source of truth for bootstrapping the Tracking app on a fresh
macOS machine with Laravel Herd.

## What You Are Setting Up

This repo contains:

- a public branded shipment-tracking surface
- an internal admin UI for companies, themes, image assets, and RBAC
- Azure OAuth login restricted by allowed domains
- Pipeline-backed shipment search, coordinates, and document retrieval

The standard local runtime is:

- Laravel Herd for local web serving
- Herd-managed PHP 8.2+
- Herd-managed or otherwise local MySQL for the app database
- npm for Vite assets

Automated tests and Dusk use sqlite-backed environments and do not require the
same MySQL setup.

## Prerequisites

Install and confirm:

- Laravel Herd
- PHP 8.2 or newer through Herd
- Composer
- Node 20 or newer
- npm
- MySQL available locally
- Git

## 1. Clone The Repository

Clone the project into your local development workspace and open the repo root.

## 2. Create The Herd Site

Point a Herd site at this repository root.

- Recommended site name: `tracking`
- Recommended local URL: `https://tracking.test`

Keep the Herd site URL, `APP_URL`, and Azure redirect URI in sync. If you use a
different Herd domain, update `.env` and your Azure app registration to match.

## 3. Install Dependencies

Install backend dependencies with Herd's Composer wrapper:

```bash
herd composer install
```

Install frontend dependencies:

```bash
npm install
```

## 4. Create The Environment File

Copy the example file:

```bash
cp .env.example .env
```

The example file is intentionally project-specific. Review these sections
before booting the app:

- app URL and local domain
- MySQL database credentials
- Azure OAuth credentials
- database-backed allowed-domain access rules
- reserved login allowlist config values
- Pipeline API credentials
- image storage disk choice

## 5. Create The Local Database

The recommended local runtime database is MySQL.

Example:

```sql
CREATE DATABASE tracking;
```

Make sure the values in `.env` match the database you created:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=tracking`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

## 6. Choose Local Image Storage

Application config defaults image uploads to the `spaces` disk, but fresh local
machines usually should not start there.

Recommended local default:

```dotenv
IMAGE_UPLOAD_DISK=public
```

If you use `public`, create the storage symlink:

```bash
herd php artisan storage:link
```

If you want cloud-backed parity instead, keep `IMAGE_UPLOAD_DISK=spaces` and
fill in the `DIGITALOCEAN_SPACES_*` values.

## 7. Generate The App Key And Bootstrap The Database

Run:

```bash
herd php artisan key:generate
herd php artisan migrate --seed
```

The seeders create important baseline records:

- an allowed domain for `prologuetechnology.com`
- a seeded super-admin Azure identity for
  `jashley@prologuetechnology.com`
- baseline roles and colon-delimited permissions
- seeded companies including `Jayco` and `Ubique`

The seeded user is not meant for password login. Normal app login still goes
through Azure OAuth.

## 8. Start Frontend Assets

Use Vite for local frontend assets:

```bash
npm run dev
```

With Herd serving the app site, you usually do not need `php artisan serve`.

## 9. Open The App

Visit your Herd site, typically:

```text
https://tracking.test
```

Expected first checks:

- the home page loads
- `/login` shows the Azure sign-in button
- no database or asset errors appear

## Required Versus Optional Integrations

### Required For Realistic Admin Login

The normal web UI login path is Azure OAuth.

You need:

- `AZURE_CLIENT_ID`
- `AZURE_CLIENT_SECRET`
- `AZURE_REDIRECT_URI`
- `AZURE_TENANT_ID`

Recommended redirect URI:

```text
https://tracking.test/oauth/azure/callback
```

You also need the user to pass the access rules enforced after Azure returns:

- the email domain must exist in `allowed_domains`

`VALID_LOGIN_DOMAINS` and `VALID_LOGIN_USERS` still exist in config, but they
are not the active access gate in the current OAuth callback. Treat
`allowed_domains` as the real source of truth unless that code path changes.

### Required For Live Tracking

Normal local tracking lookups call the real Pipeline services.

You need:

- `PIPELINE_BASE_URL`
- `PIPELINE_API_URL`
- `PIPELINE_API_KEY`

Without those values, public tracking and related admin token validation flows
will not behave like production.

### Optional For Fresh Local Machines

- `IMAGE_UPLOAD_DISK=public`
- `DIGITALOCEAN_SPACES_*` only when you want local cloud-backed image uploads
- mail provider credentials such as `RESEND_KEY` only if you are exercising
  those flows locally

## Local Verification Checklist

After setup:

1. `herd php artisan migrate --seed` succeeds.
2. The app loads at the documented Herd domain.
3. `/login` renders the Azure login button.
4. The seeded data exists:
   - allowed domain `prologuetechnology.com`
   - seeded companies such as `Jayco` and `Ubique`
5. Image uploads work on the configured disk.
6. Tracking behavior matches your configured Pipeline environment.

## Local Auth Smoke Options

Preferred path:

- sign in through Azure with an allowed-domain user

Local-only fallback:

- visit `/testing/oauth-login` in `local` or `testing`

That route creates or reuses a local super-admin test user and signs it in. It
exists for local smoke checks and browser testing support. Treat it as a local
helper, not part of the normal app contract.

## Dusk And Test-Specific Setup

Dusk has its own environment file:

- `.env.dusk.local`

That environment:

- uses sqlite instead of MySQL
- sets a local app server URL
- clears `ASSET_URL`
- switches Pipeline calls onto fake local testing endpoints
- uses the public disk for image uploads

Use it for browser tests, not as the normal local runtime template.

## Troubleshooting

### Herd Or Local URL Problems

- Confirm Herd is pointing at the repository root.
- Confirm `.env` uses the same host as the Herd site.
- Confirm Azure redirect URIs match the same host exactly.

### MySQL Connection Errors

- Confirm the `tracking` database exists.
- Confirm Herd or your local MySQL service is running.
- Confirm `.env` credentials match the actual local MySQL user.

### OAuth Redirect Or Login Problems

- Confirm Azure credentials are filled in.
- Confirm the callback URL matches `APP_URL`.
- Confirm the returning email domain exists in `allowed_domains`.

### Image Upload Problems

- If using `public`, confirm `IMAGE_UPLOAD_DISK=public` and
  `herd php artisan storage:link` has been run.
- If using `spaces`, confirm all `DIGITALOCEAN_SPACES_*` values are present.

### Tracking Problems

- Normal local tracking depends on valid Pipeline credentials.
- Dusk's fake Pipeline only applies to `.env.dusk.local`.
- If tracking fails in the normal app, check Pipeline env values before
  debugging Vue or route behavior.

### Stale Config Or Route State

Run:

```bash
herd php artisan optimize:clear
```

Then retry the failing flow.

## Next Docs To Read

- `README.md`
- `docs/dev-runbook.md`
- `docs/context-index.md`
- `docs/release-process.md`
