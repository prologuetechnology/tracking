# Changelog

All notable changes to this project will be documented in this file.

This project follows a lightweight, human-maintained changelog format inspired by "Keep a Changelog".

## Unreleased

### Added
- N/A

### Changed
- N/A

### Fixed
- N/A

## v1.0.7 - 2026-02-24

### Added
- Settings → Access history now includes an “Other active sessions” section that lists current parallel sessions with device and best-effort location context.
- Per-session revoke action with confirmation, plus backend endpoints to list/revoke active sessions.
- New access event type for per-session revocations (`security:session:revoked`).

### Changed
- Access history “not you?” safety action now invalidates active-session data in addition to access-event and API-key views.
- Device label parsing is now centralized in a shared resolver for consistent output across access history surfaces.

### Fixed
- Session-management APIs now safely handle requests where a Laravel session store is unavailable by falling back to cookie/header session identifiers.

## v1.0.6 - 2026-02-24

### Added
- Privacy receipt verification explainer callouts in both global history and per-tool history run detail sheets.

### Changed
- Tool history sheets now force-refresh on open (invalidate + immediate refetch + short retry) so new runs appear without a page reload.
- Receipt badge and status alignment tightened across tool history list rows and receipt panels for cleaner scanability.

### Fixed
- Improved visual consistency for receipt indicators between history list and run detail views.

## v1.0.5 - 2026-02-23

### Added
- Shared tool export engine for PDF and CSV downloads, including per-tool export capability flags.
- Download actions in tool footers (`Download output (PDF)` and `Download input + output (PDF)`), with CSV shown only where supported.
- CSV downloads for:
  - CSV <-> JSON (JSON -> CSV mode)
  - UUID Generator + Validator (generate mode, one UUID per row)
- PDF export support for custom tools:
  - URL Parser and Query Editor
  - HTTP Status Lookup

### Changed
- Exported PDFs now include run metadata (tool context, route/history type, config/options, timestamps, and output size details).

### Fixed
- CSV export hardening now protects against spreadsheet formula injection for unsafe leading characters.

## v1.0.4 - 2026-02-23

### Added
- Verifiable privacy receipts in tool history detail views.
- Legacy run fallback panel: “No privacy receipt for this run” for older entries created before receipts shipped.
- In-app privacy receipt explainer callouts in tool history sheet and global history.

### Changed
- Tool history sheets now refetch on open so the latest run appears without a full page refresh.
- Receipt badge alignment and row layout improved in history lists.

### Fixed
- History UX now handles mixed receipt/non-receipt datasets more clearly and consistently.

## v1.0.3 - 2026-02-19

### Added
- API endpoint to email an existing snippet share link (`POST /api/snippets/{snippet}/shares/{snippetShare}/emails` plus `/api/v1` equivalent).
- New frontend mutation for emailing existing snippet shares.
- API docs quick-link in Settings → API Access.

### Changed
- Burner flow on snippet detail pages now generates links first, then manages each link via icon actions (copy, email, revoke).
- Burner email flow now sends an existing burner link instead of silently creating a new burner share.
- Burner creation/email actions are blocked when a snippet is public, with clearer in-UI guidance.
- New snippet save flows now return users directly to the saved snippet view.

### Fixed
- Added feature coverage for “email existing burner share” in snippet sharing tests.

## v1.0.2 - 2026-02-19

### Added
- Admin operations dashboard metrics for API access, billing webhook sync, mail delivery health, and incident guardrail thresholds.
- Incident runbook and expanded production go-live/checklist coverage for billing webhooks, guardrails, and mail health.
- Global tool history “Load in tool” flow to rerun from history details.
- Session handoff helper for history reruns between the global history page and tool pages.

### Changed
- Alias verification email links now verify directly when opened (token prefill fallback remains for older links).
- Tool pages now consume queued history runs to rehydrate inputs/config before rerun.
- Post-deployment and V1.1 cleanup task boards were reality-checked and aligned with shipped work.

### Fixed
- Billing settings UX for entitlement-managed users: clearer active-entitlement handling and billing portal gating behavior.
- Mail delivery instrumentation and fallback wiring for auth/security/system emails.

## v1.0.1 - 2026-02-18

### Added
- Hidden Character Checker tool (Inspect & Validate): detect invisible/problematic characters and copy cleaned output.
- Tool history rerun: load inputs from a previous run back into the tool (no auto-run).

### Changed
- Tool history type columns now store strings (not MySQL enums) to avoid enum drift when adding new tools.

## v1.0.0 - 2026-02-12

### Added
- Initial production launch of the Cereal Eyes app.
