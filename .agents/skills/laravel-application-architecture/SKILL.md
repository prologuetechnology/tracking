---
name: laravel-application-architecture
description: Use when building, reviewing, or refactoring a Laravel application toward a thin-controller, action/service-oriented architecture with Inertia pages, API resources, form requests, policies, workspace or tenant scoping, config-driven catalogs, structured logging, and Pest-tested boundaries.
---

# Laravel Application Architecture

Use this skill to structure Laravel backend code for a modern Inertia-backed application.

## Companion Rules

Use this in tandem with Laravel Boost and Laravel best-practice rules:

- Read Boost application metadata before assuming Laravel, PHP, database, or package versions.
- Use Boost `search-docs` before changing Laravel, Inertia, Pest, Tailwind, Sanctum, Pennant, Cashier, Scout, Socialite, or other ecosystem APIs.
- Use Boost `database-schema` before changing migrations, model relationships, casts, tenant scoping, or queries that depend on existing tables.
- Use Boost `database-query` for read-only database inspection instead of ad hoc tinker queries.
- Use Boost `read_log_entries`, `last_error`, and `browser-logs` for debugging.
- Use Boost `get-absolute-url` before sharing local app URLs.
- Prefer Laravel conventions and project-local patterns over generic abstractions.

## First Checks

Before editing, inspect:

- `composer.json`
- `bootstrap/app.php`
- `routes/web.php`, `routes/api.php`, `routes/console.php`
- `app/Http/Controllers/**`
- `app/Http/Requests/**`
- `app/Http/Resources/**`
- `app/Actions/**`
- `app/Services/**`
- `app/Models/**`
- `app/Policies/**`
- `config/*.php`
- related tests

Use Laravel version-specific documentation before changing framework APIs.

## Layering Rule

The backend shape should be:

```text
routes define audience and middleware
controllers form the HTTP boundary
form requests validate input
policies authorize resources
actions execute use cases
services hold reusable domain capabilities
models expose relationships, scopes, casts, and small helpers
resources shape API/Inertia payloads
tests lock behavior and architecture
```

Controllers should not own business logic.

## Routes

Group routes by audience:

- `guest.*` for public pages and auth entry points
- `authed.*` for authenticated Inertia pages
- `api.auth.*` for internal authenticated JSON endpoints
- `api.v1.*` or similar for external token APIs
- `admin.*` / `api.auth.admin.*` for admin surfaces

Use route model binding, named routes, scoped middleware groups, and explicit prefixes.

Use middleware aliases for domain concepts:

- auth
- tenant/resource alignment
- API token ability checks
- billing/browser guards
- normalized API errors
- request context
- feature availability

For public ingress such as webhooks, explicitly remove CSRF only on the ingress routes that need it.

## Bootstrap And Exceptions

Configure middleware and exception rendering in `bootstrap/app.php`.

Add request context early in the stack:

- request ID
- method/path/route name
- user ID where available
- tenant/workspace ID where available

Normalize external/versioned API errors in one renderer so validation, auth, missing scope, rate limit, and server errors have a stable shape.

## Controllers

Controller methods should look like this:

```php
public function store(
    DomainStoreRequest $request,
    StoreDomainItem $storeDomainItem,
): DomainItemResource {
    $this->authorize('create', DomainItem::class);

    $item = $storeDomainItem->handle($request->user(), $request->validated());

    return new DomainItemResource($item);
}
```

Responsibilities:

- authorize
- use validated input
- call an action/service
- convert domain exceptions into HTTP responses when needed
- return a resource, JSON response, redirect, or Inertia response

Use invokable controllers for one-action endpoints.

## Actions

Put use cases in `app/Actions/<Domain>/<VerbNoun>.php`.

```php
class StoreDomainItem
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
        private readonly ResolveAssignableProject $resolveAssignableProject,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function handle(User $actor, array $data): DomainItem
    {
        $workspace = $this->workspaceResolver->current($actor);

        return DB::transaction(function () use ($actor, $data, $workspace): DomainItem {
            return DomainItem::create([
                'workspace_id' => $workspace?->id,
                'created_by_user_id' => $actor->id,
                'name' => $data['name'],
            ]);
        });
    }
}
```

Actions should be single-purpose and named after what they do: `List...`, `Show...`, `Store...`, `Update...`, `Delete...`, `Resolve...`, `Sync...`, `Prune...`.

## Services

Use `app/Services/<Domain>` for capabilities reused across actions/controllers:

- tenant/current workspace resolution
- plan/tier resolution
- usage metering
- cache wrappers
- notification preference resolution
- external provider clients
- feature access resolution
- audit logging

Prefer constructor injection. Avoid `app()` lookups except at framework integration boundaries.

## Models

Models should be explicit:

- `$fillable` or guarded policy
- typed relationships
- casts or `casts()` method
- local scopes for reusable query rules
- small domain helpers only

Use UUID primary keys for app-owned models when records are exposed externally:

```php
trait HasUuidPrimary
{
    use HasUuids;

    protected static function bootHasUuidPrimary(): void
    {
        static::creating(function ($model): void {
            $model->setIncrementing(false);
            $model->setKeyType('string');
        });
    }
}
```

Use enums for stable states, permissions, roles, access modes, and sources.

Use custom casts for sensitive or backward-compatible encrypted fields when built-in casts are not enough.

## Tenant Or Workspace Scoping

When the app has tenant-owned records, make ownership columns consistent:

- `workspace_id` or tenant ID
- `created_by_user_id`
- optional legacy `user_id` only during migration/backward compatibility
- visibility column when organization-visible records exist

Create a resource contract and trait:

```php
interface WorkspaceOwnedResource
{
    public function workspaceId(): ?string;

    public function createdByUserId(): ?string;
}
```

Model scopes:

```php
public function scopeForWorkspaceContext(Builder $query, ?Workspace $workspace, User $user): void
{
    $query->where('workspace_id', $workspace?->id);
}

public function scopeVisibleInWorkspaceContext(Builder $query, ?Workspace $workspace, User $user): void
{
    $query
        ->forWorkspaceContext($workspace, $user)
        ->where(function (Builder $query) use ($user): void {
            $query->where('created_by_user_id', $user->id)
                ->orWhere('workspace_visibility', 'organization');
        });
}
```

Use middleware to align the active tenant to route-bound resources on safe requests. Hide inaccessible tenant resources as `404` when that is safer than revealing existence.

## Authorization

Use policies for resource access. Use RBAC permissions for admin and operational capabilities.

Keep tenant-aware checks in a reusable service so policies stay readable:

```php
public function update(User $user, DomainItem $item): bool
{
    if ($user->can('domain-items:manage')) {
        return true;
    }

    return $this->workspaceResourceAccess->canManage($user, $item);
}
```

Do not trust frontend lock states. Backend policies and action-level rules remain authoritative.

## Form Requests

Use Form Requests for all non-trivial input.

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'project_id' => ['nullable', 'uuid'],
        'visibility' => ['sometimes', 'string', Rule::in(['private', 'organization'])],
    ];
}
```

Controllers consume `$request->validated()` only.

## Resources

Use API resources for JSON and Inertia payload shaping:

```php
return [
    'id' => $this->id,
    'name' => $this->name,
    'workspace_id' => $this->workspace_id,
    'created_at' => $this->created_at,
    'project' => $this->whenLoaded('project', fn (): ?array => $this->project ? [
        'id' => $this->project->id,
        'name' => $this->project->name,
    ] : null),
];
```

Use `Resource::collection($items)->resolve()` when seeding Inertia initial data.

## Config As Source Of Truth

Put catalogs and limits in config:

- plans and feature limits
- tool/product catalogs
- permission names
- notification categories
- public feature metadata
- retention rules

Read `env()` only in config files.

## Jobs, Events, Notifications

Jobs should store scalar IDs, then re-query inside `handle()`. Delegate real work to actions.

Listeners coordinate side effects such as mail and notifications. Use cache dedupe or idempotency keys for send-once behavior.

Notifications should store structured database payloads and resolve user channel preferences at delivery time.

## Caching And Quotas

Wrap expensive operation caching in small services.

Build keys from normalized payloads plus user, tenant, feature, and version identifiers.

Fail open on non-critical cache errors and log warnings.

Use transactions and `lockForUpdate()` for counters, quotas, and max-view consumption.

## Logging

Use structured logs with a request ID. Never log raw secrets, tokens, auth headers, private payload bodies, or sensitive user content.

Prefer metadata:

- IDs
- status
- route
- duration
- byte lengths
- error codes

Redact centrally.

## Migrations

Use additive migrations for existing apps:

- add nullable ownership columns
- backfill
- add indexes/foreign keys
- tighten nullability later if appropriate

Include all column attributes when modifying a column.

Keep `down()` reversible unless a migration is intentionally forward-only.

## Tests Required

For every backend change, add focused Pest tests:

- happy path
- unauthenticated or unauthorized path
- validation failure
- tenant isolation if scoped
- normalized error shape for external APIs

Add architecture tests for cross-cutting invariants such as tenant-owned models requiring a trait/contract or route-bound resources requiring alignment middleware.
