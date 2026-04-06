# Context Index

Last updated: 2026-03-06

## Primary Read Order

1. `docs/agent-handoff.md`
2. `docs/architecture-conventions.md`
3. `docs/v1-launch-checklist.md`
4. `docs/multi-chat-collaboration-playbook.md`
5. `docs/workstream-locks.md`
6. `docs/stability-and-release-implementation-list.md`
7. `docs/transfer-kit/project-transfer-kit.md`
8. `docs/transfer-kit/source-project-reference.md`
9. `docs/transfer-kit/files-to-transfer.md`
10. `docs/transfer-kit/pattern-adaptation-pass.md`
11. `docs/transfer-kit/agent-onboarding-prompt.md`

## Product + Launch

- Launch readiness and P0/P1 items:
  - `docs/v1-launch-checklist.md`
- Release process:
  - `docs/release-process.md`
- Changelog:
  - `CHANGELOG.md`
- Transfer kit for cloning architecture patterns into another project:
  - `docs/transfer-kit/project-transfer-kit.md`
- Source-project build/reference for transfer work:
  - `docs/transfer-kit/source-project-reference.md`
- Exact transfer manifest:
  - `docs/transfer-kit/files-to-transfer.md`
- Cross-project onboarding pass:
  - `docs/transfer-kit/pattern-adaptation-pass.md`
- Agent brief template:
  - `docs/transfer-kit/agent-onboarding-prompt.md`
- Single active roadmap board (all categories + priorities + effort):
  - `docs/roadmap.md`
- Roadmap reference docs (historical context):
  - `docs/v1-1-roadmap.md`
  - `docs/v1-2-roadmap.md`
- Production cutover + smoke script:
  - `docs/production-go-live-checklist.md`
- Forge deploy runbook:
  - `docs/forge-deploy-runbook.md`
- Incident guardrails runbook:
  - `docs/incident-guardrails-runbook.md`
- API documentation runbook (Scribe):
  - `docs/api-docs-scribe.md`
- Admin RBAC usage snippets:
  - `docs/admin-rbac-usage-snippets.md`

## User Docs (Training)

- `docs/user/getting-started.md`
- `docs/user/snippets.md`
- `docs/user/sharing.md`
- `docs/user/tools.md`
- `docs/user/api-access.md`
- `docs/user/privacy.md`

## Backend Source of Truth

- Web routes:
  - `routes/web.php`
- API routes:
  - `routes/api.php`
- Tool catalog + status + tier gates:
  - `config/tools.php`
- SEO defaults:
  - `config/seo.php`
- Plan limits and quotas:
  - `config/plan_limits.php`
- API key abilities + defaults:
  - `config/api_access.php`
- Spark/billing:
  - `config/spark.php`
  - `app/Http/Middleware/EnsureSparkBillingConfigured.php`
  - `app/Services/Billing/PlanResolver.php`
  - `app/Services/Billing/BillingConfigurationValidator.php`
- Admin system logs surface:
  - `app/Actions/Admin/ListSystemLogs.php`
  - `app/Http/Controllers/Api/Admin/AdminSystemLogController.php`
  - `app/Http/Requests/Admin/AdminSystemLogIndexRequest.php`
  - `app/Http/Resources/Admin/AdminSystemLogResource.php`
- Logging policy and channels:
  - `config/logging.php`
  - `app/Logging/ConfigureStructuredLogging.php`
  - `app/Logging/RedactSensitiveContextProcessor.php`

## Frontend Source of Truth

- App shell/layout:
  - `resources/js/components/layout/AppLayout.jsx`
  - `resources/js/components/app-sidebar.jsx`
- Inertia app entry:
  - `resources/js/app.js`
- React composable contract:
  - `docs/react-frontend-structure-contract.md`
- Query/mutation structure:
  - `resources/js/composables/queries/**`
  - `resources/js/composables/mutations/**`

## Settings/Auth/Identity

- Account settings pages:
  - `resources/js/Pages/settings/Account.jsx`
  - `resources/js/Pages/settings/EmailAliases.jsx`
  - `resources/js/Pages/settings/Privacy.jsx`
  - `resources/js/Pages/settings/ApiAccess.jsx`
- Password + MFA APIs:
  - `app/Http/Controllers/Api/Settings/AccountSecurityController.php`
  - `app/Services/Auth/EmailMfaChallengeService.php`
- Social login flow:
  - `app/Http/Controllers/Auth/SocialiteController.php`
  - `app/Actions/Auth/HandleSocialLogin.php`
  - `app/Models/UserIdentityProvider.php`

## Snippets

- Snippet page controller + render props:
  - `app/Http/Controllers/SnippetPageController.php`
- Snippet API controller:
  - `app/Http/Controllers/Api/SnippetController.php`
- Snippet encryption-at-rest logic:
  - `app/Actions/Snippets/StoreSnippet.php`
  - `app/Actions/Snippets/UpdateSnippet.php`
- Snippet share resolve / access:
  - `app/Actions/SnippetShares/**`
  - `app/Http/Controllers/Api/SnippetShareController.php`

## Tool History

- History APIs + action stack:
  - `app/Http/Controllers/Api/Tools/HistoryController.php`
  - `app/Actions/Tools/History/**`
- History logging policy:
  - `app/Services/Tools/HistoryLoggingPolicy.php`
- History UI:
  - `resources/js/Pages/tools/History.jsx`
  - `resources/js/components/feature/tools/ToolHistorySheet.jsx`

## Testing Fast Paths

- Local predeploy sanity check:
  - `scripts/predeploy-check.sh --full`
- Critical path suite:
  - Run: `herd php artisan test --group=critical`
  - Coverage (grouped tests):
    - `tests/Feature/CriticalRouteSmokeTest.php`
    - `tests/Feature/PublicPagesSmokeTest.php`
    - `tests/Feature/PasswordAuthFlowTest.php`
    - `tests/Feature/SnippetActionsTest.php`
    - `tests/Feature/SnippetShareApiTest.php`
    - `tests/Feature/ApiTokenAccessTest.php`
- Additional useful suites:
  - Auth providers: `tests/Feature/SocialLoginProvidersTest.php`
  - Billing: `tests/Feature/BillingFlowTest.php`
  - Tool history: `tests/Feature/ToolHistorySearchTest.php`, `tests/Feature/ToolHistoryScopeTest.php`
