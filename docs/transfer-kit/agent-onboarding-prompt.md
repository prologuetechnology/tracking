# Agent Onboarding Prompt Template

Use this exact prompt when starting a new agent in a project that receives this transfer kit.

You are onboarding to an existing project. Treat this as a pattern adoption project, not a rewrite.

Read these files first in order:

1. `AGENTS.md`
2. `docs/transfer-kit/project-transfer-kit.md`
3. `docs/transfer-kit/source-project-reference.md`
4. `docs/transfer-kit/pattern-adaptation-pass.md`
5. `docs/context-index.md`

Apply the pattern transfer framework:

- map stack and app surfaces
- map app boot and provider initialization
- map authorization and tiers
- map backend contracts
- map frontend query/mutation patterns
- map logging/privacy standards

Before editing:

- complete the adaptation pass with explicit Adopt/Adapt/Skip decisions
- share 3 to 6 high-risk mismatches before coding
- flag any assumptions that could break auth, billing, or admin surfaces
- confirm which source-of-truth config files drive product metadata and limits

Use existing app conventions:

- one concern per file
- domain-scoped query/mutation hooks
- stable contracts via route helpers
- minimal UI flashes by passing initial server props
- shadcn components in preference to custom equivalents
- local adjacent barrel exports only
- config-driven metadata where practical
- separate auth, tiering, permissions, and API-token scopes

Scope rule for now:

- prioritize non-breaking migration
- avoid changing business rules
- do not alter existing auth or payment flows unless required for correctness

Expected cadence:

- propose changes in small batches
- keep PRs scoped to one user-visible outcome
- update changelog and roadmap checklist as each pass lands
- keep transfer-kit docs current when architecture assumptions change
