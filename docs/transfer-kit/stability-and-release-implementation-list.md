# Stability + Release Implementation List

Last updated: 2026-02-17

This is our "don't break prod" workstream. We keep this list current and burn it down over time.

## A) Docs + onboarding (In progress)

- [x] Create user docs (short, task-based)
  - [x] Getting started (what the app is + where things live)
  - [x] Snippets (public/private/burner + inbox/outbox mental model)
  - [x] Sharing (restricted vs burner)
  - [x] Tools (privacy posture, history, limitations)
  - [x] API access (Pro-only, scopes, revocation)
  - [x] Privacy & retention (what we store, how to purge)
- [x] Improve `README.md`
  - [x] Herd-first local setup (macOS) and expected workflow
  - [x] "How to run tests / build" section (copy-paste friendly)
  - [x] High-level architecture + conventions pointers (docs index)

## B) Release notes + tagging (In progress)

- [x] Add `CHANGELOG.md` (Keep a Changelog style)
- [x] Add release process docs (how we tag + what gets updated)
- [x] Define minimal release checklist (tests/build/smoke)

## C) Tests & coverage (Planned)

- [x] Define and maintain a "critical path" test suite (fast, must-pass)
  - Auth (register/login/verify/reset)
  - Snippets CRUD + share models (inbox/outbox/burner inbox)
  - Tier gating + quotas
  - API keys/scopes + rate limits
- [x] Add a local predeploy check script (tests + build)
- [ ] Add coverage for recent regressions as they appear (regression tests)

## D) CI/CD guardrails (Planned)

- [ ] Add CI that runs on every PR/push:
  - `php artisan test`
  - `npm run build`
  - optional follow-up: lint/static analysis as separate jobs
- [ ] Post-deploy smoke checklist (documented)

## E) Nice-to-haves

- [ ] Laravel Dusk (UI smoke tests for a few critical flows)
- [ ] "app doctor" command to validate required env/config (esp. in Forge)
