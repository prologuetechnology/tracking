# Pattern Adaptation Pass (Copy-to-Existing-Project)

Use this checklist to adapt the Cereal Eyes patterns to another already built repo.

Before filling this out, read:

- `docs/transfer-kit/project-transfer-kit.md`
- `docs/transfer-kit/source-project-reference.md`
- the target repo's own `README.md`
- the target repo's own `docs/context-index.md` if it exists

## 1) Baseline mapping

### 1.1 Runtime stack

| Area | Target project current state | Source pattern | Adopt / Adapt / Skip | Notes |
| --- | --- | --- | --- | --- |
| Backend framework |  | Laravel |  |  |
| PHP version |  | Laravel-compatible |  |  |
| Frontend framework |  | React + Inertia |  |  |
| Component kit |  | shadcn UI |  |  |
| Data fetching |  | TanStack Query |  |  |
| Queue/Cache |  | Redis/DB queue |  |  |
| Logging |  | Structured channels + JSON |  |  |
| Billing |  | Stripe/Spark |  |  |
| CI/test runner |  | Pest + JS lint/test |  |  |

### 1.2 App surface map

- Public routes and middleware:
- Authenticated web routes and middleware:
- JSON API and guards:
- Admin routes and authorization model:
- Public docs/API routes:

### 1.3 Boot and provider map

- Backend bootstrap file:
- Frontend bootstrap file:
- Inertia app entry and page resolution:
- Theme/provider initialization:
- Analytics/error tracking boot:

### 1.4 Authorization model

- RBAC library:
- Role naming strategy:
- Permission naming strategy:
- Tier/billing policy separation:
- Cross-cutting admin permissions:

## 2) Contract mapping

### 2.1 Backend contract

- shared props in Inertia:
- Form request + action boundaries:
- resource serialization pattern:
- service/action naming:
- route naming standard:
- config source-of-truth files:
- middleware alias strategy:

### 2.2 Frontend contract

- route helpers:
- query key strategy:
- mutation/error handling:
- initial data propagation:
- list/detail UI shell:
- component/ui folder strategy:
- local barrel export strategy:

## 3) Privacy and observability

- Sensitive payload logging policy:
- request correlation IDs:
- security event coverage:
- redaction standard:
- retention policy:
- user-visible trust features:

## 4) Build and release mapping

- PHP/composer workflow:
- JS package manager + build workflow:
- lint/format commands:
- test commands:
- deploy/runbook equivalents:
- changelog/release tagging process:

## 5) Pattern-by-pattern migration decisions

For each doc/pattern, answer Adopt / Adapt / Skip.

- Inertia + layout contract
- public vs authenticated surface split
- Tooling/action/service layering
- Query/mutation split
- Initial data hydration
- Tool shell standard
- Settings shell and sections
- Admin split layout
- Audit/logs/surface patterns
- Public markdown/doc SEO docs approach
- Post-deploy runbook style
- Roadmap and changelog style

## 6) Documentation handoff update

- `docs/context-index` points at correct local files
- `docs/agent-handoff` updated with active priorities
- `CHANGELOG.md` reflects start state and migration decisions
- A new `docs/transfer-kit` section exists for this adaptation

## 7) Implementation sequence

1. Stabilize auth/route map first.
2. Stabilize boot and provider initialization next.
3. Then move contract and data-layer patterns.
4. Then apply frontend structural patterns.
5. Then apply observability and privacy rules.
6. Finish with release/docs/runbook updates.

## 8) Sign-off

- [ ] No route names copied blindly without adaptation.
- [ ] Boot and provider assumptions reviewed against target stack.
- [ ] Permissions and admin scope reviewed against target model.
- [ ] Initial render strategy approved for key pages.
- [ ] No cross-team sensitive data logging.
- [ ] Transfer kit committed and visible in project context index.
