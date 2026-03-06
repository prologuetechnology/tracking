# Project Scaffold Playbook

This document captures the reusable architecture, workflow, and implementation patterns used in this project so they can be applied to a new Laravel + Inertia application without reteaching an agent from scratch.

It is intentionally abstract. The domain of a future project may change completely, but the patterns in this document should still hold:

- Laravel owns the backend domain and request lifecycle.
- Inertia owns page delivery between backend and frontend.
- The frontend is a thin, interactive client over stable server-provided contracts.
- Business rules live in actions/services, not in controllers or UI components.
- Configuration is treated as a source of truth where practical.
- Privacy, observability, and release discipline are designed into the app from day one.

This write-up assumes a Laravel backend with Inertia and a component-based frontend. The current project uses React, TanStack Query, Tailwind, and shadcn/ui, but the same structure can be adapted to Vue if needed.

## 1. Core philosophy

### 1.1 Server-first, interactive second

The application should feel modern and app-like, but the server remains the source of truth.

- Initial page state should come from Laravel when the page renders.
- The frontend may revalidate and mutate with JSON APIs, but it should not need to guess what the first render looks like.
- Avoid client-only "blank state then fetch everything" patterns unless there is a strong reason.

This prevents UI flashes, reduces hydration mismatch risk, improves perceived performance, and makes the application easier to reason about.

### 1.2 Thin controllers, thick domain layer

Controllers should orchestrate. They should not own business logic.

Controllers should primarily do the following:

- accept the request
- rely on a Form Request for authorization/validation
- call an action or service
- transform the result with a resource when appropriate
- return an Inertia response or JSON response

Business logic belongs in:

- actions
- services
- dedicated support classes
- policy/gate helpers when the rule is authorization-specific

### 1.3 Stable contracts beat ad hoc convenience

The app should have clearly defined contracts between backend and frontend:

- shared Inertia props
- API response shapes
- query key conventions
- mutation result conventions
- config-driven metadata for feature catalogs, permissions, limits, and flags

An agent should be able to inspect those contracts and immediately understand how to add a new feature.

### 1.4 Privacy and observability coexist

Do not treat privacy and observability as opposites. Build both deliberately.

- Log operational events and failures.
- Avoid logging sensitive user content by default.
- Hash or minimize sensitive identifiers when practical.
- Add user-visible access history for trust-sensitive actions.
- Add verification or receipt mechanisms for operations where trust matters.

### 1.5 Reusability through shells and patterns

When a section of the product repeats, build a shell for it.

Examples of reusable shells:

- authenticated app layout
- public marketing/public tool layout
- settings section layout
- tool page shell
- history detail sheet
- item/list presentation pattern

The shell should solve layout, spacing, interaction, and data-loading patterns once so new pages become mostly configuration.

## 2. Recommended stack

This project uses the following stack and the combination works well:

- Laravel 12
- Inertia.js
- React
- Tailwind CSS
- shadcn/ui primitives
- TanStack Query
- Ziggy for named routes on the frontend
- Sanctum for API tokens and SPA auth
- Spatie Permission for RBAC
- UUID primary keys everywhere
- Pest for testing
- Pint and Larastan for code quality

For a new project, keep the stack unless there is a strong reason to change it. The patterns in this document assume:

- Laravel handles routing, validation, authorization, and domain execution
- Inertia renders pages
- TanStack Query handles revalidation, filters, pagination, polling, and optimistic client updates where appropriate
- shadcn/ui provides primitive UI components without locking the project into a visual style too early

## 3. Overall architecture

Organize the application into clear surfaces:

1. Public web surface
2. Authenticated app surface
3. JSON API for authenticated app interactions
4. Versioned external/public API surface
5. Admin surface

Each surface should have a distinct contract and purpose.

### 3.1 Public web surface

Use standard web routes that render Inertia pages without authenticated app chrome.

Examples:

- homepage
- pricing
- features
- contact
- terms/privacy
- public tools
- public docs/status pages

These routes should:

- be SEO-friendly
- render with a dedicated public layout
- avoid importing authenticated-only bundles and patterns

### 3.2 Authenticated app surface

Use web routes + Inertia pages for the main application.

Examples:

- dashboard
- settings
- feature index/show/edit pages
- authenticated tool pages

This surface should:

- use the authenticated app layout
- receive stable initial props from Laravel
- be enhanced with TanStack Query using `initialData`

### 3.3 Authenticated JSON API

Use JSON endpoints for in-app mutations and revalidation.

This surface exists to support:

- table filtering
- pagination
- detail drawers/sheets
- mutations without full page reload
- periodic polling or background refresh

These endpoints should be protected by Sanctum session auth or token auth depending on the feature.

### 3.4 Versioned external API

If the product exposes user-facing API access, isolate it under a versioned prefix such as `/api/v1`.

This surface should:

- have normalized error responses
- have explicit scope/ability enforcement
- use dedicated middleware
- be documented separately from internal app endpoints

Do not expose internal app APIs by accident. Build a deliberate external contract.

### 3.5 Admin surface

Treat admin features as their own product surface.

- Gate them with permissions, not with tier logic.
- Use the same app shell, but a separate navigation group and page set.
- Use live filters, query hooks, split views, and detail sheets consistently.

## 4. Directory and file organization

The exact folders can vary, but the following pattern is strong and scalable.

### 4.0 Code writing standards

This section is intentionally opinionated. These rules are designed to keep the codebase understandable for both humans and agents.

#### 4.0.1 One file, one primary concern

Every file should have one obvious job.

Good examples:

- one page component
- one feature component
- one query hook
- one mutation hook
- one Form Request
- one action
- one resource
- one model concern trait

Avoid files that try to do several different jobs at once, such as:

- page + networking + normalization + multiple unrelated subcomponents
- one hook file containing a dozen unrelated hooks
- one controller containing several independent business workflows
- one utility file that becomes a dumping ground

If a file name cannot clearly answer "what is this for?" in one sentence, it is probably doing too much.

#### 4.0.2 Prefer adjacent files over giant shared folders

Keep related code close to the feature that uses it.

Good:

- feature-specific components under a feature folder
- query hooks grouped by domain
- mutation hooks grouped by domain
- page-specific helpers next to the page when they are not reused elsewhere

Avoid pushing everything into top-level global folders just because it might be reused one day.

#### 4.0.3 Use small local barrel files, not giant global barrels

Use local `index.js` or `index.jsx` files only as adjacent named-export barrels for a single folder.

Good:

- `components/feature/snippets/index.js`
- `composables/queries/snippets/index.js`

Those local barrels should re-export a small, obvious set of files from that folder.

Avoid:

- a global `components/index.js` exporting the whole app
- deep barrel chains that hide where code actually lives

Rule of thumb:

- local barrels are good
- global barrels are bad

#### 4.0.4 Prefer named exports

Prefer named exports for almost everything:

- components
- hooks
- helpers
- actions
- services

Use default exports only when the framework strongly expects it, such as:

- Inertia page files
- certain framework entry points

Named exports make refactors, grep, and agent discovery much easier.

#### 4.0.5 Optimize for grep and discoverability

Code should be easy to search.

Prefer explicit names over clever abstraction.

Good:

- `useSnippetListQuery`
- `StoreSnippet`
- `CreateApiTokenRequest`
- `ApiTokenController`

Less helpful:

- `useData`
- `Manager`
- `Helper`
- `handleThing`

#### 4.0.6 Keep logic near the layer that owns it

Use this rough ownership model:

- validation/authorization of request shape -> Form Request
- orchestration of HTTP -> Controller
- business workflow -> Action
- reusable domain/system helper -> Service/Support class
- persistence + relationships -> Model
- transport formatting -> Resource
- client-side fetching -> Query hook
- client-side submission -> Mutation hook
- page composition -> Page component
- repeated UI anatomy -> Feature shell/layout

If code crosses ownership boundaries, pull it back into the proper layer.

### 4.1 Backend

Recommended backend organization:

- `app/Actions/*`
- `app/Services/*`
- `app/Http/Controllers/*`
- `app/Http/Requests/*`
- `app/Http/Resources/*`
- `app/Models/*`
- `app/Models/Concerns/*`
- `app/Policies/*`
- `app/Support/*`
- `app/Enums/*` when useful
- `config/*` for source-of-truth catalogs and limits

Rules:

- Actions represent domain work units.
- Services represent reusable business/system helpers.
- Requests handle authorization + validation + request shaping.
- Resources shape API payloads.
- Models stay focused on persistence and relationships.
- Shared model behavior goes into traits under `Models/Concerns`.

### 4.2 Frontend

Recommended frontend organization:

- `resources/js/Pages/*` for page entry points
- `resources/js/components/layout/*`
- `resources/js/components/feature/*`
- `resources/js/components/ui/*`
- `resources/js/composables/queries/<domain>/*`
- `resources/js/composables/mutations/<domain>/*`
- `resources/js/composables/hooks/*`
- `resources/js/forms/*`
- `resources/js/lib/*`

Rules:

- `Pages` should assemble data + layouts + feature components, not contain large amounts of custom business logic.
- `components/ui` contains shadcn and thin wrappers only.
- `components/feature` contains domain-aware UI blocks.
- `queries` and `mutations` are grouped by domain.
- `lib` contains shared helpers, clients, formatting, error normalization, analytics, and route utilities.

#### 4.2.1 Frontend directory rules

Use the following mental model:

- `Pages/` = route entrypoints
- `components/layout/` = app or section shells
- `components/feature/` = reusable domain-aware building blocks
- `components/ui/` = primitives only
- `composables/queries/` = read operations
- `composables/mutations/` = write operations
- `composables/hooks/` = UI/app behavior hooks that are not tied to one specific HTTP call
- `lib/` = cross-cutting helpers and infrastructure

Do not put API logic directly into page files.

Do not put page-specific rendering logic into `lib/`.

#### 4.2.2 Page file rules

Each page file should usually do the following:

- declare the page component
- receive initial props from Inertia
- call the necessary query/mutation hooks
- compose layouts and feature components
- set page metadata with `Head`

It should not:

- define large inline helper libraries
- manually normalize API payloads
- directly manage unrelated domain logic

When a page gets too large, split out:

- page sections
- dialogs/sheets
- table/list blocks
- page-specific hooks

#### 4.2.3 Query and mutation file rules

A query file should usually contain:

- query key helper if needed
- fetcher function
- hook

A mutation file should usually contain:

- request function
- hook
- invalidate/refetch behavior

Do not combine unrelated read/write concerns in one hook file.

#### 4.2.4 Component composition rules

Prefer component composition over giant props objects.

Good:

- `Item`
- `ItemContent`
- `ItemAction`

Good:

- `SettingsLayout`
- `ApiAccessForm`
- `ApiTokenList`

Avoid giant "Swiss Army knife" components with many conditional branches that try to power every screen.

#### 4.2.5 Form composition rules

For forms:

- validation belongs to the backend
- client-side state should stay shallow and explicit
- field labels/descriptions/errors should be rendered consistently
- submission and error handling should flow through a mutation hook

If a form has several related sections, split those sections into child components, but keep one owner for submit state.

### 4.3 One intent per file

Files should have one clear job.

Good:

- one request per endpoint family
- one mutation hook per action
- one query hook per dataset
- one shell component per repeated page pattern

Avoid:

- giant generic util files
- page files that contain all networking, transformation, and rendering inline
- controllers that perform business logic themselves

### 4.4 Naming conventions

Naming is part of architecture. Use consistent, explicit names.

#### 4.4.1 Backend naming

- Actions: verb-first, singular workflow
  - `StoreSnippet`
  - `IssuePasswordChallenge`
  - `RevokeApiToken`
- Services: domain/system helper
  - `AnonymousToolRunLimiter`
  - `PrivacyReceiptVerifier`
- Requests: action-oriented and endpoint-specific
  - `CreateApiTokenRequest`
  - `RunBase64Request`
- Resources: entity-oriented
  - `SnippetResource`
  - `ToolHistoryResource`
- Controllers: domain-oriented by surface
  - `ApiTokenController`
  - `PublicToolController`
  - `SnippetPageController`
- Traits: `Has...` or behavior-oriented
  - `HasUuidPrimary`

#### 4.4.2 Frontend naming

- Pages: noun-oriented, framework-default export okay
  - `Dashboard.jsx`
  - `ApiAccess.jsx`
  - `Show.jsx`
- Layouts: `*Layout`
  - `AppLayout`
  - `PublicLayout`
  - `SettingsLayout`
- Feature shells: `*Page`, `*Panel`, `*Sheet`, `*Section`
  - `ToolFormPage`
  - `PublicToolPage`
- Query hooks: `use<Thing>Query`
  - `useToolHistoryQuery`
  - `useSnippetListQuery`
- Mutation hooks: `use<Verb><Thing>Mutation`
  - `useCreateApiTokenMutation`
  - `useRunBase64Mutation`
- Generic hooks: `use<Behavior>`
  - `useAuthorization`
  - `useDebouncedValue`

#### 4.4.3 Route and permission naming

- Routes must be explicit and surface-aware.
- Permissions should use colon-delimited segments.

Examples:

- route: `api.auth.settings.api-tokens.store`
- route: `guest.tools.base64`
- permission: `admin:users:view`
- permission: `admin:roles:manage`

### 4.5 Import, export, and dependency hygiene

#### 4.5.1 Keep imports honest

Imports should make ownership obvious.

Prefer:

- direct imports from the file you need
- local adjacent index barrels for a folder

Avoid:

- deep alias webs that hide where code lives
- importing half the app through giant barrel files

#### 4.5.2 UI primitives are not feature dumping grounds

Do not add domain logic to `components/ui`.

If something knows about snippets, tools, billing, admin, or settings, it belongs in `components/feature` or `Pages`, not in `components/ui`.

#### 4.5.3 Avoid circular abstractions

If two folders import each other heavily, the abstraction line is probably wrong.

Typical healthy dependency direction:

- `Pages` -> `components/feature` -> `components/ui`
- `Pages` -> `composables`
- `Controllers` -> `Actions/Services/Resources`
- `Actions` -> `Models/Services/Support`

Try to keep lower-level layers from importing higher-level ones.

### 4.6 What not to do

These anti-patterns are worth explicitly banning:

- business logic in controllers
- networking code inline in page render functions
- hardcoded internal URLs instead of named routes
- duplicating config metadata in frontend constants
- client-only first render for pages that could be server-hydrated
- giant global barrel files
- editing shadcn base primitives for one-off feature needs
- using roles to represent paid plans
- hiding important rules in vague helpers named `utils` or `helpers`
- storing mutable workflow state in config

## 5. Routing patterns

### 5.1 Named routes are mandatory

Name routes consistently and use those names everywhere.

Benefits:

- Ziggy integration on the frontend
- stable API references
- easier refactors
- easier test assertions

Use descriptive, surface-aware names, for example:

- `guest.home`
- `auth.dashboard`
- `api.auth.settings.api-tokens.index`
- `api.v1.snippets.store`

### 5.2 Group routes by surface and middleware

Structure routes by access surface:

- guest/public
- authenticated
- admin
- API v1

This makes middleware intent obvious and prevents authorization sprawl.

### 5.3 Page routes vs data routes

Use Inertia page routes for initial render and dedicated JSON routes for live interactions.

Do not overload one route to do both jobs.

Recommended split:

- web route renders page with initial payload
- API route powers filters, pagination, create/update/delete, polling, detail sheets

## 6. Configuration as a source of truth

This is one of the most valuable patterns in the project.

When a feature has metadata that multiple layers care about, put it in config.

Examples:

- feature/tool catalogs
- plan limits
- API scopes/abilities
- public tool settings
- dashboard widget catalogs

Config is especially useful when both backend and frontend need consistent metadata such as:

- slug
- label
- domain/category
- minimum plan
- logging behavior
- route names
- tags
- capability flags

### 6.1 What config should contain

Good config data:

- static metadata
- labels
- flags
- defaults
- route names
- limits
- capability maps

Bad config data:

- mutable user state
- database-like content
- per-request dynamic values

### 6.2 Inertia shared props from config

Expose stable config-derived metadata through shared props when the whole app needs it.

Examples:

- authenticated user summary
- role/permission list
- billing/tier summary
- feature catalogs
- plan limits needed by UI

This prevents scattered frontend requests for global context.

## 7. Data modeling conventions

### 7.1 UUIDs everywhere

Use UUID primary keys across core models.

Benefits:

- safer public identifiers
- easier data merging/imports
- less guessable URLs
- consistent multi-surface behavior

Standard model defaults:

- non-incrementing
- string key type
- UUID assigned on create via model trait

### 7.2 Keep models lean

Models should focus on:

- relationships
- casts
- scopes
- simple derived attributes

Do not push feature workflows into models if they involve:

- plan checks
- authorization branching
- service calls
- multi-step orchestration

That belongs in actions/services.

### 7.3 Use resources for payloads

When an entity is used in multiple endpoints or pages, create a resource.

Benefits:

- stable JSON shape
- shared field formatting
- easier future additions
- cleaner controllers

## 8. Authorization model

Separate the following concerns cleanly:

1. Authentication
2. Role/permission authorization
3. Subscription/tier authorization
4. Per-token ability authorization

Do not collapse them into one concept.

### 8.1 Authentication

Use Laravel auth + Sanctum for:

- session auth
- SPA auth
- API token auth

### 8.2 RBAC

Use Spatie Permission for roles and permissions.

Recommended conventions:

- colon-delimited permission keys
- role names as stable slugs
- direct permissions only when needed

Example permission format:

- `admin:users:view`
- `admin:roles:manage`
- `billing:entitlements:manage`

### 8.3 Tier gating

Tier gating is separate from RBAC.

Tier should answer:

- what the customer has purchased
- what limits/capabilities the plan allows

RBAC should answer:

- what the actor is allowed to do inside the app

Do not use roles to represent paid plans unless the entire app is fundamentally staff-facing.

### 8.4 API token abilities

API tokens should have scoped abilities separate from user roles.

This allows a Pro user to create a narrow token for:

- read-only snippet access
- one integration
- one external client

This is more secure than letting all tokens inherit every user capability.

## 9. Backend request flow

For most feature endpoints, follow this flow:

1. Request authorize/validate
2. Access/tier/ability checks
3. Action/service execution
4. Optional quota accounting
5. Optional activity/history logging
6. Resource/response shaping
7. Return consistent response

### 9.1 Form Requests

Use Form Requests aggressively.

They should own:

- authorization checks that depend on the incoming request
- validation rules
- request normalization via `prepareForValidation`
- request docs metadata if using a generator like Scribe

### 9.2 Controllers

Controllers should remain narrow.

Good controller responsibilities:

- accept typed request
- call one or more actions/services
- map result into response
- log user access/security event if needed

### 9.3 Actions and services

Use actions for feature workflows that map to business verbs:

- store snippet
- issue MFA challenge
- create API token
- revoke entitlement

Use services/support classes for reusable helpers:

- anonymous limiter
- token usage recorder
- privacy receipt generator/verifier
- caching helpers

### 9.4 Resources

Use resources when returning structured entities or collections.

Use custom JSON only when the response is action-specific and not a core entity payload.

## 10. Frontend data-loading pattern

### 10.1 First render comes from Laravel

Whenever a page renders, Laravel should provide enough initial data for the page not to flash from empty to loaded.

The frontend then hydrates TanStack Query with `initialData`.

Pattern:

- web route renders Inertia page
- page receives initial payload
- page passes that payload into query hook
- query hook revalidates in background when necessary

This is a critical pattern worth preserving in every new project.

### 10.2 Query hooks

Each query hook should define:

- query key
- query function
- payload normalization
- polling/refetch behavior if needed
- optional `placeholderData`

Keep them domain-specific.

### 10.3 Mutation hooks

Mutations should be thin and consistent.

Recommended mutation hook responsibilities:

- submit request
- normalize success/error behavior
- emit toasts
- invalidate/refetch relevant queries
- emit analytics if the app uses analytics

Where possible, build generic mutation primitives and wrap them in thin domain-specific hooks.

### 10.4 Error handling

Use a shared frontend error-normalization layer.

This should turn:

- validation payloads
- network failures
- backend error strings
- unexpected exceptions

into clean, human-readable messages for the UI.

Do not let raw Axios or PHP messages leak into the interface.

## 11. Layout and shell patterns

### 11.1 App layout

Create one authenticated shell that owns:

- sidebar/nav
- command palette entry
- top/sticky header
- shared badges/plan indicators
- user menu
- page container dimensions

The app layout should be data-driven from shared props, not hardcoded per page.

### 11.2 Public layout

Create a fully separate public shell.

It should own:

- public navigation
- public footer
- SEO-friendly structure
- marketing visuals

Do not reuse authenticated layout pieces here.

### 11.3 Section layouts

Inside the app layout, use section layouts where helpful:

- settings layout
- admin layout
- feature-specific split views

This keeps page files small and consistent.

### 11.4 Feature shells

If a feature repeats the same page anatomy, create a feature shell.

Examples:

- tool form page shell
- public tool page shell
- split-view admin page shell
- list/detail sheet shell

Feature shells are one of the biggest accelerators for future work.

## 12. UI component philosophy

### 12.1 Use shadcn/ui primitives, do not fork casually

Treat shadcn as the primitive layer.

- build on top of it
- wrap it when needed
- do not modify base primitives casually

This keeps upgrades and consistency manageable.

### 12.2 Use domain-aware wrappers sparingly

Good wrappers:

- item/list presentation
- feature-specific panels
- multi-part controls used in several places

Bad wrappers:

- one-off abstraction that hides simple markup

### 12.3 Prefer composition over mega-components

For example:

- `Item`
- `ItemContent`
- `ItemAction`

is better than a single opaque "EverythingListRow" component.

## 13. Communication between backend and frontend

### 13.1 Inertia shared props are the app-level contract

Use `HandleInertiaRequests` to share:

- authenticated user summary
- role/permission data
- plan summary
- feature catalogs
- app metadata
- flash messages

Only share stable, broadly useful context.

### 13.2 Page props are the page-level contract

Each page route should pass only the initial data needed to render that page well.

Examples:

- first page of a list
- selected entity
- current filters
- server-derived capability flags

### 13.3 JSON APIs are the interaction contract

Use JSON routes for:

- list refresh
- pagination
- polling
- create/update/delete
- background details
- live search

These should return predictable shapes so query hooks stay thin.

### 13.4 Route names are shared

Use named routes through Ziggy on the frontend. Do not hardcode internal URLs.

### 13.5 Internal navigation uses Inertia Link

All internal links should use Inertia's `Link` component so navigation stays within the app lifecycle.

## 14. Feature-specific pattern: config-driven catalogs

For repeatable product surfaces, use a config catalog as the central source of truth.

Good candidates:

- tools
- widgets
- plans
- scopes/abilities
- public tool metadata

Each entry should define everything the app needs at render and decision time.

For example, a catalog entry may include:

- slug
- label
- category/domain
- plan minimum
- route names
- API route names
- whether history is logged
- public availability
- export capabilities
- tags

This enables both backend and frontend to stay in sync without duplicated logic.

## 15. Logging, observability, and privacy

### 15.1 Structured logs

Use structured JSON logging with request context. Every logged error should include:

- request ID
- method
- path
- route name
- user ID when available
- exception class
- message
- file/line for unexpected exceptions

### 15.2 Separate channels by purpose

Use dedicated channels for:

- application errors
- monitoring/alerting
- audit
- analytics relay failures

### 15.3 Do not log sensitive content unless absolutely necessary

For privacy-sensitive apps:

- avoid logging raw payloads by default
- avoid storing raw IPs when hashed versions are enough
- use explicit, narrow exceptions when certain content must be preserved

### 15.4 User-visible access history

For trust-sensitive applications, surface security-relevant access history to the user:

- login success
- logout
- password change
- social link/unlink
- MFA verification
- API key lifecycle events

## 16. Receipts, verification, and trust artifacts

For actions where trust matters, it can be valuable to produce a signed receipt or verification artifact.

General pattern:

- backend creates deterministic signed metadata about the event
- metadata is stored alongside the event or run
- user can verify later that the record has not been tampered with

This pattern is especially useful for:

- tool runs
- retention-sensitive workflows
- compliance or privacy-facing features

In a new project, use this only where it adds real trust value.

## 17. Rate limiting and quota patterns

### 17.1 Named rate limiters

Define rate limiters centrally and name them clearly.

### 17.2 Anonymous limits

For public, unauthenticated features:

- use Redis-backed daily counters
- key by a privacy-conscious fingerprint
- return remaining counts in the response

### 17.3 Product quotas

Separate request rate limiting from product quotas.

Examples of product quotas:

- monthly tool runs
- maximum snippets
- maximum active API tokens

Quotas should live in domain logic and plan config, not in generic HTTP rate limiters.

## 18. External API design

If the product exposes an external API:

- version it from day one
- document only supported endpoints
- normalize errors consistently
- scope token abilities narrowly
- record token usage metadata
- separate internal app APIs from public APIs

Use a middleware stack that makes the intent obvious:

- auth token required
- legacy bearer rejection if needed
- plan gate
- token ability checks
- token usage recording
- normalized error shaping

## 19. Testing strategy

### 19.1 Test categories

Use several layers of testing:

- unit tests for isolated business logic
- feature tests for HTTP/integration behavior
- critical-path grouped tests for deploy confidence

### 19.2 What to test first

If time is limited, prioritize:

- authentication flows
- billing/plan gates
- role/permission checks
- API token access
- core feature store/show/update/delete flows
- public endpoints with anonymous limits

### 19.3 Critical deploy suite

Have a short predeploy suite that covers the riskiest flows.

Example coverage:

- auth
- billing/tier gates
- key feature create/read/update/delete
- public API contracts
- external API auth

### 19.4 UI/end-to-end tests

Browser tests are useful, but they are not the first layer to add.

Recommended order:

1. strong feature tests
2. strong critical path suite
3. then add browser coverage for the most delicate UX flows

## 20. Documentation discipline

Maintain docs as part of the product, not as a cleanup task.

Recommended docs:

- README
- dev runbook
- architecture conventions
- release process
- roadmap
- changelog
- agent scaffold/playbook (this document)

### 20.1 README should cover

- what the app is
- stack
- local setup
- how to run tests
- branch/release flow
- high-level feature architecture

### 20.2 Runbook should cover

- deployment flow
- env requirements
- operational debugging
- analytics/mail/billing gotchas
- common recovery steps

### 20.3 Changelog should be maintained deliberately

Do not rely on git history alone for release communication.

Keep a human-readable changelog with dated entries and version tags.

## 21. Branching and release workflow

Recommended flow for this style of project:

1. branch from `develop`
2. implement feature/fix
3. run relevant tests
4. merge feature branch into `develop`
5. batch several validated changes into `main`
6. tag from `main`
7. update changelog on release

Use descriptive commits. Favor small feature branches with clear scope.

## 22. New-project scaffold checklist

When starting a new project, set these up immediately:

### 22.1 Platform and structure

- Laravel app
- Inertia app shell
- React or Vue frontend
- Tailwind + shadcn
- UUID primary key trait
- shared app layout + public layout
- route groups by surface

### 22.2 Quality and tooling

- Pest
- Pint
- Larastan
- critical-path test grouping
- predeploy script

### 22.3 Auth and access

- Laravel auth
- Sanctum
- Spatie Permission
- shared auth props
- authorization helper hook on frontend

### 22.4 Product config

- plan limits config
- feature catalog config
- API scope config if external API is planned

### 22.5 Operational foundations

- structured logging
- request IDs
- analytics abstraction
- mail fallback strategy
- changelog
- runbook
- roadmap

### 22.6 Frontend foundations

- app bootstrap with query provider
- error normalization helper
- query/mutation folder structure
- first-render initial data rule
- internal links via Inertia Link

## 23. What to tell a new coding agent

If handing a fresh agent a new project built with this pattern, tell it the following:

1. Laravel is the source of truth for first render and domain execution.
2. Controllers are thin; actions/services own business logic.
3. Inertia shared props provide global app context.
4. TanStack Query is used for revalidation and live interactions, always with server-provided `initialData` when possible.
5. Route names are canonical and must be used through Ziggy.
6. Internal navigation must use Inertia `Link`.
7. Feature catalogs and limits live in config and must not be duplicated.
8. RBAC, tier gating, and token abilities are separate concerns.
9. Use shadcn/ui primitives; do not casually edit base UI components.
10. Prefer reusable shells for repeated page patterns.
11. Prioritize privacy, structured observability, and deploy safety.
12. Update docs and changelog as part of feature completion.

## 24. Opinionated conventions worth keeping

These conventions have been especially high-leverage and are worth carrying into future projects:

- UUID primary keys by default
- config-driven feature catalogs
- server-provided first render data with query hydration
- thin controllers + action layer
- named routes everywhere
- separate public/authenticated/admin/API surfaces
- explicit separation of RBAC vs plan limits vs API abilities
- shadcn primitives with feature-level composition
- structured logging with request context
- critical-path test suite for deploy confidence
- deliberate release process with changelog + tags

## 25. Final guidance

If you reuse this scaffold in a new project, resist the urge to optimize prematurely around the domain. Keep the architecture generic, explicit, and boring in the best way:

- clear route surfaces
- explicit config
- stable contracts
- reusable shells
- predictable data loading
- isolated business logic

That combination is what makes the project scalable for both humans and agents.

If the domain changes, these patterns should still fit. The names of the features will change, but the operating model should not need to.
