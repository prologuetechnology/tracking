# Architecture Conventions

Last updated: 2026-02-12

## Backend
- Framework: Laravel 12, Sanctum auth, Spark/Stripe billing.
- RBAC conventions:
  - Spatie Permission remains UUID-first for roles/permissions/pivots.
  - Permission names are colon-delimited (`domain:resource:action`, e.g. `admin:users:manage`).
  - Role names are stable slugs (`owner`, `admin`, `support`, `billing`, `read-only-ops`, `member`).
  - Tier gating (Stripe + plan limits) and RBAC authorization are separate concerns.
  - Bootstrap/maintenance commands:
    - `php artisan admin:seed-rbac`
    - `php artisan admin:grant-owner --email=...`
    - `php artisan admin:revoke-owner --email=... --force`
  - Owner-role policy:
    - owner promotion is allowed only by existing owners.
    - owner demotion is blocked in UI/API flows and is server-console only.
    - revoking owner from the last owner account is forbidden.
- Billing preflight guard:
  - Spark routes are protected by `EnsureSparkBillingConfigured`.
  - Internal billing links should use `authed.billing` (workspace route) rather than linking directly to Spark route names.
- Tool API controllers are thin and live under `app/Http/Controllers/Api/Tools`.
- Domain logic is in `app/Actions/**`.
- Validation is in `app/Http/Requests/**`.
- Complimentary entitlement source is enum-backed:
  - `app/Enums/SubscriptionGrantSource.php`
  - options should be rendered as controlled select values in admin UI.
- Tool route groups:
  - `api.auth.tools.inspect.*`
  - `api.auth.tools.data-transform.*`
  - `api.auth.tools.identity-security.*`
  - `api.auth.tools.utility.*`
  - `api.auth.tools.history.*`
- Keep legacy route aliases only while migration compatibility is needed.

## Shared Tool Guard / Responses
- Use `ConsumesToolRunQuota` in tool controllers.
- Always apply:
  1. `denyToolAccess(<tool-slug>, user, planResolver)`
  2. execute action
  3. `consumeToolRunQuota(...)`
  4. `LogToolHistory`
  5. `successResponse(...)`
- Error shape should follow `errorResponse(...)` and `quotaExceededResponse(...)`.

## Tool Catalog as Source of Truth
- `config/tools.php` controls:
  - `domain`
  - `status` (`active`, `planned`, `coming-soon`)
  - `minimum_tier`
  - `route_name`
  - `api_route_name`
- UI grouping is derived from this catalog via Inertia shared props.
- Shared prop `tools.history_logging` is backend-resolved and is the source of truth for logging-visibility UI outside individual tool pages.

## History / Metrics
- Tool history type enum: `app/Enums/ToolHistoryType.php`.
- Tool history table enum values must stay in sync with enum cases.
- Current usage quota metric: `tool_runs_total` monthly counter.
- Tool logging policy:
  - Global default is controlled by `config/tools.php` via `logs_history`.
  - Runtime enforcement is centralized in `app/Services/Tools/HistoryLoggingPolicy.php`.
  - User-specific overrides are stored in `user_tool_history_preferences`.
- Retention source of truth:
  - `config/plan_limits.php` (`tiers.*.history_retention_days`).
  - Pruning runs daily via `routes/console.php` scheduled call `history:prune-retention`.

## Logging / Observability (Privacy-First)
- Request correlation:
  - `AttachRequestContext` middleware sets/propagates `X-Request-Id`.
  - Shared log context includes: `request_id`, `method`, `path`, `route_name`, `user_id`.
- Structured logging:
  - JSON formatting is applied via `App\Logging\ConfigureStructuredLogging`.
  - Sensitive keys are redacted via `App\Logging\RedactSensitiveContextProcessor`.
- Standard channels:
  - `app` (`storage/logs/app.log`)
  - `auth_billing` (`storage/logs/auth-billing.log`)
  - `security_audit` (`storage/logs/security-audit.log`)
  - `error_monitoring` (`storage/logs/error-monitoring.log`)
- Admin logs surface:
  - `/admin/logs` and `api.auth.admin.logs.index` are read-only, filterable, and paginated.
  - filters: `search`, `channel`, `level`, `page`, `per_page`.
  - polling is UI-controlled (pause/resume) from frontend.
- Logging policy:
  - Never log raw tool/snippet payload bodies, tokens, secrets, or auth headers.
  - Use metadata (IDs, tool slug, status, duration, byte lengths) instead of content bodies.
- Analytics decision:
  - Umami is the selected V1 analytics route with a privacy-scoped event schema.
  - Event taxonomy source of truth: `docs/analytics-event-taxonomy.md`.

## Frontend
- React + Inertia + TanStack Query.
- Do not hardcode endpoint URLs; use Ziggy `route(...)`.
- Shared Inertia `billing.tier` is used for locked/upgrade UI states; backend remains authoritative.
- Internal app navigation uses Inertia `<Link>` from `@inertiajs/react`.
- Native `<a>` tags are only for:
  - external URLs/protocol links (`https://`, `mailto:`, etc.)
  - OAuth/provider handoff routes that require a full-page redirect
- Page locations:
  - `resources/js/Pages/tools/**`
  - `resources/js/Pages/snippets/**`
- Layout:
  - `resources/js/components/layout/AppLayout.jsx`
  - Tool pages receive a backend-resolved `historyLogging` prop and surface it in the sticky header via a tooltip indicator (do not infer logging state purely in UI).
- Tool page contract (uniformity baseline):
  - Use shared shells:
    - `resources/js/components/feature/tools/ToolFormPage.jsx`
    - `resources/js/components/feature/tools/ToolApiPage.jsx`
  - Maintain consistent layout variants:
    - default: input left, output right (`outputPosition="right"`)
    - compare/validate tools: inputs above or side-by-side with output below (`outputPosition="bottom"`, `multiTextareaLayout="columns"`).
    - tool-specific output customization is allowed via shared-shell output renderer (`renderOutput`) when list-style output actions are needed (for example UUID per-line copy).
  - Avoid card-in-card page composition for tools; use clean split panes with separators.
  - Control strip pattern:
    - top row is for compact controls only (mode/version/delimiter/options).
    - compact control widths are explicit via `controlWidthClass`.
    - use shadcn `toggle-group` for mode switching, and shadcn `Select` for dropdown options.
  - Input copy pattern:
    - prefer placeholder-first phrasing (`Paste ...`) and concise labels.
    - do not seed sample payloads by default.
  - Output panel pattern:
    - scrollable, monospaced, wrapped (`break-words` + `whitespace-pre-wrap`).
    - empty state text should be concise (`Run to see output.`).
  - Tool deviation:
    - URL Parser and Query Editor is interactive/live and should behave as an editor-first surface (not a JSON request/response form).
  - Footer actions are sticky and concise:
    - primary `Run`
    - secondary `Copy`
    - optional per-tool history sheet action on the right.
  - Copy feedback should use Sonner toasts (no persistent inline status text).
  - History logging icon mapping (no custom color accents):
    - `Eye` = history on
    - `EyeOff` = history off
    - `CircleSlash` = not logged by design
- Composables:
  - queries: `resources/js/composables/queries/**`
  - mutations: `resources/js/composables/mutations/**`
- Authorization helper:
  - `resources/js/composables/hooks/auth/useAuthorization.js`
  - prefer helper methods (`can`, `canAny`, `hasRole`, etc.) over ad-hoc role/permission checks in components.
  - admin navigation rule:
    - show admin group only if at least one admin destination is allowed.
    - show admin child links only when their specific permission is granted (`admin:*:view`).
    - admin logs entry is gated by `admin:logs:view`.
  - usage snippets:
    - `docs/admin-rbac-usage-snippets.md`
- Local folder barrels only (for example `.../tools/hash-generator/index.js`).

## First Render Hydration (Required)
- Avoid first-render UI flashes: pages that depend on remote query data must receive an initial dataset from the web route (`Inertia::render(...)` props).
- TanStack Query hooks on those pages must consume that dataset via `config.initialData`.
- Pattern:
  1. backend web route prepares initial payload (same shape as query response where possible),
  2. page reads initial props with `usePage().props`,
  3. query hook is called with `config.initialData` (and optional `initialDataUpdatedAt`).
- Do not ship loading-only first paint for core page content when initial data can be provided from the server.

## Strict Constraint
- Do not modify `resources/js/components/ui/**`.
- For new UI needs, prefer existing shadcn components/patterns first.
- For input/form fields, use shadcn `Label` (not plain text tags like `p`) and connect labels to controls with `htmlFor` + matching `id`.
- If a needed shadcn component is not installed, install/add it before building native/custom UI alternatives.

## Naming / Organization Rules
- One composable per file, single intent:
  - `useXQuery.js`
  - `useYMutation.js`
- Group by tool/domain folder, not giant shared files.
- Keep imports explicit, domain-scoped.

## Subscription / Tier Rules
- Free, Standard, Pro tiers.
- Tier checks are enforced backend-first.
- Frontend should reflect lock state but not be trusted for enforcement.
- Snippet sharing capabilities are tier-driven via `plan_limits.tiers.*.snippet_sharing`.

## Snippet Data Security (V1 Option 1)
- Snippet content is encrypted at rest using Laravel application encryption (`APP_KEY`).
- Snippet titles remain plaintext/searchable for discoverability.
- UI must disclose this boundary clearly:
  - content encrypted at rest,
  - title not encrypted.
- This is not E2EE; server runtime with key access can decrypt for authorized requests.
- E2EE is deferred to later roadmap (V3+).

## Account Email Aliases
- Verified email aliases are stored in `user_emails`.
- Primary alias is auto-ensured from the account login email.
- Alias lifecycle APIs:
  - `api.auth.user-emails.index`
  - `api.auth.user-emails.store`
  - `api.auth.user-emails.verify`
  - `api.auth.user-emails.resend`
  - `api.auth.user-emails.destroy`

## Snippet Sharing APIs
- Authenticated management routes:
  - `api.auth.snippet-shares.index`
  - `api.auth.snippet-shares.store`
  - `api.auth.snippet-shares.destroy`
- Public/guest resolve route:
  - `api.guest.snippet-shares.resolve`
- Restricted share invites:
  - `SnippetShareInvitesRequested` event triggers invite mail to recipient emails.
  - Share URL points to `authed.snippet-shares.show` (auth required), then resolve enforces verified-recipient match.
- Share resolution must enforce:
  - revoked/expired checks
  - snippet availability checks
  - restricted recipient verification match
  - burner view cap atomically

## Snippet Sharing UX Contract
- Visibility intent must stay explicit in UI copy:
  - `Public`: anyone with URL can view.
  - `Private`: owner-only unless shared.
  - `Burner`: private limited-view URL.
- Save actions in snippet create flow are intentionally split:
  - `Save + email invite` -> private only.
  - `Save + copy URL` -> public only.
- Enabling burner mode during create/edit must force private visibility and show a plain-language notice.

## Notification Events / Mail
- Use domain events + listeners for user lifecycle email delivery:
  - `UserSignedUp` -> `SendSignupWelcomeEmail`
  - `UserAliasAdded` -> `SendAliasAddedEmail`
- Mailables use markdown templates under `resources/views/mail/users/**`.
- Dispatch signup notification only for newly created accounts (`wasRecentlyCreated`).

## V2 (Deferred) Desktop Hybrid Notes
- Candidate: NativePHP desktop app (macOS/Windows) using local SQLite.
- Model: local-first tools + cloud-authoritative account/entitlements.
- Snippets remain cloud-backed for public/share use cases; desktop may cache/sync snippet records via API.
- Future Pro concept: vanity snippet URLs managed in cloud.
- For multi-device sync, prefer application-level delta sync APIs over raw SQLite file sync.

## Change Discipline
- Update relevant task board checkboxes when complete.
- Add or update tests for new action + endpoint behavior.
- Validate with:
  - `php artisan test`
  - `npm run build`

## Parallel Chat Discipline
- Use a dedicated branch and preferably a dedicated git worktree per chat.
- Register active scope in `docs/workstream-locks.md` before editing.
- Read `docs/multi-chat-collaboration-playbook.md` and `docs/context-index.md` first when starting a new workstream.
