# Release Process

## Before Merge

- Run `php artisan test`.
- Run `npm run lint`.
- Run `npm run build`.
- Confirm admin pages still load with initial Inertia props and no blank first
  render when data is already available.

## Documentation

- Update `CHANGELOG.md` for architecture or behavior changes.
- Update `docs/agent-handoff.md` when priorities change.
- Update `docs/roadmap.md` as work moves between active and next.

## Manual Smoke Checks

- Azure OAuth login reaches the admin surface for an allowed domain user.
- Company list, create, and edit flows load.
- Theme list and edit flows load.
- Allowed-domain management loads for super admins.
- Branded tracking resolves a known tracking number without exposing raw secrets
  in logs.
