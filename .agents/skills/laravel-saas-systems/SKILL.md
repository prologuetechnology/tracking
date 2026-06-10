---
name: laravel-saas-systems
description: Use when adding optional SaaS systems to a Laravel app, including social identity, email aliases, workspaces, plan resolution, manual entitlements, feature flags, sharing links, audit/activity logs, API tokens, notification preferences, webhooks, scheduled jobs, and background processing. Applies even when only some of these systems are needed.
---

# Laravel SaaS Systems

Use this skill to add optional SaaS capabilities without forcing every project to adopt every subsystem.

The meta-pattern: hide each concern behind small resolver, catalog, action, and middleware seams. Products opt in by adding config, tables, routes, and UI only where needed.

## Companion Rules

Use this in tandem with Laravel Boost and Laravel best-practice rules:

- Read Boost application metadata before assuming installed packages or versions.
- Use Boost `search-docs` before changing Sanctum, Cashier, Pennant, Socialite, notifications, queues, validation, or routing APIs.
- Use Boost `database-schema` before adding SaaS migrations or touching existing ownership, billing, token, notification, or audit tables.
- Use Boost `database-query` for read-only inspection of current state.
- Use Boost logs and browser logs when diagnosing runtime behavior.
- Keep every subsystem optional unless the product explicitly needs it.

## Decision Pass

Before implementing, decide which systems are in scope:

- social auth or password-only auth
- verified email aliases
- personal and organization workspaces
- billing/subscriptions
- manual entitlements or trial grants
- feature flags and rollout groups
- public/restricted sharing
- audit logs and activity timelines
- API tokens
- notification preferences
- webhook inbox/ingress
- scheduled/background work

Do not scaffold unused systems by default.

## Auth And Social Identity

Keep provider identities separate from users:

```text
users
auth_identities
user_emails
email_login_challenges
```

Rules:

- Social identity rows store provider, provider user ID, provider email, verification timestamp, username/avatar metadata, linked/last-used timestamps.
- Verified email alias rows store normalized email, verified timestamp, primary flag, verification token hash, and source.
- Auto-link a provider only when the provider email is verified or matches an existing verified alias.
- Otherwise require an authenticated link flow.
- One-time login/MFA challenges store hashed codes, max attempts, expiry, and consumed timestamp.
- Verify one-time codes inside a transaction with row locking.

Never store raw challenge codes.

## Workspaces

Use workspaces when records may belong to a personal context, an organization context, or both.

Core tables:

```text
workspaces
workspace_memberships
workspace_invitations
```

Patterns:

- Every user gets a personal workspace.
- The user has an `active_workspace_id`.
- Memberships carry roles such as owner/admin/member.
- Invitations store normalized email, inviter, token hash, expiry, accepted/cancelled timestamps.
- A `WorkspaceResolver` owns current workspace, available workspaces, membership lookup, organization creation, and activation.
- Tenant-owned resources implement a shared contract and trait.

For organization-visible records, model visibility explicitly rather than inferring it from membership alone.

## Billing And Entitlements

Keep plan resolution behind one service.

Resolution order should be explicit, for example:

1. Current workspace manual grant.
2. Current workspace subscription.
3. User manual grant.
4. User subscription.
5. Free/default tier.

Manual grants should be first-class records:

- workspace/user target
- tier
- status
- lifetime flag
- start/end timestamps
- granted/revoked actor IDs
- source
- notes

For team billing, separate:

- licensed seats
- active member seats
- pending invitation seats
- billable seat preview
- seat update action

Frontend upgrade states can mirror this, but backend plan resolution and policies remain authoritative.

## Feature Flags

Use framework-native flags for direct user or tenant overrides. Add a registry for admin-managed feature names and rollout groups when non-developers need control.

Patterns:

- `config/features.php` for coarse product booleans.
- feature registry service for known flags.
- feature group tables for cohorts.
- admin actions to sync group members and features.
- route middleware for unavailable product areas.
- shared Inertia props for effective frontend flags.

Return `404` for unavailable product areas when the route should not appear to exist.

## Sharing And Public Access

For share links, store token hashes for lookup. Store encrypted token copies only when the creator must re-display or copy the link later.

Support access modes as separate concerns:

- public link
- authenticated recipient list
- expiry
- revocation
- max views
- import/copy attribution

Restricted sharing should authorize by verified email aliases, not only account primary email.

Consume max-view shares in a transaction:

```php
$share = Share::query()
    ->where('token_hash', hash('sha256', $token))
    ->lockForUpdate()
    ->firstOrFail();
```

Check revoked, expired, target availability, recipient eligibility, and view cap before returning data.

## Audit And Activity Logs

Split logs by purpose:

- security/admin audit logs
- product activity timelines
- operational structured logs

Audit rows should include:

- actor ID
- action
- target type and ID
- request ID
- metadata JSON
- timestamps

Product activity rows should include:

- tenant/workspace ID
- actor ID
- type
- subject type and ID
- title/summary
- metadata JSON

Attach request context globally and redact sensitive fields centrally.

## API Tokens

Use Sanctum or equivalent token storage, but wrap it with product rules:

- plan gate for API access
- workspace attribution
- created-by actor
- abilities/scopes
- one-time plaintext reveal
- rotation endpoint
- revoke endpoint
- last-used timestamp
- usage telemetry
- separate rate limits for bearer traffic

Prefer versioned external routes for token access. Reject bearer tokens on legacy/internal routes if that prevents accidental exposure.

Middleware stack for external API routes commonly includes:

- token auth
- legacy bearer rejection where needed
- plan gate
- ability gate
- token usage recorder
- tenant/resource alignment
- normalized errors
- rate limit

## Notifications And Preferences

Create a notification preference catalog:

```php
[
    'security' => [
        'label' => 'Security',
        'channels' => ['mail', 'database'],
        'required' => ['mail'],
    ],
]
```

Rules:

- Resolve preferences at delivery time.
- Required channels stay enabled.
- Optional channels use defaults until a user preference row exists.
- Actionable notifications store action state in notification data.
- Dedupe notifications with stable keys when events can be retried.

Use actions/listeners to send notifications rather than direct sends buried in controllers.

## Webhooks

Webhook endpoints should have:

- opaque public token
- status
- provider preset
- verification type
- encrypted verification secret
- signature header/prefix
- response mode
- acknowledgement status code
- retention days
- delivery counters and latest state

Webhook deliveries should record:

- endpoint ID when resolved
- method/path/query
- sanitized headers
- encrypted or truncated raw body
- body size
- source IP/user agent
- event name
- delivery state
- verification result
- parsed summary
- ack status code

Verify signatures before marking deliveries trusted. Acknowledge consistently even when an endpoint is inactive or missing if that avoids provider retry storms.

## Scheduled And Background Work

Keep `routes/console.php` schedules thin:

```php
Schedule::call(function (PruneExpiredRecords $prune): void {
    $prune->handle();
})->daily()->withoutOverlapping();
```

Jobs should:

- accept scalar IDs
- re-query models in `handle()`
- delegate to actions
- be idempotent where possible
- store run records for visible workflows/checks

Use locks around recurring checks and retention jobs.

## Optional System Rule

If a fresh project does not need a subsystem, keep the seam but skip the implementation:

- `PlanResolver` can return `free` until billing exists.
- feature resolver can read config booleans until admin flags exist.
- workspace resolver can return `null` until workspaces exist.
- notification resolver can return default channels until preferences exist.

This keeps future adoption easy without overbuilding day one.
