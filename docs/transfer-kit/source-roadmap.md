# Roadmap

Last updated: 2026-02-19 (single-board consolidation)

## How to Use This Board
- This is the **only active planning board**.
- Grouping is by category, and each category is sorted by effort: **Small -> Medium -> Large -> XL**.
- UI/UX polish and edge-case cleanup items are excluded here (tracked in Figma redesign work).
- Historical planning detail is available in Git history; do not split planning across new docs.

---

## 1) Platform

### Small effort
- [ ] **Document external API client request contract**
  - Confirm and publish CORS + content-type expectations for CLI/extensions.
  - Include canonical request/response examples for auth errors and scope failures.

- [ ] **Add API key extension smoke script**
  - Add a repeatable local script that validates key creation, scoped requests, revoke, and rotate.

### Medium effort
- [ ] **Add admin-facing API key usage filters**
  - Add richer token metadata filtering (status/activity/scope) in admin visibility surfaces.

- [ ] **Add API key abuse guard hooks**
  - Define and implement suspicious usage hooks (failure bursts, abnormal traffic, temporary key suspension path).

- [ ] **Close tool architecture debt from legacy migration**
  - Remove temporary route aliases.
  - Run final naming/dead-import cleanup pass.

### Large effort
- [ ] **Implement Dev Tester Allowlist Mode**
  - Restrict dev sign-in to allowlisted users only.
  - Support verified email + linked provider identity matching.
  - Provide simple add/remove ops workflow.

### XL effort
- [ ] **NativePHP feasibility + architecture spike (blocker epic)**
  - Evaluate desktop strategy (macOS/Windows) for local-network tooling use cases.
  - Define security/auth/session model for desktop runtime.
  - This is the blocker for the Hoppscotch-like API client feature.

---

## 2) Stability & Health

### Small effort
- [ ] **Publish customer support triage templates**
  - Templates: auth/login, billing, missing email, snippet access/share.

- [ ] **Assign weekly metrics review owner + cadence**

- [ ] **Add lightweight product experiment checklist**

### Medium effort
- [ ] **Execute backup/privacy revalidation drill (scheduled: March 5, 2026)**
  - Restore drill (prove recoverability).
  - Hard-delete account verification pass.
  - Sensitive telemetry leakage scan.

- [ ] **Record launch-week API latency baselines**
  - Capture p50/p95/p99 for tools, snippets, auth, billing APIs.
  - Store baseline values in runbook docs.

- [ ] **Adopt regression-test intake policy**
  - Every production bug gets a linked regression test before close.

### Large effort
- [ ] **Build `app doctor` command**
  - Validate critical env/config/runtime dependencies (Forge-focused).

- [ ] **CI pipeline guardrails (deferred/cost-aware)**
  - PR/push checks for tests/build with optional lint/static analysis.

- [ ] **Laravel Dusk smoke coverage (deferred)**
  - Critical auth/snippet/billing happy-path browser checks.

---

## 3) Features

### Medium effort
- [ ] **Date Conversion: timestamp format inference**
  - Detect sec/ms/micro/nano/ISO with confidence + ambiguity warnings.
  - Allow manual override when inference is uncertain.

- [ ] **Snippet sharing: vanity share URLs**
  - Pro-only friendly share URLs for non-burner flows.

- [ ] **Snippet sharing: open analytics events**
  - Optional open/view telemetry for share links (privacy-safe).

- [ ] **Snippet sharing: recipient invite notification pass**
  - Improve invite notification lifecycle and recipient clarity (feature behavior only).

### Large effort
- [ ] **Service Health Monitor (Pro)**
  - User-defined **public endpoint** checks only.
  - Auth configuration + schedule controls with sensible limits.
  - Dashboard widgets for status visibility + alert outcomes.

- [ ] **Snippet syntax highlighting (V1.2 stream)**
  - Language selection metadata + highlighted render on view.
  - Keep encryption-at-rest behavior unchanged.

- [ ] **Tool output syntax highlighting**
  - Add highlighting for code-like tool outputs (JSON/XML/diff) with restrained theme options.

- [ ] **Tool consolidation initiative**
  - Evaluate overlapping converters and merge into format-driven input/output workbench where it reduces duplication.

### XL effort
- [ ] **Hoppscotch-like API client surface**
  - Saved requests, env vars, auth helpers, response viewer.
  - **Blocked by NativePHP feasibility decision** for localhost/private-network access expectations.

- [ ] **Privacy Trust Layer: Verifiable Privacy Receipts**
  - Issue signed, content-free receipts for sensitive actions (tool runs, snippet share operations, and destructive deletes).
  - Include request ID, policy decision, retention outcome, and timestamp in each receipt.
  - Add proof-of-purge receipts for hard-delete operations.
  - Expose a public verification endpoint + key rotation model for third-party receipt validation.
  - ✅ MVP foundation shipped: signed receipts on API responses + verification keyset/verify endpoints.

- [ ] **Realtime snippet notifications + direct send**
  - Session-time notifications and inbox updates via websockets.

- [ ] **AI Cron Builder (deferred V2)**
  - Keep parked until product policy + safety constraints are finalized.

---

## 4) Bugfixes

### Small effort
- [ ] **Herd wrapper reliability fix (non-blocking local tooling debt)**
  - Resolve local `herd php artisan ...` wrapper instability.

### Medium effort
- [ ] **Run focused production incident sweep (monthly)**
  - Convert recurring support/ops incidents into explicit bugfix tasks + tests.

---

## 5) Improvements

### Small effort
- [ ] **Resend single-domain consolidation verification**
  - Reconfirm delivery/auth posture after consolidation changes.

### Medium effort
- [ ] **Define launch funnel metrics**
  - Activation, retention, conversion definitions + instrumentation map.

- [ ] **Expand query hydration contract coverage**
  - Extend initial-data assertions beyond page-level to query-backed components where valuable.

### Large effort
- [ ] **History search scalability upgrade path**
  - Evaluate external search engine option (Meilisearch/Typesense) for larger history datasets.

---

## 6) General Backlog (Catchall)

- No active catchall items right now.

---

## Completed Milestones (Condensed)

These were verified complete and intentionally removed from active planning:
- Public launch pages + guest/auth route behavior.
- Admin RBAC workspace + complimentary entitlements.
- API key management foundation (scopes/rate limits/docs/health endpoint).
- Production billing/auth/mail guardrail pass.
- Hidden Character Checker launch.
- Snippet sharing reliability baseline and burner lifecycle fixes.
- Global/per-tool history rerun capability.
- Settings and public-page polish passes already shipped.
