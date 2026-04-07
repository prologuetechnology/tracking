# Tracking

Tracking is a Laravel 13 + Inertia Vue 3 application for branded shipment
tracking and internal administration. It serves two main audiences:

- customers who need a branded public shipment-tracking experience
- internal operators who manage companies, themes, domains, images, roles, and
  permissions

This repository uses the transfer-kit docs under `docs/transfer-kit/` as a
pattern reference. The project-specific source of truth for this app starts in
`docs/context-index.md`.

## Core Surfaces

- Public branded tracking via `/trackShipment`
- Admin dashboard and management screens under `/admin/*`
- Azure OAuth login under `/login` and `/oauth/*`
- Company administration for branding, images, features, and Pipeline API
  tokens
- Theme, allowed-domain, user, role, and permission administration

## Stack

- PHP 8.4
- Laravel 13
- Inertia Laravel
- Vue 3
- TanStack Vue Query
- Ziggy
- Sanctum
- Socialite with Microsoft Azure provider
- Spatie Laravel Permission
- Tailwind CSS with shadcn-vue-style component structure
- Laravel Dusk for browser coverage

## Herd-First Quick Start

We use Laravel Herd as the standard local workflow on macOS. The recommended
local app URL for this repo is `https://tracking.test`.

1. Create or link a Herd site that points at this repository root.
   Make sure Herd is using PHP 8.4 for this project.
2. Install backend dependencies:

   ```bash
   herd composer install
   ```

3. Install frontend dependencies:

   ```bash
   npm install
   ```

4. Copy the environment file and review the project-specific settings:

   ```bash
   cp .env.example .env
   ```

5. Create the local MySQL database that matches `.env`:

   ```sql
   CREATE DATABASE tracking;
   ```

6. Generate the app key and bootstrap the database:

   ```bash
   herd php artisan key:generate
   herd php artisan migrate --seed
   ```

7. If you are using local image storage instead of DigitalOcean Spaces, create
   the public storage symlink once:

   ```bash
   herd php artisan storage:link
   ```

8. Start the frontend asset watcher:

   ```bash
   npm run dev
   ```

9. Open the Herd site in your browser.

The detailed fresh-machine version of this process lives in
`docs/local-setup.md`.

## Project-Specific Local Realities

- Normal web login is Azure OAuth only. There is no seeded email/password login
  flow for real local app usage.
- Access is further restricted by allowed domains stored in `allowed_domains`.
  The seeders add `prologuetechnology.com` as the baseline allowed domain.
- `UserSeeder` creates a seeded super-admin user for
  `jashley@prologuetechnology.com`, but that identity is still intended to log
  in through Azure.
- Branded tracking relies on live Pipeline credentials in the normal local app
  environment.
- Image uploads default to the `spaces` disk in application config. Fresh local
  machines should usually set `IMAGE_UPLOAD_DISK=public` until Spaces
  credentials are available.
- Dusk uses a separate `.env.dusk.local` file with sqlite and a fake local
  Pipeline endpoint. That setup is for browser tests, not the normal local app.

## Seeded Data You Should Know About

- Allowed domain:
  - `prologuetechnology.com`
- Seeded super-admin Azure identity:
  - `jashley@prologuetechnology.com`
- Seeded companies:
  - `Jayco`
  - `Ubique` with required brand `ubique`
  - `4 State Trucks`
  - `Loftwall`

These records are useful for local smoke checks, but live tracking results
still depend on Pipeline responses for the tracking numbers you test.

## Required And Optional Integrations

### Required For Realistic Admin Login

- Azure OAuth app registration values:
  - `AZURE_CLIENT_ID`
  - `AZURE_CLIENT_SECRET`
  - `AZURE_REDIRECT_URI`
  - `AZURE_TENANT_ID`
- A matching active record in the `allowed_domains` table
- Optional `VALID_LOGIN_DOMAINS` and `VALID_LOGIN_USERS` config values only if
  you are extending the current auth flow to use them later

### Required For Live Tracking

- `PIPELINE_BASE_URL`
- `PIPELINE_API_URL`
- `PIPELINE_API_KEY`

### Optional For Fresh Local Machines

- `IMAGE_UPLOAD_DISK=public` for local file uploads without Spaces
- `DIGITALOCEAN_SPACES_*` only if you want cloud-backed image uploads locally

## Daily Commands

Use Herd for PHP and Composer commands:

- Install or refresh backend dependencies:

  ```bash
  herd composer install
  ```

- Run migrations and seeds:

  ```bash
  herd php artisan migrate --seed
  ```

- Run the test suite:

  ```bash
  herd php artisan test
  ```

- Run browser tests:

  ```bash
  herd php artisan dusk --without-tty
  ```

- Start frontend assets:

  ```bash
  npm run dev
  ```

- Build frontend assets:

  ```bash
  npm run build
  ```

- Lint frontend code:

  ```bash
  npm run lint
  ```

- Check PHP formatting:

  ```bash
  herd php vendor/bin/pint --test
  ```

`composer dev` still exists, but it uses `php artisan serve`. With Herd, the
recommended path is to let Herd serve the app and run only the frontend watcher
or any targeted artisan processes you need.

If `herd php -r 'echo PHP_VERSION, PHP_EOL;'` reports PHP 8.3 or older, switch
the Herd site/CLI runtime to PHP 8.4 before running Composer, tests, or Dusk.

## Verification Checklist

After a fresh setup:

- The app loads at `https://tracking.test`
- `/login` renders the Azure sign-in button
- `herd php artisan migrate --seed` completes without errors
- Admin login works through Azure for an allowed-domain user, or local-only
  smoke checks work through `/testing/oauth-login`
- Image uploads work on the configured disk
- Tracking behavior matches your configured Pipeline environment

## Documentation Map

Start here, in order:

1. `AGENTS.md`
2. `README.md`
3. `docs/local-setup.md`
4. `docs/dev-runbook.md`
5. `docs/context-index.md`

Additional project docs:

- Fresh-machine setup:
  - `docs/local-setup.md`
- Daily workflow and operational notes:
  - `docs/dev-runbook.md`
- Architecture conventions:
  - `docs/architecture-conventions.md`
- Scaffold map:
  - `docs/project-scaffold-playbook.md`
- Vue frontend contract:
  - `docs/vue-frontend-structure-contract.md`
- Release expectations:
  - `docs/release-process.md`
- Current priorities and handoff:
  - `docs/agent-handoff.md`

Transfer-kit reference docs remain under `docs/transfer-kit/`.
