# Multi-Chat Collaboration Playbook

Last updated: 2026-02-10

## Goal
- Let multiple chats work on the same repo without stomping each other.
- Keep changes reviewable, reversible, and mergeable.

## Non-Negotiables
- One chat = one branch.
- Prefer one chat = one worktree.
- Never share the same physical checkout for concurrent edits.
- Update `docs/workstream-locks.md` before editing code.
- Keep each branch scoped to a single workstream.

## Recommended Setup
1. Start from up-to-date `develop`:
   - `git checkout develop`
   - `git pull`
2. Create a dedicated worktree + branch:
   - `git worktree add ../cereal-eyes-<topic> -b codex/<topic>`
3. Open that worktree in its own editor window/tab.
4. Run only that workstream there.

## Workstream Lock Protocol
- Use `docs/workstream-locks.md` as a lightweight lock board.
- Add a row before changing files:
  - workstream
  - branch/worktree
  - owner/chat
  - file scope (glob or folders)
  - status (`active`, `review`, `done`, `blocked`)
- Keep lock scope narrow (avoid “entire repo” locks).
- If you need files owned by another active lock:
  - coordinate first
  - or defer until that lock is `done`.

## File Ownership Rules
- If a file is in an active lock, treat it as read-only.
- For shared touchpoints (`routes/*`, `config/*`, core layout files):
  - isolate changes to minimal hunks
  - add explicit notes in PR description.

## Branch / PR Strategy
- Open small PRs per workstream.
- Rebase frequently onto `develop`:
  - `git fetch`
  - `git rebase origin/develop`
- Resolve conflicts in your worktree, not in main checkout.
- Merge order:
  1. schema/migrations and backend contracts
  2. API consumers (frontend/composables)
  3. polish/docs.

## Migration Safety
- For concurrent backend work, avoid migration name collisions.
- Prefix migration names by date/time and domain.
- Document migration intent in PR summary.

## Testing Contract per PR
- Backend/API change: run targeted `php artisan test --filter=...`.
- UI/composable change: run `npm run lint` and `npm run build`.
- If full suite is skipped, call that out explicitly.

## Conflict Recovery
- If two branches changed same core file:
  1. keep backend contract source of truth first
  2. replay UI changes on top
  3. re-run impacted tests/build.

## Quick Start Checklist for New Chat
- Read:
  - `docs/agent-handoff.md`
  - `docs/architecture-conventions.md`
  - `docs/context-index.md`
  - `docs/v1-launch-checklist.md`
- Register your lock in `docs/workstream-locks.md`.
- Create/confirm dedicated branch + worktree.

