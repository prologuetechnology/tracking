# Dev Runbook

## Install

- `composer install`
- `npm install`
- `cp .env.example .env`
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`

## Local Development

- Full dev stack:
  - `composer dev`
- Backend tests:
  - `php artisan test`
- Frontend production build:
  - `npm run build`
- Frontend lint:
  - `npm run lint`
- Frontend format check:
  - `npm run format:check`

## Required Environment Areas

- database connection
- session/cache configuration
- Azure OAuth credentials
- Pipeline API credentials
- Spaces or equivalent object storage configuration

## Notes

- Admin access depends on seeded roles, permissions, allowed domains, and users.
- Branded tracking depends on valid Pipeline credentials and company setup.
